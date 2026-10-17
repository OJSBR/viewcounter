<?php

/**
 * @file plugins/generic/viewcounter/tests/ViewcounterPluginTest.php
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ViewcounterPluginTest
 *
 * @brief The badge markup, the counts and their cache, the pages that prime
 *        it, what happens when the counts cannot be read, and the site level.
 */

namespace APP\plugins\generic\viewcounter\tests;

use APP\plugins\generic\viewcounter\ViewcounterPlugin;
use APP\plugins\generic\viewcounter\ViewcounterSettingsForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(ViewcounterPlugin::class)]
#[CoversClass(ViewcounterSettingsForm::class)]
class ViewcounterPluginTest extends PKPTestCase
{
    /** A plugin whose counts come from a fixed table instead of the statistics service. */
    private function plugin(array $counts = []): ViewcounterPlugin
    {
        return new class ($counts) extends ViewcounterPlugin {
            public int $queries = 0;

            public array $assocTypes = [];

            public bool $fail = false;

            public function __construct(private array $fixed)
            {
                parent::__construct();
            }

            public function getSetting($contextId, $name)
            {
                return null;
            }

            protected function sumByType(int $contextId, array $submissionIds, array $assocTypes): array
            {
                $this->queries++;
                $this->assocTypes[] = $assocTypes;
                if ($this->fail) {
                    throw new \RuntimeException('metrics table unavailable');
                }
                $key = in_array(\APP\core\Application::ASSOC_TYPE_SUBMISSION, $assocTypes, true) ? 'views' : 'downloads';
                return array_intersect_key(array_map(fn ($c) => $c[$key], $this->fixed), array_flip($submissionIds));
            }
        };
    }

    private function submission(int $id): \APP\submission\Submission
    {
        $submission = new \APP\submission\Submission();
        $submission->setId($id);
        $submission->setData('contextId', 1);
        return $submission;
    }

    protected function tearDown(): void
    {
        foreach ([900001, 900002, 900003] as $id) {
            \Illuminate\Support\Facades\Cache::forget(ViewcounterPlugin::cacheKey($id));
        }
        parent::tearDown();
    }

    public function testTheBadgeCarriesBothCountsAsVisuallyHiddenText(): void
    {
        $html = $this->plugin([900001 => ['views' => 12, 'downloads' => 3]])->renderBadge($this->submission(900001), 'summary');

        $this->assertStringContainsString('class="viewcounter-stats viewcounter-stats--summary" data-vc-place="summary"', $html);
        // Screen readers get "Views: 12"; the bare number and the icon are hidden from them.
        $this->assertMatchesRegularExpression('/<span class="viewcounter-sr">[^<]+: 12<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span class="viewcounter-sr">[^<]+: 3<\/span>/', $html);
        $this->assertMatchesRegularExpression('/<span class="viewcounter-visible" aria-hidden="true" title="[^"]+"><svg[^>]*aria-hidden="true">.*?<\/svg> 12<\/span>/', $html);
        $this->assertSame(4, substr_count($html, 'aria-hidden="true"'), 'Both icons and both visible counts.');
        // aria-label on a generic span is ignored by many screen readers.
        $this->assertStringNotContainsString('aria-label', $html);
    }

    public function testDownloadsAreGalleyDownloadsOnlyAsInTheStatisticsPage(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 1, 'downloads' => 1]]);
        $plugin->getCounts($this->submission(900001));

        $this->assertContains([\APP\core\Application::ASSOC_TYPE_SUBMISSION_FILE], $plugin->assocTypes);
        foreach ($plugin->assocTypes as $types) {
            $this->assertNotContains(\APP\core\Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER, $types, 'Supplementary files are not downloads of the article.');
        }
        // Counts cached by a release that summed supplementary files are not served.
        $this->assertSame('viewcounter-v2-900001', ViewcounterPlugin::cacheKey(900001));
    }

    public function testCountsThatCannotBeReadShowNoBadgeAndAreLogged(): void
    {
        $plugin = $this->plugin([900003 => ['views' => 5, 'downloads' => 5]]);
        $plugin->fail = true;
        $log = tempnam(sys_get_temp_dir(), 'vc');
        $previous = ini_set('error_log', $log);
        try {
            $this->assertNull($plugin->getCounts($this->submission(900003)));
            $this->assertSame('', $plugin->renderBadge($this->submission(900003), 'summary'), 'No made-up 0 views / 0 downloads.');
            $this->assertSame(1, $plugin->queries, 'A failure is not retried for every badge of the same page.');
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }
        $this->assertStringContainsString('metrics table unavailable', (string) file_get_contents($log));
        unlink($log);
    }

    public function testTheCategoryPageIsPrimedLikeTheIssuePage(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 5, 'downloads' => 1], 900002 => ['views' => 7, 'downloads' => 2]]);
        $request = \APP\core\Application::get()->getRequest();
        if (!$request->getRouter()) {
            $router = new \APP\core\PageRouter();
            $router->setApplication(\APP\core\Application::get());
            $request->setRouter($router);
        }
        $templateMgr = \APP\template\TemplateManager::getManager($request);
        $templateMgr->assign('publishedSubmissions', [$this->submission(900001), $this->submission(900002)]);

        $plugin->handleTemplateDisplay('TemplateManager::display', [$templateMgr, 'frontend/pages/catalogCategory.tpl']);

        $this->assertSame(2, $plugin->queries, 'One grouped query per metric type, before the articles are drawn.');
        $plugin->getCounts($this->submission(900001));
        $plugin->getCounts($this->submission(900002));
        $this->assertSame(2, $plugin->queries, 'No query per article on the category page.');
    }

    public function testAnUnknownPlaceFallsBackToTheSummaryAndTheThemeFunctionDoesNotRelocate(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 1, 'downloads' => 1]]);

        $this->assertStringContainsString('viewcounter-stats--summary', $plugin->renderBadge($this->submission(900001), '"><script>'));
        $this->assertStringNotContainsString('data-vc-place', $plugin->smartyViewcounterStats(['submission' => $this->submission(900001)], null));
        $this->assertSame('', $plugin->smartyViewcounterStats([], null));
    }

    public function testAListIsCountedWithOneQueryPerMetricAndThenServedFromTheCache(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 5, 'downloads' => 1], 900002 => ['views' => 7, 'downloads' => 2]]);
        $plugin->primeCache([$this->submission(900001), $this->submission(900002), 'not a submission']);

        $this->assertSame(2, $plugin->queries, 'One grouped query per metric type.');
        $this->assertSame(['views' => 7, 'downloads' => 2], $plugin->getCounts($this->submission(900002)));
        $this->assertSame(2, $plugin->queries, 'The page must not query again per article.');

        $second = $this->plugin([900001 => ['views' => 99, 'downloads' => 99]]);
        $this->assertSame(['views' => 5, 'downloads' => 1], $second->getCounts($this->submission(900001)), 'The next request reads the cache.');
        $this->assertSame(0, $second->queries);
    }

    public function testASubmissionWithoutMetricsCountsZero(): void
    {
        $this->assertSame(['views' => 0, 'downloads' => 0], $this->plugin()->getCounts($this->submission(900003)));
    }

    public function testTheSiteLevelHasNoSettingsToOpen(): void
    {
        $request = new class () {
            public function getContext()
            {
                return null;
            }

            public function getUserVar($name)
            {
                return $name === 'verb' ? 'settings' : null;
            }

            public function getRouter()
            {
                throw new \RuntimeException('The site level must not build a settings URL.');
            }
        };
        $plugin = new class () extends ViewcounterPlugin {
            public function getEnabled($contextId = null)
            {
                return true;
            }
        };

        $this->assertSame([], array_filter($plugin->getActions($request, []), fn ($action) => $action->getId() === 'settings'));
        $this->expectExceptionMessage('Unhandled management action!');
        $plugin->manage([], $request);
    }

    public function testNoGlobalConstantOnlyDefinedOutsideStrictMode(): void
    {
        // STYLE_SEQUENCE_LAST and friends are only defined globally when PKP_STRICT_MODE is off.
        $this->assertSame(0, preg_match('/(?<![:\w])STYLE_SEQUENCE_\w+/', (string) file_get_contents(dirname(__DIR__) . '/ViewcounterPlugin.php')));
    }
}
