<?php

namespace ceLTIc\LTI;

/**
 * Class to represent a platform user
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class User
{

    /**
     * List of principal roles for LTI 1.3.
     */
    const PRINCIPAL_ROLES = array(
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#None',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport',
        'http://purl.imsglobal.org/vocab/lis/v2/system/person#User',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer',
        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer'
    );

    /**
     * User's first name.
     *
     * @var string $firstname
     */
    public $firstname = '';

    /**
     * User's middle name.
     *
     * @var string $middlename
     */
    public $middlename = '';

    /**
     * User's last name (surname or family name).
     *
     * @var string $lastname
     */
    public $lastname = '';

    /**
     * User's fullname.
     *
     * @var string $fullname
     */
    public $fullname = '';

    /**
     * Allow user name field to be empty?
     *
     * @var bool $allowEmptyName
     */
    public static $allowEmptyName = false;

    /**
     * User's sourcedId.
     *
     * @var string $sourcedId
     */
    public $sourcedId = null;

    /**
     * User's username.
     *
     * @var string $username
     */
    public $username = null;

    /**
     * User's email address.
     *
     * @var string $email
     */
    public $email = '';

    /**
     * User's image URI.
     *
     * @var string $image
     */
    public $image = '';

    /**
     * Roles for user.
     *
     * @var array $roles
     */
    public $roles = array();

    /**
     * Groups for user.
     *
     * @var array $groups
     */
    public $groups = array();

    /**
     * user ID as supplied in the last connection request.
     *
     * @var string|null $ltiUserId
     */
    public $ltiUserId = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialise the user.
     */
    public function initialize()
    {
        $this->firstname = '';
        $this->middlename = '';
        $this->lastname = '';
        $this->fullname = '';
        $this->sourcedId = null;
        $this->username = null;
        $this->email = '';
        $this->image = '';
        $this->roles = array();
        $this->groups = array();
    }

    /**
     * Initialise the user.
     *
     * Synonym for initialize().
     */
    public function initialise()
    {
        $this->initialize();
    }

    /**
     * Set the user's name.
     *
     * @param string $firstname User's first name.
     * @param string $lastname User's last name.
     * @param string $fullname User's full name.
     * @param string $middlename User's middle name (optional, default is none).
     */
    public function setNames($firstname, $lastname, $fullname, $middlename = null)
    {
        $names = array(0 => '', 1 => '');
        if (!empty($fullname)) {
            $this->fullname = trim($fullname);
            $names = preg_split("/[\s]+/", $this->fullname);
        }
        if (!empty($firstname)) {
            $this->firstname = trim($firstname);
            $names[0] = $this->firstname;
        } elseif (!empty($names[0])) {
            $this->firstname = $names[0];
        } elseif (!static::$allowEmptyName) {
            $this->firstname = 'User';
        } else {
            $this->firstname = '';
        }
        if (!empty($middlename)) {
            $this->middlename = trim($middlename);
        } elseif ((count($names) > 2) && !empty($names[1])) {
            $this->middlename = '';
            for ($i = 1; $i < count($names); $i++) {
                $this->middlename .= $names[$i] . ' ';
            }
            $this->middlename = trim($this->middlename);
        } else {
            $this->middlename = '';
        }
        if (!empty($lastname)) {
            $this->lastname = trim($lastname);
        } elseif ((count($names) > 1) && !empty($names[count($names) - 1])) {
            $this->lastname = $names[count($names) - 1];
        } elseif (!static::$allowEmptyName) {
            $this->lastname = $this->ltiUserId;
        } else {
            $this->lastname = '';
        }
        if (empty($this->fullname) && (!empty($this->firstname) || !empty($this->lastname))) {
            $this->fullname = $this->firstname;
            if (!empty($this->middlename)) {
                $this->fullname .= " {$this->middlename}";
            }
            $this->fullname .= " {$this->lastname}";
        }
    }

    /**
     * Set the user's email address.
     *
     * @param string $email        Email address value
     * @param string $defaultEmail Value to use if no email is provided (optional, default is none)
     */
    public function setEmail($email, $defaultEmail = null)
    {
        if (!empty($email)) {
            $this->email = $email;
        } elseif (!empty($defaultEmail)) {
            $this->email = $defaultEmail;
            if (substr($this->email, 0, 1) === '@') {
                if (!empty($this->username)) {
                    $this->email = "{$this->username}{$this->email}";
                } else {
                    $this->email = "{$this->ltiUserId}{$this->email}";
                }
            }
        } else {
            $this->email = '';
        }
    }

    /**
     * Check if the user is an administrator (at any of the system, institution or context levels).
     *
     * @return bool    True if the user has a role of administrator
     */
    public function isAdmin()
    {
        return $this->hasRole('Administrator') || $this->hasRole('urn:lti:sysrole:ims/lis/SysAdmin') ||
            $this->hasRole('urn:lti:sysrole:ims/lis/Administrator') || $this->hasRole('urn:lti:instrole:ims/lis/Administrator');
    }

    /**
     * Check if the user is staff.
     *
     * @return bool    True if the user has a role of instructor, contentdeveloper or teachingassistant
     */
    public function isStaff()
    {
        return ($this->hasRole('Instructor') || $this->hasRole('ContentDeveloper') || $this->hasRole('TeachingAssistant'));
    }

    /**
     * Check if the user is a learner.
     *
     * @return bool    True if the user has a role of learner
     */
    public function isLearner()
    {
        return $this->hasRole('Learner');
    }

###
###  PRIVATE METHODS
###

    /**
     * Check whether the user has a specified role name.
     *
     * @param string $role Name of role
     *
     * @return bool    True if the user has the specified role
     */
    private function hasRole($role)
    {
        $ok = in_array($role, $this->roles);
        if (!$ok && (strpos($role, 'urn:') !== 0) && (strpos($role, 'http://') !== 0) && (strpos($role, 'https://') !== 0)) {
            $role = "urn:lti:role:ims/lis/{$role}";
            $ok = in_array($role, $this->roles);
        }
        if (!$ok) {
            $role2 = null;
            $role3 = null;
            if (strpos($role, 'urn:') === 0) {
                if (strpos($role, 'urn:lti:role:ims/lis/') === 0) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/membership#' . substr($role, 21);
                } elseif (strpos($role, 'urn:lti:instrole:ims/lis/') === 0) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 25);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#' . substr($role, 25);
                } elseif (strpos($role, 'urn:lti:sysrole:ims/lis/') === 0) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 24);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#' . substr($role, 24);
                }
            } elseif (strpos($role, 'http://purl.imsglobal.org/vocab/lis/v2/') === 0) {
                if (strpos($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership#') === 0) {
                    $role2 = 'urn:lti:role:ims/lis/' . substr($role, 50);
                } elseif (strpos($role, 'http://purl.imsglobal.org/vocab/lis/v2/person#') === 0) {
                    $role2 = 'urn:lti:instrole:ims/lis/' . substr($role, 46);
                    $role3 = 'urn:lti:sysrole:ims/lis/' . substr($role, 46);
                } elseif (strpos($role, 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#') === 0) {
                    $role2 = 'urn:lti:instrole:ims/lis/' . substr($role, 58);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 58);
                } elseif (strpos($role, 'http://purl.imsglobal.org/vocab/lis/v2/system/person#') === 0) {
                    $role2 = 'urn:lti:sysrole:ims/lis/' . substr($role, 53);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 53);
                }
            }
            if (!empty($role2)) {
                $ok = in_array($role2, $this->roles);
                if (!$ok && !empty($role3)) {
                    $ok = in_array($role3, $this->roles);
                }
            }
        }

        return $ok;
    }

}
