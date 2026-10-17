<?php

/**
 * @file plugins/generic/viewcounter/ViewcounterPlugin.php
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com)
 * Original OJS 3.3 plugin by STI FFLCH and ABCD/USP.
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ViewcounterPlugin
 *
 * @brief Shows each article's abstract views and downloads (sum of galleys) on
 * the article summary lists and on the article landing page.
 *
 * The badge is injected through the core content hooks
 * (Templates::Issue::Issue::Article and Templates::Article::Main); no core
 * template is replaced. A small stylesheet/script moves the badge next to the
 * title when the expected markup is present; otherwise it simply stays at the
 * hook location.
 *
 * Counts come from the statistics service (publicationStats) and are cached
 * per submission for 24 hours. Downloads are galley downloads only, the same
 * number as Statistics > Articles. On list pages (issue TOC, journal home,
 * search, category) the cache is primed in bulk with a single grouped query
 * per metric type. When the counts cannot be read the error is logged and no
 * badge is shown.
 */

namespace APP\plugins\generic\viewcounter;

use APP\core\Application;
use APP\core\Services;
use APP\notification\NotificationManager;
use APP\statistics\StatisticsHelper;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\Cache;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class ViewcounterPlugin extends GenericPlugin
{
    /** Cache lifetime for the per-submission counts, in seconds (24 h). */
    public const CACHE_TTL = 60 * 60 * 24;

    /** @var array<int,array{views:int,downloads:int}|false> Per-request memo of the counts (false: could not be read). */
    private array $counts = [];

    /**
     * Register the plugin and, where it is enabled, its hooks.
     *
     * @param string $category
     * @param string $path
     * @param null|int $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        // Only reader-facing pages of a journal reach these hooks.
        if (!$success || Application::isUnderMaintenance() || !$this->getEnabled($mainContextId)) {
            return $success;
        }

        Hook::add('Templates::Issue::Issue::Article', [$this, 'displaySummaryBadge']);
        Hook::add('Templates::Article::Main', [$this, 'displayDetailsBadge']);
        Hook::add('TemplateManager::display', [$this, 'handleTemplateDisplay']);

        return $success;
    }

    /**
     * Name shown in the plugins list.
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.viewcounter.displayName');
    }

    /**
     * Description shown in the plugins list.
     */
    public function getDescription(): string
    {
        return __('plugins.generic.viewcounter.description');
    }

    /**
     * Add the settings action to the plugin entry in the plugins list.
     */
    public function getActions($request, $verb): array
    {
        $actions = parent::getActions($request, $verb);
        if (!$request->getContext() || !$this->getEnabled()) {
            return $actions;
        }
        $router = $request->getRouter();
        $url = $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']);
        array_unshift($actions, new LinkAction('settings', new AjaxModal($url, $this->getDisplayName()), __('manager.plugins.settings')));
        return $actions;
    }

    /**
     * Show and save the settings form.
     */
    public function manage($args, $request): JSONMessage
    {
        // The settings belong to a journal; there is nothing to configure site-wide.
        if ($request->getUserVar('verb') !== 'settings' || !$request->getContext()) {
            return parent::manage($args, $request);
        }

        $form = new ViewcounterSettingsForm($this, $request->getContext()->getId());
        if (!$request->getUserVar('save')) {
            $form->initData();
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->readInputData();
        if (!$form->validate()) {
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->execute();
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification($request->getUser()->getId());
        return new JSONMessage(true);
    }

    //
    // Hooks
    //

    /**
     * Hook Templates::Issue::Issue::Article — badge on the article summary
     * (issue TOC, journal home, search results).
     *
     * @param array $args [&$params, $smarty, &$output]
     */
    public function displaySummaryBadge($hookName, $args): bool
    {
        if (!$this->isFeatureEnabled('showInSummary')) {
            return Hook::CONTINUE;
        }
        $smarty = $args[1];
        $output = & $args[2];
        $submission = $smarty->getTemplateVars('article');
        if ($submission) {
            $output .= $this->renderBadge($submission, 'summary');
        }
        return Hook::CONTINUE;
    }

    /**
     * Hook Templates::Article::Main — badge on the article landing page.
     *
     * @param array $args [&$params, $smarty, &$output]
     */
    public function displayDetailsBadge($hookName, $args): bool
    {
        if (!$this->isFeatureEnabled('showInDetails')) {
            return Hook::CONTINUE;
        }
        $smarty = $args[1];
        $output = & $args[2];
        $submission = $smarty->getTemplateVars('article');
        if ($submission) {
            $output .= $this->renderBadge($submission, 'details');
        }
        return Hook::CONTINUE;
    }

    /**
     * Hook TemplateManager::display — adds the stylesheet/script on the reader
     * pages, primes the counts cache in bulk on list pages and keeps the
     * {viewcounterStats} Smarty function available for customised themes.
     *
     * @param array $args [&$templateMgr, &$template]
     */
    public function handleTemplateDisplay($hookName, $args): bool
    {
        /** @var TemplateManager $templateMgr */
        $templateMgr = $args[0];
        $template = (string) $args[1];

        if (strncmp($template, 'frontend/', 9) !== 0) {
            return Hook::CONTINUE;
        }

        try {
            $templateMgr->registerPlugin('function', 'viewcounterStats', [$this, 'smartyViewcounterStats']);
        } catch (\Throwable $e) {
            // Already registered in this request.
        }

        $summary = $this->isFeatureEnabled('showInSummary');
        $details = $this->isFeatureEnabled('showInDetails');
        if (!$summary && !$details) {
            return Hook::CONTINUE;
        }

        $this->addAssets($templateMgr);

        if ($summary) {
            switch ($template) {
                case 'frontend/pages/catalogCategory.tpl':
                    $this->primeCache((array) $templateMgr->getTemplateVars('publishedSubmissions'));
                    break;
                case 'frontend/pages/issue.tpl':
                case 'frontend/pages/indexJournal.tpl':
                    $submissions = [];
                    foreach ((array) $templateMgr->getTemplateVars('publishedSubmissions') as $section) {
                        foreach ($section['articles'] ?? [] as $submission) {
                            $submissions[] = $submission;
                        }
                    }
                    $this->primeCache($submissions);
                    break;
                case 'frontend/pages/search.tpl':
                    $results = $templateMgr->getTemplateVars('results');
                    if (is_object($results) && method_exists($results, 'toArray')) {
                        $results = $results->toArray();
                    }
                    $submissions = [];
                    foreach (is_iterable($results) ? $results : [] as $result) {
                        if (is_array($result) && isset($result['publishedSubmission'])) {
                            $submissions[] = $result['publishedSubmission'];
                        }
                    }
                    $this->primeCache($submissions);
                    break;
            }
        }

        return Hook::CONTINUE;
    }

    /**
     * Smarty function kept for backwards compatibility with themes that copied
     * the old templates: {viewcounterStats submission=$article}
     */
    public function smartyViewcounterStats($params, $smarty): string
    {
        $submission = $params['submission'] ?? null;
        if (!$submission) {
            return '';
        }
        return $this->renderBadge($submission, ($params['place'] ?? '') === 'details' ? 'details' : 'summary', false);
    }

    //
    // Rendering
    //

    /**
     * Renders the badge (views | downloads). The numbers and icons are hidden
     * from assistive technology, which reads a visually hidden text instead
     * ("Views: 12"). Nothing is rendered when the counts cannot be read.
     *
     * @param string $place 'summary' or 'details'
     * @param bool $relocate Whether the script may move the badge next to the title
     */
    public function renderBadge($submission, string $place, bool $relocate = true): string
    {
        $counts = $this->getCounts($submission);
        if ($counts === null) {
            return '';
        }

        $viewsLabel = htmlspecialchars(__('plugins.generic.viewcounter.views'), ENT_QUOTES);
        $downloadsLabel = htmlspecialchars(__('plugins.generic.viewcounter.downloads'), ENT_QUOTES);

        $eye = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        $down = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

        $place = $place === 'details' ? 'details' : 'summary';
        $attrs = $relocate ? ' data-vc-place="' . $place . '"' : '';

        return '<span class="viewcounter-stats viewcounter-stats--' . $place . '"' . $attrs . '>'
            . self::renderCount($viewsLabel, $eye, $counts['views'])
            . self::renderCount($downloadsLabel, $down, $counts['downloads'])
            . '</span>';
    }

    /**
     * One count of the badge: icon and number for the eye, a visually hidden
     * label and number for screen readers.
     *
     * @param string $label Already escaped label
     */
    protected static function renderCount(string $label, string $icon, int $count): string
    {
        // The tooltip sits on the visible part, which assistive technology does
        // not see: a title on the count itself would be read out as well.
        return '<span class="viewcounter-count">'
            . '<span class="viewcounter-visible" aria-hidden="true" title="' . $label . '">' . $icon . ' ' . $count . '</span>'
            . '<span class="viewcounter-sr">' . $label . ': ' . $count . '</span>'
            . '</span>';
    }

    /**
     * Adds the plugin stylesheet and script to the frontend page.
     */
    protected function addAssets(TemplateManager $templateMgr): void
    {
        $request = Application::get()->getRequest();
        $base = $request->getBaseUrl() . '/' . $this->getPluginPath();
        // The plugin version, not the application's: the markup and the script
        // change together, so a browser must not keep the script of a release
        // that expected another markup.
        $version = $this->getPluginPath() ? $this->getCurrentVersion() : null;
        $query = $version ? '?v=' . rawurlencode($version->getVersionString()) : '';
        $templateMgr->addStyleSheet('viewcounter', $base . '/css/viewcounter.css' . $query, [
            'contexts' => ['frontend'],
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
        $templateMgr->addJavaScript('viewcounter', $base . '/js/viewcounter.js' . $query, [
            'contexts' => ['frontend'],
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
    }

    //
    // Statistics
    //

    /**
     * Views and downloads of one submission, from the request memo, the
     * application cache (24 h) or, as a last resort, two aggregation queries.
     * Null when they cannot be read: the error is logged and no number is
     * made up.
     *
     * @return null|array{views:int,downloads:int}
     */
    public function getCounts($submission): ?array
    {
        $submissionId = (int) $submission->getId();
        if (array_key_exists($submissionId, $this->counts)) {
            return $this->counts[$submissionId] ?: null;
        }

        $contextId = (int) $submission->getData('contextId');
        try {
            $counts = Cache::remember(self::cacheKey($submissionId), self::CACHE_TTL, function () use ($contextId, $submissionId) {
                return [
                    'views' => $this->sumByType($contextId, [$submissionId], [Application::ASSOC_TYPE_SUBMISSION])[$submissionId] ?? 0,
                    'downloads' => $this->sumByType($contextId, [$submissionId], self::fileAssocTypes())[$submissionId] ?? 0,
                ];
            });
        } catch (\Throwable $e) {
            error_log('viewcounter: the counts of submission ' . $submissionId . ' could not be read: ' . $e->getMessage());
            $this->counts[$submissionId] = false;
            return null;
        }

        return $this->counts[$submissionId] = $counts;
    }

    /**
     * Fills the cache for every submission of a list that is not cached yet,
     * with one grouped query per metric type instead of one per row.
     *
     * @param iterable $submissions Submission objects
     */
    public function primeCache(iterable $submissions): void
    {
        $byContext = [];
        foreach ($submissions as $submission) {
            if (!is_object($submission) || !method_exists($submission, 'getId')) {
                continue;
            }
            $byContext[(int) $submission->getData('contextId')][] = (int) $submission->getId();
        }

        foreach ($byContext as $contextId => $ids) {
            $ids = array_values(array_unique($ids));
            try {
                $keys = array_map([self::class, 'cacheKey'], $ids);
                $cached = Cache::many($keys);
                $missing = [];
                foreach ($ids as $i => $id) {
                    $hit = $cached[$keys[$i]] ?? null;
                    if (is_array($hit)) {
                        $this->counts[$id] = $hit;
                    } else {
                        $missing[] = $id;
                    }
                }
                if (!$missing) {
                    continue;
                }

                $views = $this->sumByType($contextId, $missing, [Application::ASSOC_TYPE_SUBMISSION]);
                $downloads = $this->sumByType($contextId, $missing, self::fileAssocTypes());

                $values = [];
                foreach ($missing as $id) {
                    $counts = ['views' => $views[$id] ?? 0, 'downloads' => $downloads[$id] ?? 0];
                    $this->counts[$id] = $counts;
                    $values[self::cacheKey($id)] = $counts;
                }
                Cache::putMany($values, self::CACHE_TTL);
            } catch (\Throwable $e) {
                // Rendering falls back to per-submission lookups.
                error_log('viewcounter: the counts of a list could not be read: ' . $e->getMessage());
            }
        }
    }

    /**
     * Grouped sum of the metrics of the given assoc types, keyed by submission id.
     *
     * @return array<int,int>
     */
    protected function sumByType(int $contextId, array $submissionIds, array $assocTypes): array
    {
        $filters = [
            'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
            'dateEnd' => date('Y-m-d', strtotime('yesterday')),
            'contextIds' => [$contextId],
            'submissionIds' => $submissionIds,
            'assocTypes' => $assocTypes,
        ];
        $rows = Services::get('publicationStats')
            ->getQueryBuilder($filters)
            ->getSum([StatisticsHelper::STATISTICS_DIMENSION_SUBMISSION_ID])
            ->get();

        $sums = [];
        foreach ($rows as $row) {
            $sums[(int) $row->submission_id] = (int) $row->metric;
        }
        return $sums;
    }

    /**
     * Assoc types that count as downloads: galley files only, as in
     * Statistics > Articles. Supplementary files
     * (ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER) are left out.
     */
    protected static function fileAssocTypes(): array
    {
        return [Application::ASSOC_TYPE_SUBMISSION_FILE];
    }

    /**
     * Cache key of a submission's counts.
     */
    public static function cacheKey(int $submissionId): string
    {
        // v2: since 1.3.1.0 downloads leave supplementary files out, so counts
        // cached by an earlier release must not be served.
        return 'viewcounter-v2-' . $submissionId;
    }

    /**
     * Whether a display place is enabled for the current context. Default: enabled.
     */
    private function isFeatureEnabled(string $key): bool
    {
        $context = Application::get()->getRequest()->getContext();
        $contextId = $context ? $context->getId() : PKPApplication::CONTEXT_SITE;
        $value = $this->getSetting($contextId, $key);
        return $value === null ? true : (bool) $value;
    }
}
