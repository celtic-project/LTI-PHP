<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Session;

use Illuminate\Support\Facades\Session;

/**
 * Class to implement the user session interface using the Laravel framework.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LaravelSessionClient implements ClientInterface
{

    /**
     * Get user session ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return Session::getId();
    }

    /**
     * Get user session name.
     *
     * @return string
     */
    public function getName(): string
    {
        return Session::getName();
    }

    /**
     * Open a user session.
     *
     * @return bool  True if a user session was opened (false if one was already open)
     */
    public function openSession(): bool
    {
        return false;
    }

    /**
     * Close a user session.
     *
     * @return void
     */
    public function closeSession(): void
    {

    }

    /**
     * Check if an item exists in the user session.
     *
     * @param $name string  Name of session item
     *
     * @return bool  True if the item exists in the user session
     */
    public function hasItem(string $name): bool
    {
        return Session::has($name);
    }

    /**
     * Get a session item value.
     *
     * @param $name string  Name of session item
     *
     * @return mixed  Value of session item or null if the item does not exist
     */
    public function getItem(string $name): mixed
    {
        $value = null;
        if ($this->hasItem($name)) {
            $value = Session::get($name);
        }

        return $value;
    }

    /**
     * Set a session item value.
     *
     * @param $name string  Name of session item
     * @param $value mixed  Value of session item (or null to delete the item)
     *
     * @return void
     */
    public function setItem(string $name, mixed $value): void
    {
        if (!is_null($value)) {
            Session::put($name, $value);
        } else {
            Session::forget($name);
        }
    }

}
