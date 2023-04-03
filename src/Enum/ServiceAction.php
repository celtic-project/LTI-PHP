<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

enum ServiceAction: int
{

    /**
     * Read action.
     */
    case Read = 1;

    /**
     * Write (create/update) action.
     */
    case Write = 2;

    /**
     * Delete action.
     */
    case Delete = 3;

    /**
     * Create action.
     */
    case Create = 4;

    /**
     * Update action.
     */
    case Update = 5;

}
