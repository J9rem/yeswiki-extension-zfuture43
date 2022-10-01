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

use YesWiki\Core\YesWikiHandler;
use YesWiki\Zfuture43\Controller\DocumentationController;

class DocHandler extends YesWikiHandler
{
    public function run()
    {
        return $this->getService(DocumentationController::class)->show();
    }
}
