<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Zfuture43\Service;

class Commons
{
    /**
     * furnish a method to generateRandomString
     * @param int $length
     * @param string $charset
     * @return string
     */
    public function generateRandomString(
        int $length = 30,
        string $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-_*=.:,?'
    ): string {
        $randompassword = "";
        $maxIndex = strlen($charset) -1;

        if ($length < 1) {
            $length = 30;
        }

        for ($i=0; $i < $length; $i++) {
            $randompassword .= substr($charset, random_int(0, $maxIndex), 1);
        }
        return $randompassword;
    }
}
