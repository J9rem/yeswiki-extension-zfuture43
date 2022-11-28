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
        list($major, $minor, $bugFix) = array_merge(explode('.', $release), [1 => "",2 => ""]);

        $output = "";
        if ($revision == "doryphore" &&
                $major == 4 && (
                    empty($minor) || $minor < 3
                )
        ) {
            // replace CalcField value by string
            $output .= $this->calcFieldToString();
        }

        // check if SQL tables are well defined
        $output .= $this->checkSQLTablesThenFixThem($this->dbService);

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



    /**
     * @param DbService $dbService
     * @return string $output
     */
    private function checkSQLTablesThenFixThem($dbService): string
    {
        $output = "ℹ️ Checking SQL table structure... ";
        try {
            foreach ([
                ['pages','id','int(10) unsigned NOT NULL AUTO_INCREMENT'],
                ['links','id','int(10) unsigned NOT NULL AUTO_INCREMENT'],
                ['nature','bn_id_nature','int(10) UNSIGNED NOT NULL AUTO_INCREMENT'],
                ['referrers','id','int(10) unsigned NOT NULL AUTO_INCREMENT'],
                ['triples','id','int(10) unsigned NOT NULL AUTO_INCREMENT'],
            ] as $data) {
                $output .= $this->checkThenUpdateColumnAutoincrement($dbService, $data[0], $data[1], $data[2]);
            }
            foreach ([
                ['pages','id',['id']],
                ['links','id',['id']],
                ['nature','bn_id_nature',['bn_id_nature']],
                ['referrers','id',['id']],
                ['triples','id',['id']],
                ['users','name',['name']],
                ['acls','page_tag',['page_tag','privilege']],
                ['acls','privilege',['page_tag','privilege']],
            ] as $data) {
                $output .= $this->checkThenUpdateColumnPrimary($dbService, $data[0], $data[1], $data[2]);
            }
            $output .= "✅ All is right !<br/>";
        } catch (\Throwable $th) {
            if ($th->getCode() ===1) {
                $output .= "{$th->getMessage()} <br/>";
            } else {
                $output .= "❌ Not checked because of error during tests : {$th->getMessage()} (file : '{$th->getFile()}' - line : ('{$th->getLine()}')! <br/>";
            }
        }

        return $output;
    }

    /**
     * check if a column has auto_increment then try to update it
     * @param $dbService
     * @param string $tableName
     * @param string $columnName
     * @param string $SQL_columnDef
     * @return string $output
     * @throws \Exception
     */
    private function checkThenUpdateColumnAutoincrement($dbService, string $tableName, string $columnName, string $SQL_columnDef): string
    {
        $output = "";
        try {
            $data = $this->getColumnInfo($dbService, $tableName, $columnName);
        } catch (Exception $ex) {
            if ($ex->getCode() != 1) {
                throw $ex;
            }
            $data = [];
        }
        if (empty($data['Extra']) || (is_string($data['Extra']) && strstr($data['Extra'], 'auto_increment') === false)) {
            $output .= "<br/>  Updating `$columnName` in `$tableName`... ";
            if (empty($data)) {
                $dataIndex = $this->getColumnInfo($dbService, $tableName, 'index');
                if (!empty(array_filter($dataIndex, function ($keyData) {
                    return !empty($keyData['Key_name']) && $keyData['Key_name'] == 'PRIMARY';
                }))) {
                    $dbService->query("ALTER TABLE {$dbService->prefixTable($tableName)} DROP PRIMARY KEY;");
                }
                $dbService->query("ALTER TABLE {$dbService->prefixTable($tableName)} ADD COLUMN `$columnName` $SQL_columnDef FIRST, ADD PRIMARY KEY(`$columnName`);");
            }
            $dbService->query("ALTER TABLE {$dbService->prefixTable($tableName)} MODIFY COLUMN `$columnName` $SQL_columnDef;");
            $data = $this->getColumnInfo($dbService, $tableName, $columnName);
            if (empty($data['Extra']) || (is_string($data['Extra']) && strstr($data['Extra'], 'auto_increment') === false)) {
                throw new \Exception("❌ tables `$tableName`, column `$columnName` not updated !", 1);
            }
        }
        return $output;
    }

    /**
     * check if a column is primary then try to update it
     * @param $dbService
     * @param string $tableName
     * @param string $columnName
     * @param array $newKeys
     * @return string $output
     * @throws \Exception
     */
    private function checkThenUpdateColumnPrimary($dbService, string $tableName, string $columnName, array $newKeys): string
    {
        $output = "";
        $data = $this->getColumnInfo($dbService, $tableName, $columnName);
        if (empty($data['Key']) || $data['Key'] !== "PRI") {
            $output .= "<br/>  Updating key for `$columnName` in `$tableName`... ";
            $newKeysFormatted = implode(
                ',',
                array_map(
                    function ($key) use ($dbService) {
                        return "`{$dbService->escape($key)}`";
                    },
                    array_filter($newKeys)
                )
            );
            if (!empty($newKeysFormatted)) {
                $data = $this->getColumnInfo($dbService, $tableName, 'index');
                if (!empty(array_filter($data, function ($keyData) {
                    return !empty($keyData['Key_name']) && $keyData['Key_name'] == 'PRIMARY';
                }))) {
                    $dbService->query("ALTER TABLE {$dbService->prefixTable($tableName)} DROP PRIMARY KEY;");
                }
                $dbService->query("ALTER TABLE {$dbService->prefixTable($tableName)} ADD PRIMARY KEY($newKeysFormatted);");
            }
            $data = $this->getColumnInfo($dbService, $tableName, $columnName);
            if (empty($data['Key']) || $data['Key'] !== "PRI") {
                throw new \Exception("❌ tables `$tableName`, column `$columnName` key not updated !", 1);
            }
        }
        return $output;
    }

    /**
     * get columnInfo from a table
     * @param DbService $dbService
     * @param string $tableName
     * @param string $columnName
     * @return array $data
     * @throws \Exception
     */
    private function getColumnInfo($dbService, string $tableName, string $columnName): array
    {
        if ($columnName == 'index') {
            $result = $dbService->query("SHOW INDEX FROM {$dbService->prefixTable($tableName)};");
            if (@mysqli_num_rows($result) === 0) {
                return [];
            }
        } else {
            $result = $dbService->query("SHOW COLUMNS FROM {$dbService->prefixTable($tableName)} LIKE '$columnName';");
            if (@mysqli_num_rows($result) === 0) {
                throw new \Exception("❌ tables `$tableName` not verified because error while getting `$columnName` column !", 1);
            }
        }
        $data = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return $data;
    }
}
