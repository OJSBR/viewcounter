<?php

/**
 * @file ViewcounterPlugin.php
 *
 * Plugin "View counter" — reescrito e adaptado para OJS 3.4 pela OJSBR.
 *
 * Exibe a quantidade de visualizações (abstract) e de downloads (soma das galés)
 * dos artigos, no resumo (listas) e na página de metadados do artigo. O local de
 * exibição é configurável (sumário e/ou página de detalhes).
 *
 * Plugin original (OJS 3.3) por STI FFLCH e ABCD/USP.
 *
 * @class ViewcounterPlugin
 */

namespace APP\plugins\generic\viewcounter;

use APP\core\Application;
use APP\notification\NotificationManager;
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
            // O override por template respeita as configurações (sumário/detalhes).
            Hook::add('TemplateResource::getFilename', [$this, 'overrideTemplates']);
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
     * Verifica se um local de exibição está habilitado. Padrão: habilitado
     * (enquanto a configuração nunca tiver sido salva).
     */
    private function isFeatureEnabled(string $key): bool
    {
        $context = Application::get()->getRequest()->getContext();
        $contextId = $context ? $context->getId() : PKPApplication::SITE_CONTEXT_ID;
        $value = $this->getSetting($contextId, $key);
        return $value === null ? true : (bool) $value;
    }
}
