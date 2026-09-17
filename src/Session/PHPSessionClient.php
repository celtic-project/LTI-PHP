<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Session;

/**
 * Class to implement the user session interface using the PHP $_SESSION superglobal variable.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class PHPSessionClient implements ClientInterface
{

    /**
     * Get user session ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Set user session ID.
     *
     * @param string $id  Session ID value
     *
     * @return void
     */
    public function setId(string $id): void
    {
        session_id($id);
        session_start();
    }

    /**
     * Get user session name.
     *
     * @return string
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Open a user session.
     *
     * @return bool  True if a user session was opened (false if one was already open)
     */
    public function openSession(): bool
    {
        $hasSession = session_status() === PHP_SESSION_ACTIVE;
        if (!$hasSession) {
            session_start();
        }

        return !$hasSession;
    }

    /**
     * Close a user session.
     *
     * @return void
     */
    public function closeSession(): void
    {
        session_write_close();
    }

    /**
     * Check if an item exists in the user session.
     *
     * @param string $name  Name of session item
     *
     * @return bool  True if the item exists in the user session
     */
    public function hasItem(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Get a session item value.
     *
     * @param string $name    Name of session item
     * @param mixed $default  Default value to return when item does not exist
     *
     * @return mixed  Value of session item or default value if the item does not exist
     */
    public function getItem(string $name, mixed $default = null): mixed
    {
        if ($this->hasItem($name)) {
            $default = $_SESSION[$name];
        }

        return $default;
    }

    /**
     * Set a session item value.
     *
     * @param string $name  Name of session item
     * @param mixed $value  Value of session item (or null to delete the item)
     *
     * @return void
     */
    public function setItem(string $name, mixed $value): void
    {
        if (!is_null($value)) {
            $_SESSION[$name] = $value;
        } else {
            unset($_SESSION[$name]);
        }
    }

}
