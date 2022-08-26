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

use YesWiki\Core\YesWikiAction;

class DocumentationAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        return [
        ];
    }

    public function run()
    {
        return $this->render('@zfuture43/documentation.twig', [
            'isIframe' => (testUrlInIframe() == 'iframe'),
        ]) ;
    }
}
