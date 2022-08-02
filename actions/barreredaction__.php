<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Security\Controller\SecurityController;
use YesWiki\Core\Service\AclService;
use YesWiki\Zfuture43\Service\FavoritesManager;
use YesWiki\Core\Service\UserManager;

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$params = $this->services->get(ParameterBagInterface::class);
if ($params->get("yeswiki_version") == "doryphore" &&
    preg_match("/^4\.2\.(?:[2-9]|[1-9][0-9])$/", $params->get("yeswiki_release"))){
    $user = $this->services->get(UserManager::class)->getLoggedUser();
    $favoritesManager = $this->services->get(FavoritesManager::class);
    if ((!empty($user) || $this->HasAccess("write")) && $this->method != "revisions") {
        // on récupére la page et ses valeurs associées
        $page = $this->GetParameter('page');
        if (empty($page)) {
            $page = $this->GetPageTag();
            $time = $this->GetPageTime();
            $content = $this->page;
        } else {
            $content = $this->LoadPage($page);
            $time = $content["time"];
        }
    
        // on choisit le template utilisé
        $template = $this->GetParameter('template');
        if (empty($template)) {
            $template = 'barreredaction_basic.twig';
        }
        
        $barreredactionelements['page'] = $page;
        $barreredactionelements['linkpage'] = $this->href('', $page);
    
        // on peut ajouter des classes, la classe par défaut est .footer
        $barreredactionelements['class'] = ($this->GetParameter('class') ? 'footer '.$this->GetParameter('class') : 'footer');
            
        if ($this->HasAccess("write")) {
            // on ajoute le lien d'édition si l'action est autorisée
            if ($this->HasAccess("write", $page) && !$this->services->get(SecurityController::class)->isWikiHibernated()) {
                $barreredactionelements['linkedit'] = $this->href("edit", $page);
            }
    
            if ($time) {
                // hack to hide E_STRICT error if no timezone set
                date_default_timezone_set(@date_default_timezone_get());
                $barreredactionelements['linkrevisions'] = $this->href("revisions", $page);
                $barreredactionelements['time'] = date(_t('TEMPLATE_DATE_FORMAT'), strtotime($time));
            }
    
            // if this page exists
            if ($content) {
                $owner = $this->GetPageOwner($page);
                // message
                if ($this->UserIsOwner($page)) {
                    $barreredactionelements['owner'] = _t('TEMPLATE_OWNER')." : "._t('TEMPLATE_YOU');
                } elseif ($owner) {
                    $barreredactionelements['owner'] = _t('TEMPLATE_OWNER')." : ".$owner;
                } else {
                    $barreredactionelements['owner'] = _t('TEMPLATE_NO_OWNER');
                }
    
                // if current user is owner or admin
                if ($this->UserIsOwner($page) || $this->UserIsAdmin()) {
                    $barreredactionelements['owner'] .= ' - '._t('TEMPLATE_PERMISSIONS');
                    if (!$this->services->get(SecurityController::class)->isWikiHibernated()) {
                        $barreredactionelements['linkacls'] = $this->href("acls", $page);
                        $barreredactionelements['linkdeletepage'] = $this->href("deletepage", $page);
                    }
                    $aclsService = $this->services->get(AclService::class);
                    $hasAccessComment = $aclsService->hasAccess('comment');
                    $barreredactionelements['wikigroups'] = $this->GetGroupsList();
                    if ($this->services->get(ParameterBagInterface::class)->get('comments_activated')) {
                        if ($hasAccessComment && $hasAccessComment !== 'comments-closed') {
                            $barreredactionelements['linkclosecomments'] = $this->href("claim", $page, ['action' => 'closecomments'], false);
                        } else {
                            $barreredactionelements['linkopencomments'] = $this->href("claim", $page, ['action' => 'opencomments'], false);
                        }
                    }
                } elseif (!$owner && $this->GetUser()) {
                    $barreredactionelements['owner'] .= " - "._t('TEMPLATE_CLAIM');
                    if (!$this->services->get(SecurityController::class)->isWikiHibernated()) {
                        $barreredactionelements['linkacls'] = $this->href("claim", $page);
                    }
                }
            }
        }
        $barreredactionelements['linkshare'] = $this->href("share", $page);
    
        $favoritesManager = $this->services->get(FavoritesManager::class);
        if (!empty($user) && $favoritesManager->areFavoritesActivated()) {
            $barreredactionelements['currentuser'] = $user['name'];
            $barreredactionelements['isUserFavorite'] = $favoritesManager->isUserFavorite($user['name'], $page);
        }
        
        $plugin_output_new = $this->render("@templates/$template", $barreredactionelements);
        $plugin_output_new .= ' <!-- /.footer -->'."\n";    
    }
}
