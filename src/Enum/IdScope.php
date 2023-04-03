<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

enum IdScope: int
{

    /**
     * Use ID value only.
     */
    case IdOnly = 0;

    /**
     * Prefix an ID with the consumer key.
     */
    case Global = 1;

    /**
     * Prefix the ID with the consumer key and context ID.
     */
    case Context =
    2;

    /**
     * Prefix the ID with the consumer key and resource ID.
     */
    case Resource =
    3;

    /**
     * Character used to separate each element of an ID.
     */
    const SEPARATOR =

    ':';

    }

