<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace YesWiki\Zfuture43;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;
use YesWiki\Bazar\Field\CalcField;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    protected $config;
    protected $dbService;
    protected $formManager;
    protected $securityController;

    public function run()
    {
        $this->config = $this->getService(ParameterBagInterface::class);
        $this->dbService = $this->getService(DbService::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->securityController = $this->getService(SecurityController::class);
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }
        $revision = $this->config->get('yeswiki_version');
        $release = $this->config->get('yeswiki_release');
        list($major,$minor,$bugFix) = array_merge(explode('.',$release),[1 => "",2 => ""]);

        $output = "";
        if ($revision == "doryphore" && 
                $major == 4 && (
                    empty($minor) || $minor < 3
                )
            ){

            // replace CalcField value by string
            $output .= $this->calcFieldToString();
        }

        // set output
        $this->output = str_replace(
            '<!-- end handler /update -->',
            $output.'<!-- end handler /update -->',
            $this->output
        );
        return null;
    }
    
    private function calcFieldToString(): string
    {
        $output = "ℹ️ CalcField value to string... ";

        // find CalcField in forms
        $forms = $this->formManager->getAll();
        if (!empty($forms)) {
            $fields = [];
            foreach ($forms as $form) {
                $formId = $form['bn_id_nature'];
                if (!empty($form['prepared'])) {
                    foreach ($form['prepared'] as $field) {
                        if ($field instanceof CalcField) {
                            // init array for this form, if needed
                            if (empty($fields[$formId])) {
                                $fields[$formId] = [];
                            }
                            // append propertyName if not already present
                            if (!empty($field->getPropertyName()) && !in_array($field->getPropertyName(), $fields[$formId])) {
                                $fields[$formId][] = $field->getPropertyName();
                            }
                        }
                    }
                }
            }

            if (!empty($fields)) {
                foreach ($fields as $formId => $fieldNames) {
                    if (!empty($fieldNames)) {
                        // prepare SQL to select concerned entries (EntryManager->search does not manage int)
                        $fieldsNamesList = implode('|', $fieldNames);
                        $sql =
                            <<<SQL
                            SELECT DISTINCT * FROM {$this->dbService->prefixTable('pages')}
                            WHERE `comment_on` = ''
                            AND `body` LIKE '%"id_typeannonce":"{$this->dbService->escape(strval($formId))}"%'
                            AND `tag` IN (
                                SELECT DISTINCT `resource` FROM {$this->dbService->prefixTable('triples')}
                                WHERE `value` = "fiche_bazar" AND `property` = "http://outils-reseaux.org/_vocabulary/type"
                                ORDER BY `resource` ASC
                            )
                            AND `body` REGEXP '"($fieldsNamesList)":-?[0-9]'
                            SQL;
                        $results = $this->dbService->loadAll($sql);
                        if (!empty($results)) {
                            foreach ($results as $page) {
                                if (preg_match_all("/\"($fieldsNamesList)\":(-?[0-9\.]*),/", $page['body'], $matches)) {
                                    foreach ($matches[0] as $index => $match) {
                                        $fieldName = $matches[1][$index];
                                        $oldValue = $matches[2][$index];
                                        $newValue = strval($oldValue);
                                        $replaceSQL =
                                        <<<SQL
                                        UPDATE {$this->dbService->prefixTable('pages')} 
                                        SET `body` = replace(`body`,'"$fieldName":$oldValue,','"$fieldName":"$newValue",')
                                        WHERE `id` = '{$this->dbService->escape($page['id'])}'
                                        SQL;
                                        // replace
                                        $this->dbService->query($replaceSQL);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $output .= '✅ Done !<br />';

        return $output;
    }
}
