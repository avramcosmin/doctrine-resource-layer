<?php

namespace Mindlahus\Helper;

use Doctrine\Common\Collections\ArrayCollection;

class EntityHelper
{
    /**
     * \DateTime::ISO8601 is not compatible with the ISO8601 itself
     * For compatibility use \DateTime::ATOM or just c
     *
     * @param \DateTime $val
     * @param string $format
     * @return string
     */
    public static function getFormattedDateTime(\DateTime $val, string $format = \DateTime::ATOM)
    {
        return StringHelper::dateFormat($val, $format);
    }

    /**
     * $options = [
     *  getter          required    string
     *  hasPivotTable   optional    boolean
     *  pivotGetter     optional    string      *Required if $options['hasPivotTable'] === true
     * ]
     *
     * @param array $groups
     * @param array $assignees
     * @param array $options
     * @return mixed
     */
    public static function getMembersAllTogether(array $groups, array $assignees, array $options = [])
    {
        $membersAllTogether = static::getMembersOfGroupsAllTogether($groups, $options);

        foreach ($assignees as $assignee) {
            /**
             * @var Object $assignee
             */
            if (!in_array($assignee->getId(), $membersAllTogether->array)) {
                $membersAllTogether->array[] = $assignee->getId();
                $membersAllTogether->collection->add($assignee);
                $membersAllTogether->total++;
            }
        }

        return $membersAllTogether;
    }

    /**
     * $options = [
     *  getter          required    string
     *  hasPivotTable   optional    boolean
     *  pivotGetter     optional    string      *Required if $options['hasPivotTable'] === true
     * ]
     *
     * @param array $groups
     * @param array $options
     * @return object
     */
    public static function getMembersOfGroupsAllTogether(array $groups, array $options = [])
    {
        $membersOfGroupsAllTogether = (object)[
            'collection' => new ArrayCollection(),
            'array' => [],
            'total' => 0
        ];

        foreach ($groups as $group) {
            foreach ($group->{$options['getter']}() as $member) {
                /**
                 * Even if in general a Group Entity has a $users representation of the User Entity,
                 * some times the User Entity is connected to the Group entity via a group_user table
                 */
                if (($options['hasPivotTable'] ?? null) === true) {
                    $member = $member->{$options['pivotGetter']}();
                }

                /**
                 * Many groups can have the same user
                 * We are interested to having a unique collection of users
                 * This is why we ignore the duplicate id's
                 * @var Object $member
                 */
                if (!in_array($member->getId(), $membersOfGroupsAllTogether->array)) {
                    $membersOfGroupsAllTogether->array[] = $member->getId();
                    $membersOfGroupsAllTogether->collection->add($member);
                    $membersOfGroupsAllTogether->total++;
                }
            }
        }

        return $membersOfGroupsAllTogether;
    }

    /**
     * @param string $password
     * @param string $passwordConfirmation
     * @param string $passwordHistory
     * @return null|string
     */
    public static function isNotValidPassword(string $password, string $passwordConfirmation, string $passwordHistory)
    {
        if (array_key_exists(
            static::_encryptPassword($password),
            static::getPasswordHistory($passwordHistory)
        )) {
            return 'Sorry! You have to choose a password which you never used in the past.';
        }

        if ($password !== $passwordConfirmation) {
            return "`Password` and `Password Confirmation` does not match!";
        }

        switch (false) {
            case preg_match('/[0-9]+/', $password):
                $wrongPasswordFormat = 'one digit';
                break;
            case preg_match('/[a-z]+/', $password):
                $wrongPasswordFormat = 'one lowercase letter';
                break;
            case preg_match('/[A-Z]+/', $password):
                $wrongPasswordFormat = 'one uppercase letter';
                break;
            case preg_match('/[^a-zA-Z0-9]/', $password):
                $wrongPasswordFormat = 'one special character';
                break;
            case strlen($password) > 5:
                $wrongPasswordFormat = '6 (six) characters';
                break;
            default:
                return false;
        }

        return "Your password should contain at least {$wrongPasswordFormat}.";
    }

    /**
     * @param string $password
     * @return string
     */
    private static function _encryptPassword(string $password)
    {
        return sha1($password);
    }

    /**
     * @param string $passwordHistory
     * @return array|mixed|string
     */
    public static function getPasswordHistory(string $passwordHistory)
    {
        $passwordHistory = unserialize($passwordHistory);

        if (!is_array($passwordHistory)) {
            $passwordHistory = [];
        }

        return $passwordHistory;
    }

    public static function setPasswordHistory(string $newPassword, string $passwordHistory)
    {
        $passwordHistory = static::getPasswordHistory($passwordHistory);
        $passwordHistory[static::_encryptPassword($newPassword)] = new \DateTime();

        return serialize($passwordHistory);
    }
}