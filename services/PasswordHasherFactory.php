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

require_once 'tools/zfuture43/objects/MD5PasswordHasher.php'; // TODO use autoload

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory as SymfonyPasswordHasherFactory;
use YesWiki\Zfuture43\Entity\User;
use YesWiki\Zfuture43\MD5PasswordHasher;
use YesWiki\Core\Service\DbService;

class PasswordHasherFactory extends SymfonyPasswordHasherFactory
{
    protected $dbService;

    public function __construct(DbService $dbService)
    {
        $this->dbService = $dbService;
        $newModeActivated = $this->newModeIsActivated();
        if ($newModeActivated) {
            $params = [
                'md5' => [
                    'class' => MD5PasswordHasher::class,
                    'arguments' => [true]
                ],
                User::class => [
                    'algorithm' => 'auto',
                    'migrate_from' => [
                        'md5' // uses the "md5" hasher configured above
                    ]
                ]
            ];
        } else {
            $params = [
                User::class => [
                    'class' => MD5PasswordHasher::class,
                    'arguments' => [false]
                ]
            ];
        }
        parent::__construct($params);
    }

    public function newModeIsActivated(): bool
    {
        return false;
        $result = $this->dbService->query("SHOW COLUMNS FROM {$this->dbService->prefixTable("users")} LIKE 'password';");
        if (@mysqli_num_rows($result) === 0) {
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return !empty($row['Type']) && $row['Type'] == "varchar(256)";
    }

    public function activateNewMode(): bool
    {
        return $this->dbService->query("ALTER TABLE {$this->dbService->prefixTable("users")} MODIFY COLUMN `password` varchar(256) NOT NULL;");
    }
}
