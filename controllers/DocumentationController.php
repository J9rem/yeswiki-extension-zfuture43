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

use YesWiki\Core\YesWikiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentationController extends YesWikiController
{
    /**
     * @Route("/doc",options={"acl":{"public"}})
     */
    public function show()
    {
        return new Response($this->render('@core/documentation.twig', [
          'config' => $this->wiki->config,
          'i18n' => $GLOBALS['translations_js'],
          'locale' => $GLOBALS['prefered_language'],
          'extensions' => $this->getExtensionsWithDocs()
        ]));
    }

    private function getExtensionsWithDocs(): array
    {
        $extensions = [];
        foreach ($this->wiki->extensions as $extName => $extPath) {
            $localizedPath = "{$extPath}docs/{$GLOBALS['prefered_language']}/README.md";
            $path = "{$extPath}docs/README.md";
            $docPath = glob($localizedPath)[0] ?? glob($path)[0] ?? null;
            if ($docPath) {
                $extensions[] = ["name" => $extName, "docPath" => $docPath];
            }
        }
        return $extensions;
    }
}
