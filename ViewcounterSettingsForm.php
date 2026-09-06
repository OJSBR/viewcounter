<?php

/**
 * @file ViewcounterSettingsForm.php
 *
 * Settings form of the View counter plugin (OJSBR).
 *
 * @class ViewcounterSettingsForm
 */

namespace APP\plugins\generic\viewcounter;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class ViewcounterSettingsForm extends Form
{
    public function __construct(private ViewcounterPlugin $plugin, private int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        // Padrão: ambos ativos (quando ainda não foi salvo, getSetting retorna null).
        $this->setData('showInSummary', $this->plugin->getSetting($this->contextId, 'showInSummary') ?? 1);
        $this->setData('showInDetails', $this->plugin->getSetting($this->contextId, 'showInDetails') ?? 1);
        parent::initData();
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData(): void
    {
        $this->readUserVars(['showInSummary', 'showInDetails']);
    }

    /**
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting($this->contextId, 'showInSummary', (bool) $this->getData('showInSummary'), 'bool');
        $this->plugin->updateSetting($this->contextId, 'showInDetails', (bool) $this->getData('showInDetails'), 'bool');

        parent::execute(...$functionArgs);
    }
}
