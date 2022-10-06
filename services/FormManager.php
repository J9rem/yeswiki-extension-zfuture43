<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Zfuture43\Service;

use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Field\CheckboxEntryField;
use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Field\RadioEntryField;
use YesWiki\Bazar\Field\SelectEntryField;
use YesWiki\Bazar\Service\FormManager as BazarFormManager;

class FormManager extends BazarFormManager
{
    public function scanAllFacettable($entries, $groups = ['all'], $onlyLists = false)
    {
        if (!preg_match("/^4\.2\.[2-9]/", $this->params->get("yeswiki_release")) || $this->params->get("yeswiki_version") != "doryphore") {
            return parent::scanAllFacettable($entries, $groups, $onlyLists);
        }
        $facetteValue = $fields = [];

        foreach ($entries as $entry) {
            $form = $this->getOne($entry['id_typeannonce']);

            // on filtre pour n'avoir que les liste, checkbox, listefiche ou checkboxfiche
            if (!isset($fields[$entry['id_typeannonce']])) {
                $fields[$entry['id_typeannonce']] = (empty($form['prepared']))
                    ? []
                    : $this->filterFieldsByPropertyName($form['prepared'], $groups);
            }

            foreach ($entry as $key => $value) {
                $facetteasked = (isset($groups[0]) && $groups[0] == 'all') || in_array($key, $groups);

                if (!empty($value) and is_array($fields[$entry['id_typeannonce']]) && $facetteasked) {
                    if (in_array($key, ['id_typeannonce','owner'])) {
                        $fieldPropName = $key;
                        $field = null;
                    } else {
                        $filteredFields = $this->filterFieldsByPropertyName($fields[$entry['id_typeannonce']], [$key]);
                        $field = array_pop($filteredFields);

                        $fieldPropName = null;
                        if ($field instanceof BazarField) {
                            $fieldPropName = $field->getPropertyName();
                            $fieldType = $field->getType();
                        }
                    }

                    if ($fieldPropName) {
                        if ($field instanceof EnumField) {
                            if ($field instanceof SelectEntryField || $field instanceof CheckboxEntryField  || $field instanceof RadioEntryField) {
                                // listefiche ou checkboxfiche
                                $facetteValue[$fieldPropName]['type'] = 'fiche';
                            } else {
                                $facetteValue[$fieldPropName]['type'] = 'liste';
                            }

                            $facetteValue[$fieldPropName]['source'] = $key;

                            $tabval = explode(',', $value);
                            foreach ($tabval as $tval) {
                                if (isset($facetteValue[$fieldPropName][$tval])) {
                                    ++$facetteValue[$fieldPropName][$tval];
                                } else {
                                    $facetteValue[$fieldPropName][$tval] = 1;
                                }
                            }
                        } elseif (!$onlyLists) {
                            // texte
                            $facetteValue[$key]['type'] = 'form';
                            $facetteValue[$key]['source'] = $key;
                            if (isset($facetteValue[$key][$value])) {
                                ++$facetteValue[$key][$value];
                            } else {
                                $facetteValue[$key][$value] = 1;
                            }
                        }
                    }
                }
            }
        }

        // remove `id_typeannonce` if only one form
        if (isset($facetteValue['id_typeannonce'])) {
            $nbForms = count(
                array_filter(
                    array_keys($facetteValue['id_typeannonce']),
                    function ($key) {
                        return !in_array($key, ['type','source']);
                    }
                )
            );
            if ($nbForms < 2) {
                unset($facetteValue['id_typeannonce']);
            }
        }
        return $facetteValue;
    }

    /*
     * Filter an array of fields by their potential entry ID
     */
    private function filterFieldsByPropertyName(array $fields, array $id)
    {
        if (count($id)===1 && $id[0]==='all') {
            return array_filter($fields, function ($field) use ($id) {
                if ($field instanceof EnumField) {
                    return true;
                }
            });
        } else {
            return array_filter($fields, function ($field) use ($id) {
                if ($field instanceof BazarField) {
                    return $id[0] === 'all' || in_array($field->getPropertyName(), $id);
                }
            });
        }
    }
}
