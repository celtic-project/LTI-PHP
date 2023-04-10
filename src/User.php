<?php
declare(strict_types=1);

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
    public const PRINCIPAL_ROLES = [
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
    ];

    /**
     * Allow user name field to be empty?
     *
     * @var bool $allowEmptyName
     */
    public static bool $allowEmptyName = false;

    /**
     * User's first name.
     *
     * @var string $firstname
     */
    public string $firstname = '';

    /**
     * User's middle name.
     *
     * @var string $middlename
     */
    public string $middlename = '';

    /**
     * User's last name (surname or family name).
     *
     * @var string $lastname
     */
    public string $lastname = '';

    /**
     * User's fullname.
     *
     * @var string $fullname
     */
    public string $fullname = '';

    /**
     * User's sourcedId.
     *
     * @var string $sourcedId
     */
    public ?string $sourcedId = null;

    /**
     * User's username.
     *
     * @var string $username
     */
    public ?string $username = null;

    /**
     * User's email address.
     *
     * @var string $email
     */
    public string $email = '';

    /**
     * User's image URI.
     *
     * @var string $image
     */
    public string $image = '';

    /**
     * Roles for user.
     *
     * @var array $roles
     */
    public array $roles = [];

    /**
     * Groups for user.
     *
     * @var array $groups
     */
    public array $groups = [];

    /**
     * user ID as supplied in the last connection request.
     *
     * @var string|null $ltiUserId
     */
    public ?string $ltiUserId = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialise the user.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->firstname = '';
        $this->middlename = '';
        $this->lastname = '';
        $this->fullname = '';
        $this->sourcedId = null;
        $this->username = null;
        $this->email = '';
        $this->image = '';
        $this->roles = [];
        $this->groups = [];
    }

    /**
     * Initialise the user.
     *
     * Synonym for initialize().
     *
     * @return void
     */
    public function initialise(): void
    {
        $this->initialize();
    }

    /**
     * Set the user's name.
     *
     * @param string $firstname   User's first name.
     * @param string $lastname    User's last name.
     * @param string $fullname    User's full name.
     * @param string $middlename  User's middle name (optional, default is none).
     *
     * @return void
     */
    public function setNames(string $firstname, string $lastname, string $fullname, ?string $middlename = null): void
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
     * @param string $email         Email address value
     * @param string $defaultEmail  Value to use if no email is provided (optional, default is none)
     *
     * @return void
     */
    public function setEmail(string $email, ?string $defaultEmail = null): void
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
     * Check if the user is a learner.
     *
     * @return bool  True if the user has a context role of Learner
     */
    public function isLearner(): bool
    {
        return $this->hasRole('Learner');
    }

    /**
     * Check if the user is an instructor.
     *
     * @return bool  True if the user has a context role of Instructor
     */
    public function isInstructor(): bool
    {
        return $this->hasRole('Instructor');
    }

    /**
     * Check if the user is a content developer.
     *
     * @return bool  True if the user has a context role of ContentDeveloper
     */
    public function isContentDeveloper(): bool
    {
        return $this->hasRole('ContentDeveloper');
    }

    /**
     * Check if the user is a teaching assistant.
     *
     * @return bool  True if the user has a context role of TeachingAssistant
     */
    public function isTeachingAssistant(): bool
    {
        return $this->hasRole('TeachingAssistant');
    }

    /**
     * Check if the user is a manager.
     *
     * @return bool  True if the user has a context role of Manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('Manager');
    }

    /**
     * Check if the user is a member.
     *
     * @return bool  True if the user has a context role of Member
     */
    public function isMember(): bool
    {
        return $this->hasRole('Member');
    }

    /**
     * Check if the user is an officer.
     *
     * @return bool  True if the user has a context role of Officer
     */
    public function isOfficer(): bool
    {
        return $this->hasRole('Officer');  // NB Role not defined for LTI versions prior to 1.3
    }

    /**
     * Check if the user is staff.
     *
     * @return bool  True if the user has a context role of Instructor, ContentDeveloper or TeachingAssistant
     */
    public function isStaff(): bool
    {
        return $this->isInstructor() || $this->isContentDeveloper() || $this->isTeachingAssistant();
    }

    /**
     * Check if the user is a mentor.
     *
     * @return bool  True if the user has a context role of Mentor
     */
    public function isMentor(): bool
    {
        return $this->hasRole('Mentor');
    }

    /**
     * Check if the user is an administrator (at any of the system, institution or context levels).
     *
     * @return bool  True if the user has a role of administrator
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Administrator') || $this->hasRole('urn:lti:sysrole:ims/lis/SysAdmin') ||
            $this->hasRole('urn:lti:sysrole:ims/lis/Administrator') || $this->hasRole('urn:lti:instrole:ims/lis/Administrator');
    }

###
###  PRIVATE METHODS
###

    /**
     * Check whether the user has a specified role name.
     *
     * @param string $role Name of role
     *
     * @return bool  True if the user has the specified role
     */
    private function hasRole(string $role): bool
    {
        $ok = in_array($role, $this->roles);
        if (!$ok && !str_starts_with($role, 'urn:') && !str_starts_with($role, 'http://') && !str_starts_with($role, 'https://')) {
            $role = "urn:lti:role:ims/lis/{$role}";
            $ok = in_array($role, $this->roles);
        }
        if (!$ok) {
            $role2 = null;
            $role3 = null;
            if (str_starts_with($role, 'urn:')) {
                if (str_starts_with($role, 'urn:lti:role:ims/lis/')) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/membership#' . substr($role, 21);
                    if (substr($role, 21) === 'TeachingAssistant') {
                        $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant';
                    }
                } elseif (str_starts_with($role, 'urn:lti:instrole:ims/lis/')) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 25);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#' . substr($role, 25);
                } elseif (str_starts_with($role, 'urn:lti:sysrole:ims/lis/')) {
                    $role2 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 24);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#' . substr($role, 24);
                }
            } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/')) {
                if (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership#')) {
                    $role2 = 'urn:lti:role:ims/lis/' . substr($role, 50);
                } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/person#')) {
                    $role2 = 'urn:lti:instrole:ims/lis/' . substr($role, 46);
                    $role3 = 'urn:lti:sysrole:ims/lis/' . substr($role, 46);
                } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#')) {
                    $role2 = 'urn:lti:instrole:ims/lis/' . substr($role, 58);
                    $role3 = 'http://purl.imsglobal.org/vocab/lis/v2/person#' . substr($role, 58);
                } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/system/person#')) {
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
