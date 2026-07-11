<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Session;

/**
 * Interface to represent a user session
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
interface ClientInterface
{

    /**
     * Get user session ID.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get user session name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Open a user session.
     *
     * @return bool  True if a user session was opened (false if one was already open)
     */
    public function openSession(): bool;

    /**
     * Close a user session.
     *
     * @return void
     */
    public function closeSession(): void;

    /**
     * Check if an item exists in the user session.
     *
     * @param $name string  Name of session item
     *
     * @return bool  True if the item exists in the user session
     */
    public function hasItem(string $name): bool;

    /**
     * Get a session item value.
     *
     * @param $name string    Name of session item
     * @param $default mixed  Default value to return when item does not exist
     *
     * @return mixed  Value of session item or default value if the item does not exist
     */
    public function getItem(string $name, mixed $default = null): mixed;

    /**
     * Set a session item value.
     *
     * @param $name string  Name of session item
     * @param $value mixed  Value of session item (or null to delete the item)
     *
     * @return void
     */
    public function setItem(string $name, mixed $value): void;

}
