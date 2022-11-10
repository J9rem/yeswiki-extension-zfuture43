<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Zfuture43\Field;

use YesWiki\Bazar\Field\AclField as CoreAclField;

/**
 * @Field({"acls"})
 */
class AclField extends CoreAclField
{
    protected function renderInput($entry)
    {
        return "";
    }

    protected function renderStatic($entry)
    {
        return "";
    }
}
