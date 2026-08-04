<?php

namespace DirectMailTeam\DirectMail;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\DBAL\Exception as DBALException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController;

/**
 * Used to generate queries for selecting users in the database
 *
 * @author		Kasper Skårhøj <kasper@typo3.com>
 * @author		Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 */
class DmQueryGenerator extends DatabaseIntegrityController
{
    public $settings;
    protected array $allowedTables = ['tt_address', 'fe_users'];

    #[\Override]
    public function mkTableSelect(string $name, string $cur): string
    {
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . $name . '">';
        $out[] = '<option value=""></option>';

        // An additional recipient table (usually a view) can be configured per page via
        // mod.web_modules.dmail.userTable, so it has to be added to the allowed list here as well.
        $pageId = (int)($GLOBALS['TYPO3_REQUEST']->getQueryParams()['id'] ?? 0);
        $tsParams = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_modules.']['dmail.'] ?? [];
        if ($tsParams['userTable'] ?? false) {
            $addTables = GeneralUtility::trimExplode(',', $tsParams['userTable'], true);
            $this->allowedTables = array_merge($this->allowedTables, $addTables);
        }

        foreach ($GLOBALS['TCA'] as $tN => $value) {
            //if ($this->getBackendUserAuthentication()->check('tables_select', $tN)) {
            if ($this->getBackendUserAuthentication()->check('tables_select', $tN) && in_array($tN, $this->allowedTables)) {
                $label = $this->getLanguageService()->sL($GLOBALS['TCA'][$tN]['ctrl']['title']);
                if ($this->showFieldAndTableNames) {
                    $label .= ' [' . $tN . ']';
                }
                $out[] = '<option value="' . htmlspecialchars((string)$tN) . '"' . ($tN == $cur ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Query marker
     *
     * @param array $allowedTables
     * @param array $set current SET[] parameters of the calling module
     * @param array|string $queryConfig submitted query configuration, array from the form or serialized from the record
     *
     * @return array
     */
    public function queryMakerDM(ServerRequestInterface $request, array $allowedTables = [], array $set = [], array|string $queryConfig = []): array
    {
        if (count($allowedTables)) {
            $this->allowedTables = $allowedTables;
        }

        // The parent controller fills MOD_SETTINGS from its own module data, which never happens
        // when we are rendered inside the dmail module - so seed it from the caller instead.
        if ($set && !$this->MOD_SETTINGS) {
            $this->MOD_SETTINGS = $set;
        }
        if ($queryConfig && !($this->MOD_SETTINGS['queryConfig'] ?? '')) {
            $this->MOD_SETTINGS['queryConfig'] = is_array($queryConfig) ? serialize($queryConfig) : $queryConfig;
        }

        $output = '';
        $selectQueryString = '';
        // Query Maker:
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        if ($this->formName !== '' && $this->formName !== '0') {
            $this->setFormName($this->formName);
        }
        $tmpCode = $this->makeSelectorTable($this->MOD_SETTINGS, $request);
        $output .= '<div id="query"></div><h2>Make query</h2><div>' . $tmpCode . '</div>';
        // Direct mail always wants the full recipient list, the query type selector of the
        // DB check module is not rendered here.
        $mQ = 'all';

        // Make form elements:
        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            // Show query
            $this->enablePrefix = true;
            $queryString = $this->getQuery($this->queryConfig);
            $selectQueryString = $this->stripPidField($this->getSelectQuery($queryString));
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
            $isConnectionMysql = str_starts_with($connection->getServerVersion(), 'MySQL');
            $fullQueryString = '';
            try {
                $fullQueryString = $selectQueryString;
                $dataRows = $connection->executeQuery($selectQueryString)->fetchAllAssociative();
                //$output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                $cPR = $this->getQueryResultCode($mQ, $dataRows, $this->table, $request);
                $output .= '<h2>' . ($cPR['header'] ?? '') . '</h2><div>' . $cPR['content'] . '</div>';
            } catch (DBALException $e) {
                $output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                $out = '<p><strong>Error: <span class="text-danger">'
                    . htmlspecialchars($e->getMessage())
                    . '</span></strong></p>';
                $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
            }
        }
        return ['<div class="database-query-builder">' . $output . '</div>', $selectQueryString];
    }

    public function getQueryDM(bool $queryLimitDisabled, ServerRequestInterface $request, string $table = '', array $mailGroup = []): string
    {
        $selectQueryString = '';
        if (!($this->MOD_SETTINGS['queryTable'] ?? '') && $table) {
            $this->MOD_SETTINGS['queryTable'] = $table;
        }
        if (!($this->MOD_SETTINGS['queryConfig'] ?? '') && ($mailGroup['query'] ?? '')) {
            $this->MOD_SETTINGS['queryConfig'] = $mailGroup['query'];
        }
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        if ($this->formName !== '' && $this->formName !== '0') {
            $this->setFormName($this->formName);
        }
        // Called for its side effects only - it populates extFieldLists and queryConfig.
        $this->makeSelectorTable($this->MOD_SETTINGS, $request);
        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            // Show query
            $this->enablePrefix = true;
            $queryString = $this->getQuery($this->queryConfig);
            if ($queryLimitDisabled) {
                $this->extFieldLists['queryLimit'] = '';
            }
            $selectQueryString = $this->stripPidField($this->getSelectQuery($queryString));
        }
        return $selectQueryString;
    }

    public function setFormName(string $formName): void
    {
        $this->formName = trim($formName);
    }

    /**
     * Initialise the generator for a table the calling module already knows about,
     * used where queryMakerDM() is not the entry point.
     */
    public function initQueryConfig(string $table, array $settings): void
    {
        $this->init('queryConfig', $table, '', $settings);
    }

    /**
     * Custom recipient sources are often database views without a pid column, but the
     * query builder of the DB check module adds `pid` to every select unconditionally.
     */
    protected function stripPidField(string $selectQueryString): string
    {
        return preg_replace('!, `pid`,?\s*!', ' ', $selectQueryString);
    }
}
