<?php

namespace Mindlahus\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class EntityHelper
{
    private static $membersOfGroup;
    private static $membersAllTogether;

    /**
     * \DateTime::ISO8601 is not compatible with the ISO8601 itself
     * For compatibility use \DateTime::ATOM or just c
     *
     * @param $propertyPath
     * @param $returnFormatted
     * @param string $format
     * @return \DateTime|null|string
     */
    public static function getDateTime($propertyPath, $returnFormatted, $format = \DateTime::ATOM)
    {
        if ($propertyPath instanceof \DateTime) {
            return $returnFormatted
                ?
                $propertyPath->format($format)
                :
                $propertyPath;
        }

        return null;
    }

    /**
     * @param array $groups
     * @param $getter (string) Ex getMembers | getRoles
     * @param null $hasPivotTable
     * @return mixed
     */
    public static function getMembersOfGroups(array $groups, $getter, $hasPivotTable = null)
    {
        static::$membersOfGroup = (object)[
            'collection' => new ArrayCollection(),
            'array' => [],
            'total' => 0
        ];

        foreach ($groups as $group) {

            /**
             * get team members
             */
            foreach ($group->{$getter}() as $member) {
                if ($hasPivotTable) {
                    $member = $member->getUser();
                }
                /**
                 * two or more teams can contain the same member
                 */
                if (!in_array($member->getId(), static::$membersOfGroup->array)) {
                    static::$membersOfGroup->array[] = $member->getId();
                    static::$membersOfGroup->collection->add($member);
                    static::$membersOfGroup->total++;
                }
            }
        }

        return static::$membersOfGroup;
    }

    /**
     * @param array $assignees
     * @param array $groups
     * @param $getter
     * @param null $hasPivotTable
     * @return mixed
     */
    public static function getMembersOfGroupsAndAssignees(array $assignees, array $groups, $getter, $hasPivotTable = null)
    {
        static::$membersAllTogether = static::getMembersOfGroups($groups, $getter, $hasPivotTable);

        foreach ($assignees as $assignee) {

            if (!in_array($assignee->getId(), static::$membersAllTogether->array)) {
                static::$membersAllTogether->array[] = $assignee->getId();
                static::$membersAllTogether->collection->add($assignee);
                static::$membersAllTogether->total++;
            }
        }

        return static::$membersAllTogether;
    }

    /**
     * @param array $usedPasswords
     * @return null|string
     */
    public static function isNotValidPassword(array $usedPasswords)
    {
        $requestStack = Request::createFromGlobals();


        /**
         * if no password change action registered
         * do nothing
         */
        if ($requestStack->getContentType() === 'json') {
            $requestContent = json_decode($requestStack->getContent());

            if (!property_exists($requestContent, 'password')) {
                return null;
            }

            if (!property_exists($requestContent, 'passwordConfirmation')) {
                return "The `Password Confirmation` value is required!`";
            }

            $password = $requestContent->password;
            $passwordConfirmation = $requestContent->passwordConfirmation;
        } else {
            if (!$requestStack->request->has('password')) {
                return null;
            }

            $password = $requestStack->request->get('password');
            $passwordConfirmation = $requestStack->request->get('passwordConfirmation');
        }

        if (in_array(sha1($password), $usedPasswords)) {
            return "This password was already used!";
        }

        if ($password !== $passwordConfirmation) {
            return "`Password` and `Password Confirmation` does not match!";
        }

        switch (false) {
            case preg_match('/[0-9]+/', $password):
                $wrongPasswordFormat = 'one digit.';
                break;
            case preg_match('/[a-z]+/', $password):
                $wrongPasswordFormat = 'one lowercase letter.';
                break;
            case preg_match('/[A-Z]+/', $password):
                $wrongPasswordFormat = 'one uppercase letter.';
                break;
            case preg_match('/[^a-zA-Z\d]/', $password):
                $wrongPasswordFormat = 'one special character.';
                break;
            default:
                return null;
        }

        return "Your password should contain at least {$wrongPasswordFormat}";
    }
}