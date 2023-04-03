<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

enum LtiVersion: string
{

    /**
     * LTI version 1 for messages.
     */
    case V1 = 'LTI-1p0';

    /**
     * LTI version 1.3 for messages.
     */
    case V1P3 = '1.3.0';

    /**
     * LTI version 2 for messages.
     */
    case V2 = 'LTI-2p0';

}
