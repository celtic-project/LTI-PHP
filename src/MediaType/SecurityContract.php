<?php

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\ToolProvider;

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
     * @param ToolProvider $toolProvider  Tool Provider instance
     * @param string $secret Shared secret
     */
    function __construct($toolProvider, $secret)
    {
        $tcContexts = array();
        foreach ($toolProvider->consumer->profile->{'@context'} as $context) {
            if (is_object($context)) {
                $tcContexts = array_merge(get_object_vars($context), $tcContexts);
            }
        }

        $this->shared_secret = $secret;
        $toolServices = array();
        foreach ($toolProvider->requiredServices as $requiredService) {
            foreach ($requiredService->formats as $format) {
                $service = $toolProvider->findService($format, $requiredService->actions);
                if (($service !== false) && !array_key_exists($service->{'@id'}, $toolServices)) {
                    $id = $service->{'@id'};
                    $parts = explode(':', $id, 2);
                    if (count($parts) > 1) {
                        if (array_key_exists($parts[0], $tcContexts)) {
                            $id = "{$tcContexts[$parts[0]]}{$parts[1]}";
                        }
                    }
                    $toolService = new \stdClass;
                    $toolService->{'@type'} = 'RestServiceProfile';
                    $toolService->service = $id;
                    $toolService->action = $requiredService->actions;
                    $toolServices[$service->{'@id'}] = $toolService;
                }
            }
        }
        foreach ($toolProvider->optionalServices as $optionalService) {
            foreach ($optionalService->formats as $format) {
                $service = $toolProvider->findService($format, $optionalService->actions);
                if (($service !== false) && !array_key_exists($service->{'@id'}, $toolServices)) {
                    $id = $service->{'@id'};
                    $parts = explode(':', $id, 2);
                    if (count($parts) > 1) {
                        if (array_key_exists($parts[0], $tcContexts)) {
                            $id = "{$tcContexts[$parts[0]]}{$parts[1]}";
                        }
                    }
                    $toolService = new \stdClass;
                    $toolService->{'@type'} = 'RestServiceProfile';
                    $toolService->service = $id;
                    $toolService->action = $optionalService->actions;
                    $toolServices[$service->{'@id'}] = $toolService;
                }
            }
        }
        $this->tool_service = array_values($toolServices);
    }

}
