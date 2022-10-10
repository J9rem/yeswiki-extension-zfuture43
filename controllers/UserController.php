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

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Zfuture43\Entity\User;
use YesWiki\Zfuture43\Controller\AuthController;
use YesWiki\Zfuture43\Exception\BadFormatPasswordException;
use YesWiki\Zfuture43\Exception\DeleteUserException;
use YesWiki\Zfuture43\Exception\UserEmailAlreadyUsedException;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Zfuture43\Service\UserManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Security\Controller\SecurityController;

class UserController extends YesWikiController
{
    use LimitationsTrait; // can be used here because included via AuthController

    public const DEFAULT_NAME_MAX_LENGTH = 80;
    public const DEFAULT_EMAIL_MAX_LENGTH = 254;

    private $limitations;

    protected $authController;
    protected $dbService;
    protected $pageManager;
    protected $params;
    protected $securityController;
    protected $tripleStore;
    protected $userManager;

    public function __construct(
        AuthController $authController,
        DbService $dbService,
        PageManager $pageManager,
        ParameterBagInterface $params,
        SecurityController $securityController,
        TripleStore $tripleStore,
        UserManager $userManager
    ) {
        $this->authController = $authController;
        $this->dbService = $dbService;
        $this->pageManager = $pageManager;
        $this->params = $params;
        $this->securityController = $securityController;
        $this->tripleStore = $tripleStore;
        $this->userManager = $userManager;
        $this->initLimitations();
    }

    /** Initializes object limitation properties using values from the config file
     *
     * @return void
     */
    private function initLimitations()
    {
        $this->limitations = [];
        $this->initLimitationHelper(
            'user_name_max_length',
            'nameMaxLength',
            FILTER_VALIDATE_INT,
            self::DEFAULT_NAME_MAX_LENGTH,
            'USER_NAME_MAX_LENGTH_NOT_INT'
        );
        $this->initLimitationHelper(
            'user_email_max_length',
            'emailMaxLength',
            FILTER_VALIDATE_INT,
            self::DEFAULT_EMAIL_MAX_LENGTH,
            'USER_EMAIL_MAX_LENGTH_NOT_INT'
        );
    }

    /**
     * create a user
     * for e-mail check is existing e-mail
     *
     * @param array $newValues (associative array)
     * @throws Exception
     * @return null|User $user
     */
    public function create(array $newValues): ?User
    {
        $newValues = $this->sanitizeValues($newValues);
        if (!empty($this->userManager->getOneUserByName($newValues['name']))) {
            throw new Exception(str_replace('{currentName}', $newValues['name'], _t('USERSETTINGS_NAME_ALREADY_USED')));
        }
        if (!empty($this->userManager->getOneUserByEmail($newValues['email']))) {
            throw new Exception(str_replace('{email}', $newValues['email'], _t('USERSETTINGS_EMAIL_ALREADY_USED')));
        }
        if (!empty($this->userManager->create($newValues))) {
            $user = $this->userManager->getOneUserByName($newValues['name']);
            if (!empty($user)) {
                return $user;
            }
        }
        throw new Exception(_t('USER_CREATION_FAILED').'.');
        return null;
    }

    /**
     * update user params
     * for e-mail check is existing e-mail
     *
     * @param User $user
     * @param array $newValues (associative array)
     * @throws BadFormatPasswordException
     * @throws Exception
     * @throws UserEmailAlreadyUsedException
     * @return bool
     */
    public function update(User $user, array $newValues): bool
    {
        $newValues = $this->sanitizeValues($newValues);
        $this->userManager->updatecommon($user, $newValues);
        if (isset($newValues['password'])) {
            $user = $this->userManager->getOneUserByName($user['name']);
            $this->authController->setPassword($user, $newValues['password']);
        }
        return true;
    }

    /**
     * sanitize values
     * @param array $newValues (associative array)
     * @return array $sanitizedValues
     * @throws Exception
     */
    private function sanitizeValues(array $newValues): array
    {
        if (isset($newValues['name'])) {
            $newValues['name'] = $this->sanitizeName($newValues['name']);
        }
        if (isset($newValues['email'])) {
            $newValues['email'] = $this->sanitizeEmail($newValues['email']);
        }
        if (isset($newValues['revisioncount'])) {
            $newValues['revisioncount'] = $this->sanitizeCount($newValues['revisioncount'], 'revisioncount');
        }
        if (isset($newValues['changescount'])) {
            $newValues['changescount'] = $this->sanitizeCount($newValues['changescount'], 'changescount');
        }
        if (isset($newValues['show_comments'])) {
            $newValues['show_comments'] = $this->sanitizeBoolean($newValues['show_comments'], 'show_comments');
        }
        if (isset($newValues['doubleclickedit'])) {
            $newValues['doubleclickedit'] = $this->sanitizeBoolean($newValues['doubleclickedit'], 'doubleclickedit');
        }
        if (isset($newValues['motto'])) {
            $newValues['doubleclickedit'] = $this->sanitizeString($newValues['doubleclickedit'], 'motto');
        }
        return $newValues;
    }

    /**
     * delete a user but check if possible before
     * @param User $user
     * @throws DeleteUserException
     * @throws Exception
     */
    public function delete(User $user)
    {
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        }
        if (!$this->wiki->UserIsAdmin()) {
            throw new DeleteUserException(_t('USER_MUST_BE_ADMIN_TO_DELETE').'.');
        }
        if ($this->isRunner($user)) {
            throw new DeleteUserException(_t('USER_CANT_DELETE_ONESELF').'.');
        }
        $this->checkIfUserIsNotAloneInEachGroup($user);
        $this->deleteUserFromEveryGroup($user);
        $this->removeOwnership($user);
        $this->userManager->deleteCommon($user);
    }

    /**
     * get first admin name
     * @return string $adminName
     * @throws Exception
     */
    public function getFirstAdmin(): string
    {
        $admins = $this->wiki->GetGroupACL(ADMIN_GROUP);
        $admins = str_replace(["\r\n","\r"], "\n", $admins);
        $admins = explode("\n", $admins);
        foreach ($admins as $line) {
            $line = trim($line);
            if (!empty($line) &&
                !in_array(substr($line, 0, 1), ['@','!','#'])) {
                $adminUser = $this->userManager->getOneUserByName($line);
                if (!empty($adminUser['name'])) {
                    $admin = $adminUser['name'];
                    break;
                }
            }
        }
        if (empty($admin)) {
            throw new Exception("No admin found");
        }

        return $admin;
    }

    /**
     * check if current user is the user to delete
     * @param User $user
     * @return bool
     */
    private function isRunner(User $user): bool
    {
        $loggedUser = $this->authController->getLoggedUser();
        return (!empty($loggedUser) && ($loggedUser['name'] == $user['name']));
    }

    /**
     * check if user is not alone in each group
     * @param User $user
     * @throws DeleteUserException
     */
    private function checkIfUserIsNotAloneInEachGroup(User $user)
    {
        $grouptab = $this->userManager->groupsWhereIsMemberCommon($user, false);
        foreach ($grouptab as $group) {
            $groupmembers = $this->wiki->GetGroupACL($group);
            $groupmembers = str_replace(["\r\n","\r"], "\n", $groupmembers);
            $groupmembers = explode("\n", $groupmembers);
            $groupmembers = array_unique(array_filter(array_map('trim', $groupmembers)));
            if (count($groupmembers) == 1) { // Only one user in (this user then)
                throw new DeleteUserException(_t('USER_DELETE_LONE_MEMBER_OF_GROUP')." ($group).");
            }
        }
    }

    /**
     * remove user from every group
     * @param User $user
     * @throws DeleteUserException
     */
    private function deleteUserFromEveryGroup(User $user)
    {
        // Delete user in every group
        $searchedValue = $this->dbService->escape($user['name']);
        $groups = $this->tripleStore->getMatching(
            GROUP_PREFIX."%",
            "http://www.wikini.net/_vocabulary/acls",
            "%$searchedValue%",
            "LIKE",
            "=",
            "LIKE"
        );
        $error = false;
        if (is_array($groups)) {
            $pregQuoteSearchValue = preg_quote($searchedValue, '/');
            foreach ($groups as $group) {
                $newValue = $group['value'];
                $newValue = preg_replace("/(?<=^|\\n|\\r)$pregQuoteSearchValue(?:\\r\\n|\\n|\\r|$)/", "", $newValue);
                if ($newValue != $group['value'] &&
                    !in_array($this->tripleStore->update(
                        $group['resource'],
                        $group['property'],
                        $group['value'],
                        $newValue,
                        '',
                        ''
                    ), [0,3])) {
                    $error = true;
                }
            }
        }
        if ($error) {
            throw new DeleteUserException(_t('USER_DELETE_QUERY_FAILED').'.');
        }
    }

    /**
     * remove user from every group
     * @param User $user
     * @throws Exception
     */
    private function removeOwnership(User $user)
    {
        $pagesWhereOwner = $this->dbService->loadAll("
            SELECT `tag` FROM {$this->dbService->prefixTable('pages')} 
            WHERE `owner` = \"{$this->dbService->escape($user['name'])}\"
            AND `latest` = \"Y\" ;
        ");
        $pagesWhereOwner = array_map(function ($page) {
            return $page['tag'];
        }, $pagesWhereOwner);

        $firstAdmin = $this->getFirstAdmin();
        foreach ($pagesWhereOwner as $tag) {
            $this->pageManager->setOwner($tag, $firstAdmin);
        }
    }

    /**
     * check if value is int and return new value
     * @throws Exception
     * @param mixed $value
     * @param string $propertyName
     * @return int
     */
    private function sanitizeCount($value, string $propertyName): int
    {
        if (!filter_var($value, FILTER_VALIDATE_INT) || $value < 0) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_A_POSITIVE_INTEGER_FOR', ['name' =>$propertyName]));
        }
        return intval($value);
    }

    /**
     * check if value is Y or N and return new value
     * @throws Exception
     * @param mixed $value
     * @param string $propertyName
     * @return string
     */
    private function sanitizeBoolean($value, string $propertyName): string
    {
        $value = strtolower($value);
        if (!in_array($value, ['o', 'oui', 'y', 'yes', 'n', 'non', 'no', '0', '1',"true","false"])) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_YES_OR_NO', ['name' =>$propertyName]));
        }
        return in_array($value, ['o', 'oui', 'y', 'yes','1',"true"]) ? 'Y' : 'N';
    }

    /**
     * check if value is String and return new value
     * @throws Exception
     * @param mixed $value
     * @param string $propertyName
     * @return string
     */
    private function sanitizeString($value, string $propertyName): string
    {
        if (!is_scalar($value)) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_A_STRING', ['name' =>$propertyName]));
        }
        return strval($value);
    }

    /**
     * check if value is a nameand return new value
     * @throws Exception
     * @param mixed $value
     * @return string
     */
    private function sanitizeName($value): string
    {
        if (empty($value)) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_A_NAME').'.');
        }
        if (!is_scalar($value)) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_A_STRING', ['name' =>'name']));
        }
        $value = strval($value);
        if (strlen($value) > $this->limitations['nameMaxLength']) {
            throw new Exception(_t('USER_NAME_S_MAXIMUM_LENGTH_IS')." {$this->limitations['nameMaxLength']}.");
        } elseif (preg_match('/(?:^[!#@<>\\\\\/].*$|[<>\\\\\/]|^.{0,2}$)/', $value)) {
            throw new Exception(_t('USER_THIS_IS_NOT_A_VALID_NAME').".");
        }
        return $value;
    }

    /**
     * check if value is an email and return new value
     * @throws Exception
     * @param mixed $value
     * @return string
     */
    private function sanitizeEmail($value): string
    {
        if (empty($value)) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_AN_EMAIL').'.');
        }

        if (!is_scalar($value)) {
            throw new Exception(_t('USER_YOU_MUST_SPECIFY_A_STRING', ['name' =>'email']));
        }
        $value = strval($value);
        if (strlen($value) > $this->limitations['emailMaxLength']) {
            throw new Exception(_t('USER_EMAIL_S_MAXIMUM_LENGTH_IS')." {$this->limitations['emailMaxLength']}.");
        } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(_t('USER_THIS_IS_NOT_A_VALID_EMAIL').".");
        }
        return $value;
    }
}
