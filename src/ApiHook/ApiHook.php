<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ApiHook;

/**
 * Trait to handle API hook registrations
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
trait ApiHook
{

    /**
     * User Id hook name.
     */
    public static string $USER_ID_HOOK = "UserId";

    /**
     * Context Id hook name.
     */
    public static string $CONTEXT_ID_HOOK = "ContextId";

    /**
     * Course Groups service hook name.
     */
    public static string $GROUPS_SERVICE_HOOK = "Groups";

    /**
     * Memberships service hook name.
     */
    public static string $MEMBERSHIPS_SERVICE_HOOK = "Memberships";

    /**
     * Outcomes service hook name.
     */
    public static string $OUTCOMES_SERVICE_HOOK = "Outcomes";

    /**
     * Tool Settings service hook name.
     */
    public static string $TOOL_SETTINGS_SERVICE_HOOK = "ToolSettings";

    /**
     * Access Token service hook name.
     */
    public static string $ACCESS_TOKEN_SERVICE_HOOK = "AccessToken";

    /**
     * API hook class names.
     */
    private static array $API_HOOKS = [];

    /**
     * Register the availability of an API hook.
     *
     * @param string $hookName    Name of hook
     * @param string $familyCode  Family code for current platform
     * @param string $className   Name of implementing class
     *
     * @return void
     */
    public static function registerApiHook(string $hookName, string $familyCode, string $className): void
    {
        $class = self::class;
        self::$API_HOOKS["{$class}-{$hookName}-{$familyCode}"] = $className;
    }

    /**
     * Get the class name for an API hook.
     *
     * @param string $hookName    Name of hook
     * @param string $familyCode  Family code for current platform
     *
     * @return string|null  Class name
     */
    private static function getApiHook(string $hookName, string $familyCode): ?string
    {
        $class = self::class;
        return self::$API_HOOKS["{$class}-{$hookName}-{$familyCode}"];
    }

    /**
     * Check if an API hook is registered.
     *
     * @param string $hookName    Name of hook
     * @param string $familyCode  Family code for current platform
     *
     * @return bool  True if the API hook is registered
     */
    private static function hasApiHook(string $hookName, string $familyCode): bool
    {
        $class = self::class;
        return isset(self::$API_HOOKS["{$class}-{$hookName}-{$familyCode}"]);
    }

    /**
     * Check if an API hook is registered and configured.
     *
     * @param string $hookName                             Name of hook
     * @param string $familyCode                           Family code for current platform
     * @param Platform|Context|ResourceLink $sourceObject  Source object for which hook is to be used
     *
     * @return bool  True if the API hook is registered and configured
     */
    private static function hasConfiguredApiHook(string $hookName, string $familyCode, $sourceObject): bool
    {
        $ok = false;
        $class = self::class;
        if (isset(self::$API_HOOKS["{$class}-{$hookName}-{$familyCode}"])) {
            $className = self::$API_HOOKS["{$class}-{$hookName}-{$familyCode}"];
            $hook = new $className($sourceObject);
            $ok = $hook->isConfigured();
        }

        return $ok;
    }

}
