<?php
namespace PITS\Deepltranslate\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Ricky Mathew <ricky.mk@pitsolutions.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class SettingsController
 */
class SettingsController extends ActionController
{
    /**
     * pageRenderer
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     * @Inject
     */
    protected $pageRenderer;

    /**
     * @var \PITS\Deepltranslate\Domain\Repository\DeeplSettingsRepository
     * @Inject
     */
    protected $deeplSettingsRepository;

    /**
     * @var \PITS\Deepltranslate\Service\DeeplService
     * @Inject
     */
    protected $deeplService;

    /**
     * Default action
     */
    public function indexAction()
    {
        $args = $this->request->getArguments();
        if (!empty($args) && $args['redirectFrom'] == 'savesetting') {
            $successMessage = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('settings_success', 'Deepl');
            $this->pageRenderer->addJsInlineCode('success', "top.TYPO3.Notification.success('Saved', '" . $successMessage . "');");
        }

        $sysLanguages = $this->deeplSettingsRepository->getSysLanguages();
        $data         = [];
        $preSelect    = [];
        //get existing assignments if any
        $languageAssignments = $this->deeplSettingsRepository->getAssignments();
        if (!empty($languageAssignments) && !empty($languageAssignments[0]['languages_assigned'])) {
            $preSelect = array_filter(unserialize($languageAssignments[0]['languages_assigned']));
        }
        $selectBox = $this->buildTableAssignments($sysLanguages, $preSelect);
        $this->view->assignMultiple(['sysLanguages' => $sysLanguages, 'selectBox' => $selectBox]);
    }

    /**
     * save language assignments
     */
    public function saveSettingsAction()
    {
        $args = $this->request->getArguments();
        if (!empty($args['languages'])) {
            $languages = array_filter($args['languages']);
        }

        $data = [];
        //get existing assignments if any
        $languageAssignments = $this->deeplSettingsRepository->getAssignments($queryBuilder);
        if (!empty($languages)) {
            $data['languages_assigned'] = serialize($languages);
        }
        if (empty($languageAssignments)) {
            $data['crdate']      = time();
            $languageAssignments = $this->deeplSettingsRepository->insertDeeplSettings($data);
        } else {
            $data['uid']    = $languageAssignments[0]['uid'];
            $updateSettings = $this->deeplSettingsRepository->updateDeeplSettings($data);
        }
        $args['redirectFrom'] = 'savesetting';
        $this->redirect('index', 'Settings', 'Deepl', $args);
    }

    /**
     * return an array of options for multiple selectbox
     * @param array $sysLanguages
     * @param array $preselectedValues
     * @return array
     */
    public function buildTableAssignments($sysLanguages, $preselectedValues)
    {
        $table        = [];
        $selectedKeys = array_keys($preselectedValues);
        foreach ($sysLanguages as $sysLanguage) {
            $syslangIso = $sysLanguage['language_isocode'];
            $option     = [];
            $option     = $sysLanguage;
            if (in_array($sysLanguage['uid'], $selectedKeys) || in_array(strtoupper($sysLanguage['language_isocode']), $this->deeplService->apiSupportedLanguages)) {
                $option['value'] = $preselectedValues[$sysLanguage['uid']] ? $preselectedValues[$sysLanguage['uid']] : strtoupper($syslangIso);
            }
            $table[] = $option;
        }
        return $table;
    }
}
