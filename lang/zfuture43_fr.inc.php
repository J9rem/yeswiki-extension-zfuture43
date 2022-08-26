<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'AB_MYFAVORITES_LABEL' => 'Mes favoris',
    'AB_MYFAVORITES_TEMPLATE_LABEL' => 'Template',
    'AB_MYFAVORITES_TEMPLATE_LINKS' => 'Liens',
    'AB_MYFAVORITES_TEMPLATE_LINKS_WITH_TITLES' => 'Liens avec titres',
    'AB_MYFAVORITES_TEMPLATE_TILES' => 'Vignettes',
    'AB_MYFAVORITES_TEMPLATE_TABLE' => 'Tableau',
    'TRIPLES' => 'Triples',

    // javascripts/favorites.js
    'FAVORITES_ADD' => 'Ajouter aux favoris',
    'FAVORITES_REMOVE' => 'Retirer des favoris',

    // templates/actions/my-favorites.twig
    'FAVORITES_DELETE_ALL' => 'Supprimer tous mes favoris',
    'FAVORITES_MY_FAVORITES' => 'Mes favoris',
    'FAVORITES_NO_FAVORITE' => 'Aucun favori n\'a été enregistré',
    'FAVORITES_NOT_ACTIVATED' => 'L\'usage des favoris n\'est pas activé sur ce site.',
    'FAVORITES_NOT_CONNECTED' => 'L\'usage des favoris n\'est possible que pour les personnes connectées.',

    // templates/actions/my-favorites-table.twig
    'FAVORITES_TITLE' => 'Titre',
    'FAVORITES_LINK' => 'Lien',
    
    'EDIT_CONFIG_HINT_FAVORITES_ACTIVATED' => 'Activer les favoris (true ou false)',

    'TEMPLATE_NO_OWNER' => 'Pas de propriétaire',
    
    
    "AB_bazarcarto_entrydisplay_option_popup" => "Dans une petite popup (sur la carte)",
    "AB_bazarcarto_popuptemplate_label" => "Template pour la petite popup",
    "AB_bazarcarto_popuptemplate_entry_from_html" => "Rendu côté serveur",
    "AB_bazarcarto_popuptemplate_entry_from_data" => "Rendu local",
    "AB_bazarcarto_popuptemplate_custom" => "Template personnalisé",
    "AB_bazarliste_popupselectedfields_label" => "Champs à conserver dans la popup",
    "AB_bazarliste_popupneededfields_label" => "Champs à ajouter dans la popup",
    "AB_bazarcarto_popupcustomtemplate_label" => "Template personnalisé pour la petite popup",
    "AB_bazarcarto_popupcustomtemplate_hint" => "ex. 'custom_map_popup.twig' à placer dans 'custom/templates/bazar/entries/index-dynamic-templates/'",

    
    'USER_YOU_MUST_SPECIFY_A_POSITIVE_INTEGER_FOR' => 'Il faut une valeur entier positif pour %{name}.',
    'USER_YOU_MUST_SPECIFY_YES_OR_NO' => 'Il faut une value \'Y\' ou  \'N\' pour %{name}.',
    'USER_YOU_MUST_SPECIFY_A_STRING' => 'Il faut une chaîne de caractères pour %{name}.',

    'USERSTABLE_CREATE_USER' => 'Créer un utilisateur',
    'USERSTABLE_CREATE_USER_HINT' => 'Mot de passe généré aléatoirement',
    
    'TEMPLATE_NO_OWNER' => 'Pas de propriétaire',
    
    'USERSETTINGS_CAPTCHA_USER_CREATION' => 'Vérification pour créer un utilisateur',
    'USERSETTINGS_SIGNUP_MISSING_INPUT' => 'Les paramètres \'{parameters}\' ne peuvent être vides !',
    'USERSETTINGS_NAME_ALREADY_USED' => 'L\'identifiant "{currentName}" existe déjà !',
    'USERSETTINGS_EMAIL_ALREADY_USED' => 'L\'email "{email}" est déjà utilisé par un autre compte !',
    'USERSETTINGS_CHANGE_PWD_IN_IFRAME' => "Vous vous apprêtez à changer votre mot de passe dans une fenêtre de type iframe.\n".
        "Pour éviter les attaques par enregistrement de vos touches, assurez-vous que l'url du site commence bien par {baseUrl}.\n".
        "Au moindre doute, ouvrez ce formulaire dans une page dédiée en cliquant sur ce lien {link}.",

    
    "AB_advanced_action_login_label" => "Connexion",
    "AB_advanced_action_login_signupurl_label" => "Url d'inscription",
    "AB_advanced_action_login_signupurl_hint" => "Page du wiki ou url ou '0' pour masquer le bouton d'inscription",
    "AB_advanced_action_login_profileurl_label" => "Url du profil",
    "AB_advanced_action_login_profileurl_hint" => "Page du wiki ou url ou 'WikiName' pour le lien d'édition vers la page de l'utilisateur",
    "AB_advanced_action_login_incomingurl_label" => "Url de retour après connexion (réussie ou non)",
    "AB_advanced_action_login_userpage_label" => "Se rendre sur la page de l'utilisateur une fois connecté",
    "AB_advanced_action_login_lostpasswordurl_label" => "Url pour les mots de passe perdus",
    "AB_advanced_action_login_lostpasswordurl_hint" => "Page du wiki ou url",
    "AB_advanced_action_login_class_label" => "Classe CSS pour le bloc",
    "AB_advanced_action_login_btnclass_label" => "Classe CSS pour les boutons",
    "AB_advanced_action_login_nobtn_label" => "Remplacer le bouton par un lien (modal uniquement)",
    "AB_advanced_action_login_template_label" => "Template",
    "AB_advanced_action_login_template_default" => "Standard",
    "AB_advanced_action_login_template_modal" => "Modal",
    "AB_advanced_action_login_template_horizontal" => "Horizontal",
    "AB_advanced_action_login_template_dropdown" => "Menu déroulant",
    "AB_advanced_action_login_loggedinurl_label" => "Url de redirection après connexion réussie",
    "AB_advanced_action_login_loggedinurl_hint" => "Page du wiki ou url (utilise 'incomingurl' si vide ou en cas d'erreur)",
    "AB_advanced_action_login_loggedouturl_label" => "Url de redirection après déconnexion",
    "AB_advanced_action_login_loggedouturl_hint" => "Page du wiki ou url (utilise 'incomingurl' si vide)",
    
];
