<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Zfuture43\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\SemanticTransformer;
use YesWiki\Bazar\Controller\EntryController as BazarEntryController;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Security\Controller\SecurityController;

class EntryController extends BazarEntryController
{
    private $parentsEntries ;

    public function __construct(
        EntryManager $entryManager,
        FormManager $formManager,
        AclService $aclService,
        SemanticTransformer $semanticTransformer,
        PageManager $pageManager,
        ParameterBagInterface $config,
        SecurityController $securityController,
        UserManager $userManager
    ) {
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->aclService = $aclService;
        $this->semanticTransformer = $semanticTransformer;
        $this->pageManager = $pageManager;
        $this->config = $config->all();
        $this->securityController = $securityController;
        $this->userManager = $userManager;
        $this->parentsEntries = [];
    }
    /**
     * @param string $entryId
     * @param string|null $time choose only the entry's revision corresponding to time, null = latest revision
     * @param bool $showFooter
     * @param string|null $userNameForRendering userName used to render the entry, if empty uses the connected user
     */
    public function view($entryId, $time = '', $showFooter = true, ?string $userNameForRendering = null)
    {
        if (is_array($entryId)) {
            // If entry ID is the full entry with all the values
            $entry = $entryId;
            $entryId = $entry['id_fiche'];
        } elseif ($entryId) {
            $entry = $this->entryManager->getOne($entryId, false, $time, empty($userNameForRendering), false, $userNameForRendering);
            if (!$entry) {
                return '<div class="alert alert-danger">' . _t('BAZ_PAS_DE_FICHE_AVEC_CET_ID') . ' : ' . $entryId . '</div>';
            }
        } else {
            return '<div class="alert alert-danger">' . _t('BAZ_PAS_D_ID_DE_FICHE_INDIQUEE') . '</div>';
        }

        $form = $this->formManager->getOne($entry['id_typeannonce']);

        // fake ->tag for the attached images
        $oldPageTag = $this->wiki->GetPageTag();
        $this->wiki->tag = $entryId;
        $renderedEntry = null;
        $message = $_GET['message'] ?? '';
        // unset $_GET['message'] to prevent infinite loop when rendering entry with textarea and {{bazarliste}}
        unset($_GET['message']);
        // to synchronize with const in BazarAction (but do not include it here otherwise include shunts Performer job)
        $isUpdatingEntry = (isset($_GET['vue']) && $_GET['vue'] === 'consulter');
        if ($isUpdatingEntry) {
            unset($_GET['vue']);
        }
        // unshift stack to check if this entry is included into a bazarliste into a Field
        array_unshift($this->parentsEntries, $entryId);
        if (count(array_filter($this->parentsEntries, function ($value) use ($entryId) {
            return $value === $entryId;
        })) < 3 // max 3 levels
        ) {
            // use a custom template if exists (fiche-FORM_ID.tpl.html or fiche-FORM_ID.twig)
            $customTemplatePath = $this->getCustomTemplatePath($entry);
            if ($customTemplatePath) {
                $customTemplateValues = $this->getValuesForCustomTemplate($entry, $form, $userNameForRendering);
                $renderedEntry = $this->render($customTemplatePath, $customTemplateValues);
            }

            // use a custom semantic template if exists
            if (is_null($renderedEntry) && !empty($customTemplateValues['html']['semantic'])) {
                $customTemplatePath = $this->getCustomSemanticTemplatePath($customTemplateValues['html']['semantic']);
                if ($customTemplatePath) {
                    $renderedEntry = $this->render("@bazar/$customTemplatePath", $customTemplateValues);
                }
            }
            // if not found, use default template
            if (is_null($renderedEntry)) {
                if (!empty($form)) {
                    foreach ($form['prepared'] as $field) {
                        if ($field instanceof BazarField) {
                            // TODO handle html_outside_app mode for images
                            if (!in_array($field->getPropertyName(), $this->fieldsToExclude())) {
                                $renderedEntry .= $field->renderStaticIfPermitted($entry, $userNameForRendering);
                            }
                        }
                    }
                } else {
                    $renderedEntry = $this->render(
                        "@templates/alert-message.twig",
                        [
                            'type' => 'info',
                            'message' => str_replace('{{nb}}', $entry['id_typeannonce'], _t('BAZ_PAS_DE_FORM_AVEC_ID_DE_CETTE_FICHE')),
                        ]
                    );
                }
            }
        }

        // fake ->tag for the attached images
        $this->wiki->tag = $oldPageTag;
        // shift stack
        array_shift($this->parentsEntries);

        // Format owner
        $owner = $this->wiki->GetPageOwner($entryId);
        $isOwnerIpAddress = preg_replace('/([0-9]|\.)/', '', $owner) == '';
        if ($isOwnerIpAddress || !$owner) {
            $owner = _t('BAZ_UNKNOWN_USER');
        }
        if (!empty($this->config['sso_config']) && isset($this->config['sso_config']['bazar_user_entry_id']) && $this->pageManager->getOne($owner)) {
            $owner = $this->wiki->Format('[[' . $this->wiki->GetPageOwner($entryId) . ' ' . $this->wiki->GetPageOwner($entryId) . ']]');
        }

        // remake $_GET['message'] for BazarAction__ like in webhooks extension
        if (!empty($message)) {
            $_GET['message'] = $message;
        }
        if ($isUpdatingEntry) {
            $_GET['vue'] = 'consulter';
        }

        return $this->render('@bazar/entries/view.twig', [
            "form" => $form,
            "entry" => $entry,
            "entryId" => $entryId,
            "owner" => $owner,
            "message" => $message,
            "showFooter" => $showFooter,
            "canShow" => $this->wiki->GetPageTag() != $entry['id_fiche'], // hide if we are already in the show page
            "canEdit" =>  !$this->securityController->isWikiHibernated() && $this->aclService->hasAccess('write', $entryId),
            "canDelete" => !$this->securityController->isWikiHibernated() && ($this->wiki->UserIsAdmin($userNameForRendering) || $this->wiki->UserIsOwner($entryId)),
            "isAdmin" => $this->wiki->UserIsAdmin($userNameForRendering),
            "renderedEntry" => $renderedEntry,
            "incomingUrl" => $_GET['incomingurl'] ?? getAbsoluteUrl()
        ]);
    }

    private function getCustomTemplatePath($entry): ?string
    {
        $templatePaths = [
            "@bazar/fiche-{$entry['id_typeannonce']}.tpl.html",
            "@bazar/fiche-{$entry['id_typeannonce']}.twig"
        ];
        foreach ($templatePaths as $templatePath) {
            if ($this->getService(TemplateEngine::class)->hasTemplate($templatePath)) {
                return $templatePath;
            }
        }
        return null;
    }


    private function getCustomSemanticTemplatePath($semanticData): ?string
    {
        if (empty($semanticData)) {
            return null;
        }

        // Trouve le contexte principal
        if (is_array($semanticData['@context'])) {
            foreach ($semanticData['@context'] as $context) {
                if (is_string($context)) {
                    break;
                }
            }
        } else {
            $context = $semanticData['@context'];
        }

        // Si on a trouvé un contexte et qu'un mapping existe pour ce contexte
        if (isset($context) && $dir_name = $this->config['baz_semantic_types_mapping'][$context]) {
            // Trouve le type principal
            if (is_array($semanticData['@type'])) {
                foreach ($semanticData['@type'] as $type) {
                    if (is_string($type)) {
                        break;
                    }
                }
            } else {
                $type = $semanticData['@type'];
            }

            if (isset($type)) {
                $templatePath = $dir_name . "/" . strtolower($type) . ".tpl.html";
                return $this->getService(TemplateEngine::class)->hasTemplate($templatePath) ? $templatePath : null;
            }
        }

        return null;
    }

    private function fieldsToExclude()
    {
        return isset($_GET['excludeFields']) ? explode(',', $_GET['excludeFields']) : [];
    }

    /**
     * @param array $entry
     * @param array|null $form
     * @param string|null $userNameForRendering userName used to render the entry, if empty uses the connected user
     */
    private function getValuesForCustomTemplate($entry, $form, ?string $userNameForRendering = null)
    {
        $html = [];
        foreach ($form['prepared'] as $field) {
            if ($field instanceof BazarField) {
                $id = $field->getPropertyName();
                if (!empty($id) && !in_array($id, $this->fieldsToExclude())) {
                    $html[$id] = $field->renderStaticIfPermitted($entry, $userNameForRendering);
                    // reset $matches before preg_match
                    $matches = [];
                    if ($id == 'bf_titre') {
                        preg_match('/<h1 class="BAZ_fiche_titre">\s*(.*)\s*<\/h1>.*$/is', $html[$id], $matches);
                    } elseif (!empty($html[$id])) {
                        preg_match('/<span class="BAZ_texte">\s*(.*)\s*<\/span>.*$/is', $html[$id], $matches);
                    }
                    if (isset($matches[1]) && $matches[1] != '') {
                        $html[$id] = $matches[1];
                    }
                }
            }
        }

        if ($form['bn_sem_type']) {
            $html['id_fiche'] = $entry['id_fiche'];
            $html['semantic'] = $GLOBALS['wiki']->services->get(SemanticTransformer::class)->convertToSemanticData($form, $html, true);
        }

        $values['html'] = $html;
        $values['fiche'] = $entry;
        $values['form'] = $form;

        return $values;
    }
}
