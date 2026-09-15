<?php

/**
 * @file plugins/generic/viewcounter/ViewcounterSettingsForm.php
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ViewcounterSettingsForm
 *
 * @brief Where the badge is shown: summary lists, article page, or both.
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
     * Load the current settings of the journal; both places are on until saved.
     */
    public function initData(): void
    {
        $this->setData('showInSummary', $this->plugin->getSetting($this->contextId, 'showInSummary') ?? 1);
        $this->setData('showInDetails', $this->plugin->getSetting($this->contextId, 'showInDetails') ?? 1);
        parent::initData();
    }

    /**
     * Read the submitted settings.
     */
    public function readInputData(): void
    {
        $this->readUserVars(['showInSummary', 'showInDetails']);
    }

    /**
     * Render the form.
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * Save the settings of the journal.
     */
    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting($this->contextId, 'showInSummary', (bool) $this->getData('showInSummary'), 'bool');
        $this->plugin->updateSetting($this->contextId, 'showInDetails', (bool) $this->getData('showInDetails'), 'bool');

        parent::execute(...$functionArgs);
    }
}
