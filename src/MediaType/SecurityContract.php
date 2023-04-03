<?php
declare(strict_types=1);

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\Tool;

/**
 * Class to represent an LTI Security Contract document
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class SecurityContract
{

    /**
     * Class constructor.
     *
     * @param Tool $tool      Tool instance
     * @param string $secret  Shared secret
     */
    function __construct(Tool $tool, string $secret)
    {
        $tcContexts = [];
        foreach ($tool->platform->profile->{'@context'} as $context) {
            if (is_object($context)) {
                $tcContexts = array_merge(get_object_vars($context), $tcContexts);
            }
        }

        $this->shared_secret = $secret;
        $toolServices = [];
        foreach ($tool->requiredServices as $requiredService) {
            foreach ($requiredService->formats as $format) {
                $service = $tool->findService($format, $requiredService->actions);
                if (($service !== false) && !array_key_exists($service->{'@id'}, $toolServices)) {
                    $id = $service->{'@id'};
                    $parts = explode(':', $id, 2);
                    if (count($parts) > 1) {
                        if (array_key_exists($parts[0], $tcContexts)) {
                            $id = "{$tcContexts[$parts[0]]}{$parts[1]}";
                        }
                    }
                    $toolServices[$service->{'@id'}] = (object) [
                            '@type' => 'RestServiceProfile',
                            'service' => $id,
                            'action' => $requiredService->actions
                    ];
                }
            }
        }
        foreach ($tool->optionalServices as $optionalService) {
            foreach ($optionalService->formats as $format) {
                $service = $tool->findService($format, $optionalService->actions);
                if (($service !== false) && !array_key_exists($service->{'@id'}, $toolServices)) {
                    $id = $service->{'@id'};
                    $parts = explode(':', $id, 2);
                    if (count($parts) > 1) {
                        if (array_key_exists($parts[0], $tcContexts)) {
                            $id = "{$tcContexts[$parts[0]]}{$parts[1]}";
                        }
                    }
                    $toolServices[$service->{'@id'}] = (object) [
                            '@type' => 'RestServiceProfile',
                            'service' => $id,
                            'action' => $optionalService->actions
                    ];
                }
            }
        }
        $this->tool_service = array_values($toolServices);
    }

}
