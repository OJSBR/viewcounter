<?php

/**
 * @file ViewcounterPlugin.php
 *
 * Plugin "View counter" — reescrito e adaptado para OJS 3.5 pela OJSBR.
 *
 * Exibe a quantidade de visualizações (abstract) e de downloads (soma das galés)
 * dos artigos, no resumo (listas) e na página de metadados do artigo. O local de
 * exibição é configurável (sumário e/ou página de detalhes).
 *
 * Diferenças para a versão 3.4: no 3.5 os métodos Submission::getViews() e
 * Galley::getViews() foram removidos; as contagens são calculadas aqui via o
 * serviço de estatísticas (publicationStats) e expostas ao Smarty pela função
 * {viewcounterStats submission=$article}.
 *
 * Plugin original (OJS 3.3) por STI FFLCH e ABCD/USP.
 *
 * @class ViewcounterPlugin
 */

namespace APP\plugins\generic\viewcounter;

use APP\core\Application;
use APP\core\Services;
use APP\notification\NotificationManager;
use APP\statistics\StatisticsHelper;
use PKP\core\Core;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class ViewcounterPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            // Sobrescreve os templates (respeitando as configurações de local).
            Hook::add('TemplateResource::getFilename', [$this, 'overrideTemplates']);
            // Registra a função Smarty {viewcounterStats} usada pelos templates.
            Hook::add('TemplateManager::display', [$this, 'registerSmartyFunction']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.viewcounter.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.viewcounter.description');
    }

    /**
     * Adiciona o link "Configurações" na lista de plugins.
     *
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb): array
    {
        $actions = parent::getActions($request, $verb);
        if (!$this->getEnabled()) {
            return $actions;
        }
        $router = $request->getRouter();
        $url = $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']);
        array_unshift($actions, new LinkAction('settings', new AjaxModal($url, $this->getDisplayName()), __('manager.plugins.settings')));
        return $actions;
    }

    /**
     * Exibe/salva o formulário de configurações.
     *
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') !== 'settings') {
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

    /**
     * Hook TemplateResource::getFilename — substitui o template apenas quando o
     * local correspondente estiver habilitado nas configurações.
     */
    public function overrideTemplates($hookName, $args)
    {
        $template = preg_replace('#^templates/#', '', $args[1] ?? '');

        $map = [
            'frontend/objects/article_details.tpl' => 'showInDetails',
            'frontend/objects/article_summary.tpl' => 'showInSummary',
        ];

        if (isset($map[$template]) && $this->isFeatureEnabled($map[$template])) {
            $args[0] = Core::getBaseDir() . '/' . $this->getPluginPath() . '/templates/' . $template;
        }

        return false;
    }

    /**
     * Hook TemplateManager::display — registra a função Smarty {viewcounterStats}.
     */
    public function registerSmartyFunction($hookName, $args)
    {
        $templateMgr = $args[0];
        try {
            $templateMgr->registerPlugin('function', 'viewcounterStats', [$this, 'smartyViewcounterStats']);
        } catch (\Throwable $e) {
            // já registrada nesta requisição; ignora.
        }
        return false;
    }

    /**
     * Função Smarty: {viewcounterStats submission=$article fontSize="14px"}
     * Devolve o bloco compacto (visualizações | downloads) com ícones e tooltips.
     */
    public function smartyViewcounterStats($params, $smarty)
    {
        $submission = $params['submission'] ?? null;
        if (!$submission) {
            return '';
        }
        $fontSize = $params['fontSize'] ?? '14px';

        $views = $this->countSubmissionViews($submission);
        $downloads = $this->countSubmissionDownloads($submission);

        $viewsLabel = htmlspecialchars(__('plugins.generic.viewcounter.views'), ENT_QUOTES);
        $downloadsLabel = htmlspecialchars(__('plugins.generic.viewcounter.downloads'), ENT_QUOTES);

        $eye = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        $down = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

        $itemStyle = 'display:inline-flex;align-items:center;gap:5px;cursor:default;';

        return '<span class="viewcounter-stats" style="flex:0 0 auto;display:inline-flex;gap:14px;align-items:center;color:#6b7280;font-weight:normal;line-height:1.6;white-space:nowrap;font-size:' . htmlspecialchars($fontSize, ENT_QUOTES) . ';">'
            . '<span title="' . $viewsLabel . '" aria-label="' . $viewsLabel . ': ' . $views . '" style="' . $itemStyle . '">' . $eye . ' ' . $views . '</span>'
            . '<span title="' . $downloadsLabel . '" aria-label="' . $downloadsLabel . ': ' . $downloads . '" style="' . $itemStyle . '">' . $down . ' ' . $downloads . '</span>'
            . '</span>';
    }

    /**
     * Total de visualizações (abstract) da submissão. Tolerante a falhas.
     */
    private function countSubmissionViews($submission): int
    {
        try {
            $filters = [
                'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
                'dateEnd' => date('Y-m-d', strtotime('yesterday')),
                'contextIds' => [$submission->getData('contextId')],
                'submissionIds' => [$submission->getId()],
                'assocTypes' => [Application::ASSOC_TYPE_SUBMISSION],
            ];
            $metric = Services::get('publicationStats')->getQueryBuilder($filters)->getSum([])->value('metric');
            return (int) ($metric ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Total de downloads (soma das galés) da submissão. Tolerante a falhas.
     */
    private function countSubmissionDownloads($submission): int
    {
        try {
            // Soma todos os downloads de arquivos (galés) da submissão. As métricas
            // de arquivo no OJS 3.5 usam os assocTypes SUBMISSION_FILE (+ COUNTER_OTHER).
            $assocTypes = [Application::ASSOC_TYPE_SUBMISSION_FILE];
            if (defined(Application::class . '::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER')) {
                $assocTypes[] = Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER;
            }
            $filters = [
                'dateStart' => StatisticsHelper::STATISTICS_EARLIEST_DATE,
                'dateEnd' => date('Y-m-d', strtotime('yesterday')),
                'contextIds' => [$submission->getData('contextId')],
                'submissionIds' => [$submission->getId()],
                'assocTypes' => $assocTypes,
            ];
            $metric = Services::get('publicationStats')->getQueryBuilder($filters)->getSum([])->value('metric');
            return (int) ($metric ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Verifica se um local de exibição está habilitado. Padrão: habilitado.
     */
    private function isFeatureEnabled(string $key): bool
    {
        $context = Application::get()->getRequest()->getContext();
        $contextId = $context ? $context->getId() : PKPApplication::SITE_CONTEXT_ID;
        $value = $this->getSetting($contextId, $key);
        return $value === null ? true : (bool) $value;
    }
}
