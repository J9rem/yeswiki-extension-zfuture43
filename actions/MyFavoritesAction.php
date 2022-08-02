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

use attach;
use YesWiki\Bazar\Field\ImageField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiAction;
use YesWiki\Zfuture43\Service\FavoritesManager;

class MyFavoritesAction extends YesWikiAction
{
    protected $attach;
    protected $entryManager;
    protected $favoritesManager;
    protected $formManager;
    protected $pageManager;
    protected $templateEngine;
    protected $userManager;

    public function formatArguments($arg)
    {
        return [
            'template' => !empty($arg['template']) ? basename($arg['template']) : "" ,
            'isuserfavorite' => $this->formatBoolean($arg, false, 'isuserfavorite'),
            'entryid' => $arg['entryid'] ?? '',
        ];
    }

    public function run()
    {
        // get Services
        $this->entryManager  = $this->getService(EntryManager::class);
        $this->favoritesManager  = $this->getService(FavoritesManager::class);
        $this->formManager  = $this->getService(FormManager::class);
        $this->pageManager  = $this->getService(PageManager::class);
        $this->templateEngine  = $this->getService(TemplateEngine::class);
        $this->userManager  = $this->getService(UserManager::class);

        if ($this->arguments['isuserfavorite']) {
            $user = $this->userManager->getLoggedUser();
            if (!empty($user) && $this->favoritesManager->areFavoritesActivated()) {
                $currentuser = $user['name'];
                $isUserFavorite = $this->favoritesManager->isUserFavorite($currentuser, $this->arguments['entryid']);
                return $this->render('@zfuture43/for-entries.twig', [
                    "currentuser" => $currentuser ?? null,
                    "isUserFavorite" => $isUserFavorite ?? false,
                    "entryId" => $this->arguments['entryid'],
                ]);
            }
        }

        if (!class_exists('attach')) {
            include 'tools/attach/libs/attach.lib.php';
        }
        $this->attach  = new attach($this->wiki);

        $user = $this->userManager->getLoggedUser();
        $currentUser = empty($user) ? null : $user['name'];

        $favorites = empty($currentUser) ? [] : $this->favoritesManager->getUserFavorites($currentUser) ;

        $template = (empty($this->arguments['template']) || !$this->templateEngine->hasTemplate("@zfuture43/actions/{$this->arguments['template']}"))
            ? '@zfuture43/actions/my-favorites.twig'
            : "@zfuture43/actions/{$this->arguments['template']}";

        $this->updateFavoritesWithTitleImagesAndEntries($favorites);

        return $this->render($template, [
            'areFavoritesActivated' => $this->favoritesManager->areFavoritesActivated(),
            'currentUser' => $currentUser,
            'favorites' => $favorites,
        ]) ;
    }

    private function updateFavoritesWithTitleImagesAndEntries(array &$favorites)
    {
        foreach ($favorites as $key => $favorite) {
            if ($this->entryManager->isEntry($favorite['resource'])) {
                $entry = $this->entryManager->getOne($favorite['resource']);
                if (!empty($entry)) {
                    $favorites[$key]['entry'] = $entry;
                    $favorites[$key]['title'] = $entry['bf_titre'] ?? $favorite['resource'];
                    $form = $this->formManager->getOne($entry['id_typeannonce']);
                    if (!empty($form)) {
                        $favorites[$key]['form'] = $form;
                        $imageFields = array_filter($form['prepared'], function ($field) {
                            return $field instanceof ImageField;
                        });
                        if (!empty($imageFields)) {
                            $imageField = $imageFields[array_key_first($imageFields)];
                            if (!empty($entry[$imageField->getPropertyName()])) {
                                $favorites[$key]['image'] = $entry[$imageField->getPropertyName()];
                            }
                        }
                    }
                }
            } else {
                $page = $this->pageManager->getOne($favorite['resource']);
                if (!empty($page)) {
                    include_once 'tools/tags/libs/tags.functions.php';
                    $title = get_title_from_body($page);
                    if (!empty($title)) {
                        $favorites[$key]['title'] = $title;
                    }
                    $image = $this->get_image_from_body($page);
                    if (!empty($image)) {
                        $favorites[$key]['image'] = $image;
                    }
                }
            }
        }
    }

    private function get_image_from_body($page)
    {
        // on cherche les actions attach avec image, puis les images bazar
        preg_match_all("/\{\{attach.*file=\".*\.(?i)(jpg|png|gif|bmp).*\}\}/U", $page['body'], $images);
        if (is_array($images[0]) && isset($images[0][0]) && $images[0][0] != '') {
            preg_match_all("/.*file=\"(.*\.(?i)(jpg|png|gif|bmp))\".*desc=\"(.*)\".*\}\}/U", $images[0][0], $attachimg);
            return $this->getFileName($page, $attachimg[1][0]);
        } else {
            preg_match_all('/"imagebf_image":"(.*)"/U', $page['body'], $image);
            if (is_array($image[1]) && isset($image[1][0]) && $image[1][0] != '') {
                $imagefile = utf8_decode(
                    preg_replace_callback(
                        '/\\\\u([a-f0-9]{4})/',
                        'encodingFromUTF8',
                        $image[1][0]
                    )
                );
                return $imagefile;
            } else {
                preg_match_all("/\[\[(http.*\.(?i)(jpg|png|gif|bmp)) .*\]\]/U", $page['body'], $image);
                if (is_array($image[1]) && isset($image[1][0]) && $image[1][0] != '') {
                    return $image[1][0];
                } else {
                    preg_match_all("/\<img.*src=\"(.*)\"/U", $page['body'], $image);
                    if (is_array($image[1]) && isset($image[1][0]) && $image[1][0] != '') {
                        return $image[1][0];
                    } else {
                        return "";
                    }
                }
            }
        }
        return "";
    }

    private function getFileName($page, $file)
    {
        $oldpagetag = $this->wiki->GetPageTag();
        $oldpage = $this->wiki->page;
        $this->wiki->tag = $page['tag'];
        $this->wiki->page = $page;

        $this->attach->file = $file;
        $fullFileName = $this->attach->GetFullFilename();

        if (substr($fullFileName, 0, strlen("files/")) == "files/") {
            $fullFileName = substr($fullFileName, strlen("files/"));
        }

        $this->wiki->tag = $oldpagetag;
        $this->wiki->page = $oldpage;
        return $fullFileName;
    }
}
