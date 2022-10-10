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

use YesWiki\Aceditor\Service\ActionsBuilderService as AceditorActionsBuilderService;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Wiki;

trait ActionsBuilderServiceCommon
{
    protected $previousData;
    protected $data;
    protected $parentActionsBuilderService;
    protected $renderer;
    protected $wiki;

    public function __construct(TemplateEngine $renderer, Wiki $wiki, $parentActionsBuilderService)
    {
        $this->data = null;
        $this->previousData = null;
        $this->parentActionsBuilderService = $parentActionsBuilderService;
        $this->renderer = $renderer;
        $this->wiki = $wiki;
    }

    public function setPreviousData(?array $data)
    {
        if (is_null($this->previousData)) {
            $this->previousData = is_array($data) ? $data : [];
            if ($this->parentActionsBuilderService && method_exists($this->parentActionsBuilderService, 'setPreviousData')) {
                $this->parentActionsBuilderService->setPreviousData($data);
            }
        }
    }

    // ---------------------
    // Data for the template
    // ---------------------
    public function getData()
    {
        if (is_null($this->data)) {
            if (!empty($this->parentActionsBuilderService)) {
                $this->data = $this->parentActionsBuilderService->getData();
            } else {
                $this->data = $this->previousData;
            }

            if (isset($this->data['action_groups']['reactions']['actions'])) {
                $this->data['action_groups']['reactions']['actions']['myfavorites'] = [
                    'label' => _t('AB_MYFAVORITES_LABEL'),
                    'properties' => [
                        'template' => [
                            'label' => _t('AB_MYFAVORITES_TEMPLATE_LABEL'),
                            'type' => 'list',
                            'default' => 'my-favorites.twig',
                            'options' => [
                                'my-favorites.twig' => _t('AB_MYFAVORITES_TEMPLATE_LINKS'),
                                'my-favorites-with-titles.twig' => _t('AB_MYFAVORITES_TEMPLATE_LINKS_WITH_TITLES'),
                                'my-favorites-tiles.twig' => _t('AB_MYFAVORITES_TEMPLATE_TILES'),
                                'my-favorites-table.twig' => _t('AB_MYFAVORITES_TEMPLATE_TABLE'),
                            ]
                        ]
                    ]
                ];
            }

            if (isset($this->data['action_groups']['advanced-actions']['actions'])) {
                $this->data['action_groups']['advanced-actions']['actions']['login'] = [
                    'label' => _t('AB_advanced_action_login_label'),
                    'properties' => [
                        'template' => [
                            'label' => _t('AB_advanced_action_login_template_label'),
                            'type' => 'list',
                            'default' => 'default.twig',
                            'options' => [
                                'default.twig' => _t('AB_advanced_action_login_template_default'),
                                'modal.twig' => _t('AB_advanced_action_login_template_modal'),
                                'horizontal.twig' => _t('AB_advanced_action_login_template_horizontal'),
                                'dropdown.twig' => _t('AB_advanced_action_login_template_dropdown'),
                            ]
                        ],
                        'signupurl' => [
                            'label' => _t('AB_advanced_action_login_signupurl_label'),
                            'hint' => _t('AB_advanced_action_login_signupurl_hint'),
                            'type' => 'page-list',
                            'default' => '',
                        ],
                        'incomingurl' => [
                            'label' => _t('AB_advanced_action_login_incomingurl_label'),
                            'type' => 'page-list',
                            'default' => '',
                        ],
                        'loggedinurl' => [
                            'label' => _t('AB_advanced_action_login_loggedinurl_label'),
                            'hint' => _t('AB_advanced_action_login_loggedinurl_hint'),
                            'type' => 'page-list',
                            'default' => '',
                            'advanced' => true,
                        ],
                        'loggedouturl' => [
                            'label' => _t('AB_advanced_action_login_loggedouturl_label'),
                            'hint' => _t('AB_advanced_action_login_loggedouturl_hint'),
                            'type' => 'page-list',
                            'default' => '',
                            'advanced' => true,
                        ],
                        'userpage' => [
                            'label' => _t('AB_advanced_action_login_userpage_label'),
                            'type' => 'checkbox',
                            'default' => '',
                            'checkedvalue' => "user",
                            'uncheckedvalue' => "",
                        ],
                        'lostpasswordurl' => [
                            'label' => _t('AB_advanced_action_login_lostpasswordurl_label'),
                            'hint' => _t('AB_advanced_action_login_lostpasswordurl_hint'),
                            'type' => 'page-list',
                            'default' => '',
                        ],
                        'profileurl' => [
                            'label' => _t('AB_advanced_action_login_profileurl_label'),
                            'hint' => _t('AB_advanced_action_login_profileurl_hint'),
                            'type' => 'page-list',
                            'default' => '',
                            'advanced' => true,
                        ],
                        'class' => [
                            'label' => _t('AB_advanced_action_login_class_label'),
                            'type' => 'text',
                            'advanced' => true,
                        ],
                        'btnclass' => [
                            'label' => _t('AB_advanced_action_login_btnclass_label'),
                            'type' => 'text',
                            'default' => '',
                            'advanced' => true,
                        ],
                        'nobtn' => [
                            'label' => _t('AB_advanced_action_login_nobtn_label'),
                            'type' => 'checkbox',
                            'default' => 'false',
                            'checkedvalue' => "true",
                            'uncheckedvalue' => "false",
                            'showif' => [
                                "template" => "modal\.(?:twig|tpl\.html)"
                            ],
                        ]
                    ]
                ];
            }



            if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties'])) {
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['entrydisplay']['options']) &&
                    !isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['entrydisplay']['options']['popup'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['entrydisplay']['options']['popup'] = _t('AB_bazarcarto_entrydisplay_option_popup');
                }
                if (!isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popuptemplate'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popuptemplate'] =
                    [
                        'label' => _t('AB_bazarcarto_popuptemplate_label'),
                        'type' => 'list',
                        'value' => '_map_popup_html.twig',
                        'advanced' => true,
                        'options' => [
                            '_map_popup_html.twig' => _t('AB_bazarcarto_popuptemplate_entry_from_html'),
                            '_map_popup_from_data.twig' => _t('AB_bazarcarto_popuptemplate_entry_from_data'),
                            'custom' => _t('AB_bazarcarto_popuptemplate_custom')
                        ],
                        'showif' => [
                            'dynamic' => true,
                            'entrydisplay' => 'popup'
                        ]
                    ];
                }
                if (!isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popupcustomtemplate'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popupcustomtemplate'] =
                    [
                        'label' => _t('AB_bazarcarto_popupcustomtemplate_label'),
                        'type' => 'text',
                        'value' => 'custom_map_popup.twig',
                        'hint' => _t('AB_bazarcarto_popupcustomtemplate_hint'),
                        'advanced' => true,
                        'showif' => [
                            'dynamic' => true,
                            'entrydisplay' => 'popup',
                            'popuptemplate' => 'custom',
                        ]
                    ];
                }
                if (!isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popupselectedfields'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['popupselectedfields'] =
                    [
                        'label' => _t('AB_bazarliste_popupselectedfields_label'),
                        'type' => 'form-field',
                        'default' => '',
                        'multiple' => true,
                        'advanced' => true,
                        'showif' => [
                            'dynamic' => true,
                            'entrydisplay' => 'popup',
                            'popuptemplate' => '_map_popup_html.twig|custom'
                        ]
                    ];
                }
                if (!isset($this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['necessary_fields'])) {
                    $this->data['action_groups']['bazarliste']['actions']['bazarcarto']['properties']['necessary_fields'] =
                    [
                        'label' => _t('AB_bazarliste_popupneededfields_label'),
                        'type' => 'form-field',
                        'value' => "bf_titre,imagebf_image",
                        'multiple' => true,
                        'advanced' => true,
                        'showif' => [
                            'dynamic' => true,
                            'entrydisplay' => 'popup',
                            'popuptemplate' => '_map_popup_from_data.twig|custom'
                        ]
                    ];
                }
            }
            if (isset($this->data['action_groups']['bazarliste']['actions'])) {
                if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['colorfield'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['colorfield']['extraFields'] = "id_typeannonce";
                    }
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['colormapping']['subproperties']['id'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['colormapping']['subproperties']['id']['extraFields'] = "id_typeannonce";
                    }
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['iconfield'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['iconfield']['extraFields'] = "id_typeannonce";
                    }
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['iconmapping']['subproperties']['id'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['iconmapping']['subproperties']['id']['extraFields'] = "id_typeannonce";
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['commons2']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons2']['properties']['facettes']['subproperties']['field'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons2']['properties']['facettes']['subproperties']['field']['extraFields'] = "id_typeannonce";
                    }
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons2']['properties']['champ'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons2']['properties']['champ']['extraFields'] =
                        [
                            "id_typeannonce",
                            "date_creation_fiche",
                            "date_maj_fiche"
                        ];
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['subtitle'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['subtitle']['extraFields'] = [
                                "owner",
                                "date_creation_fiche",
                                "date_maj_fiche"
                            ];
                        }
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['visual'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['visual']['extraFields'] = [
                            "owner",
                            "date_creation_fiche",
                            "date_maj_fiche"
                            ];
                        }
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['floating'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarliste']['properties']['displayfields']['subproperties']['floating']['extraFields'] = [
                            "owner",
                            "date_creation_fiche",
                            "date_maj_fiche"
                            ];
                        }
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['subtitle'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['subtitle']['extraFields'] = [
                                "owner"
                            ];
                        }
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['footer'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['footer']['extraFields'] = [
                                "owner"
                            ];
                        }
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['floating'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties']['displayfields']['subproperties']['floating']['extraFields'] = [
                                "owner"
                            ];
                        }
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties']['correspondance']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties']['correspondance']['subproperties']['bf_date_debut_evenement'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties']['correspondance']['subproperties']['bf_date_debut_evenement']['extraFields'] = [
                                "date_creation_fiche",
                                "date_maj_fiche"
                            ];
                        }
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties']['correspondance']['subproperties']['bf_date_fin_evenement'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazaragenda']['properties']['correspondance']['subproperties']['bf_date_fin_evenement']['extraFields'] = [
                                "date_creation_fiche",
                                "date_maj_fiche"
                            ];
                        }
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazartimeline']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazartimeline']['properties']['correspondance']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazartimeline']['properties']['correspondance']['subproperties']['bf_date_debut_evenement'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazartimeline']['properties']['correspondance']['subproperties']['bf_date_debut_evenement']['extraFields'] = [
                                "date_creation_fiche",
                                "date_maj_fiche"
                            ];
                        }
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcarousel']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcarousel']['properties']['correspondance']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarcarousel']['properties']['correspondance']['subproperties']['bf_titre'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarcarousel']['properties']['correspondance']['subproperties']['bf_titre']['extraFields'] = [
                                "owner",
                            ];
                        }
                    }
                }
                if (isset($this->data['action_groups']['bazarliste']['actions']['bazarblog']['properties'])) {
                    if (isset($this->data['action_groups']['bazarliste']['actions']['bazarblog']['properties']['correspondance']['subproperties'])) {
                        if (isset($this->data['action_groups']['bazarliste']['actions']['bazarblog']['properties']['correspondance']['subproperties']['date_creation_fiche'])) {
                            $this->data['action_groups']['bazarliste']['actions']['bazarblog']['properties']['correspondance']['subproperties']['date_creation_fiche']['extraFields'] = [
                                "date_creation_fiche",
                                "date_maj_fiche"
                            ];
                        }
                    }
                }
            }

            // add extra components
            $extraComponents = [];
            $files = [];
            foreach ($this->wiki->extensions as $pluginName => $pluginPath) {
                $files = glob("tools/$pluginName/javascripts/components/actions-builder/*.js");
                foreach ($files as $filePath) {
                    $filename = pathinfo($filePath)['filename'];
                    $extraComponents[$filename] = "../../$pluginName/javascripts/components/actions-builder/$filename.js";
                }
            }
            $files = glob("custom/javascripts/components/actions-builder/*.js");
            foreach ($files as $filePath) {
                $filename = pathinfo($filePath)['filename'];
                $extraComponents[$filename] = "../../../custom/javascripts/components/actions-builder/$filename.js";
            }
            if (!empty($extraComponents)) {
                $this->data['extraComponents'] = $extraComponents;
            }
        }
        return $this->data;
    }
}

if (class_exists(AceditorActionsBuilderService::class, false)) {
    class ActionsBuilderService extends AceditorActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
} else {
    class ActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
}
