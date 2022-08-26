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
    'AB_MYFAVORITES_LABEL' => 'My favorites',
    'AB_MYFAVORITES_TEMPLATE_LABEL' => 'Template',
    'AB_MYFAVORITES_TEMPLATE_LINKS' => 'Links',
    'AB_MYFAVORITES_TEMPLATE_LINKS_WITH_TITLES' => 'Links with titles',
    'AB_MYFAVORITES_TEMPLATE_TILES' => 'Tiles',
    'AB_MYFAVORITES_TEMPLATE_TABLE' => 'Table',
    'TRIPLES' => 'Triples',
    
    // javascripts/favorites.js
    'FAVORITES_ADD' => 'Add to favorites',
    'FAVORITES_REMOVE' => 'Remove from favorites',

    // templates/actions/my-favorites.twig
    'FAVORITES_DELETE_ALL' => 'Delete all my favorites',
    'FAVORITES_MY_FAVORITES' => 'My favorites',
    'FAVORITES_NO_FAVORITE' => 'No favorites have been saved',
    'FAVORITES_NOT_ACTIVATED' => 'The use of favorites is not enabled on this site.',
    'FAVORITES_NOT_CONNECTED' => 'The use of favorites is possible only for connected people.',

    // templates/actions/my-favorites-table.twig
    'FAVORITES_TITLE' => 'Title',
    'FAVORITES_LINK' => 'Link',
    
    'EDIT_CONFIG_HINT_FAVORITES_ACTIVATED' => 'Enable favorites (true or false)',

    'USER_ERRORS_FOUND' => 'Found(s) errors(s)',
    'USER_YOU_MUST_SPECIFY_A_POSITIVE_INTEGER_FOR' => 'A positive integer is needed for %{name}.',
    'USER_YOU_MUST_SPECIFY_YES_OR_NO' => '\'Y\' or \'N\' is required for %{name}.',
    'USER_YOU_MUST_SPECIFY_A_STRING' => 'A string is required for %{name}.',

    'EDIT_CONFIG_HINT_TIMEZONE' => 'Time zone of the site (e.g. UCT, Europe/Paris, Europe/London, GMT = use the server time zone,)',
    'EDIT_CONFIG_HINT_ALLOWED_METHODS_IN_IFRAME' => 'Methods allowed to be displayed in iframes (iframe,editiframe,bazariframe,render,all = allow all)',
    'EDIT_CONFIG_HINT_REVISIONSCOUNT' => 'Maximum number of page\'s revisions displayed by the handler `/revisions`.',
    'EDIT_CONFIG_HINT_HTMLPURIFIERACTIVATED' => 'Enable HTML purifier before backup. Be careful, modify the content to backup! (true or false)',
    'EDIT_CONFIG_HINT_FAVORITES_ACTIVATED' => 'Enable favorites (true or false)',
    
    'USERSTABLE_CREATE_USER' => 'Create a user',
    'USERSTABLE_CREATE_USER_HINT' => 'Password randomly generated',

    
    'USERSETTINGS_CAPTCHA_USER_CREATION' => 'Verification to create a user',
    'USERSETTINGS_SIGNUP_MISSING_INPUT' => 'The \'{parameters}\' parameters cannot be empty!',
    'USERSETTINGS_NAME_ALREADY_USED' => 'The identifier "{currentName}" already exists!',
    'USERSETTINGS_EMAIL_ALREADY_USED' => 'The email "{email}" is already used by another account!',
    'USERSETTINGS_CHANGE_PWD_IN_IFRAME' => "You are about to change your password in an iframe window.\n".
        "To avoid keylogging attacks, make sure the site url starts with {baseUrl}.\n".
        "If in doubt, open this form in a dedicated page by clicking on this link {link}.",
];
