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

use YesWiki\Bazar\Controller\EntryController;
use YesWiki\Core\YesWikiAction;

class __BazarCartoAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $query = $this->getService(EntryController::class)->formatQuery($arg, $_GET);
        if (!isset($query['bf_latitude!'])) {
            $query['bf_latitude!'] = ""; // prevent rendering without bf_latitude
        }
        if (!isset($query['bf_longitude!'])) {
            $query['bf_longitude!'] = ""; // prevent rendering without bf_latitude
        }
        return [
            'query' => $query
        ];
    }

    public function run()
    {
    }
}
