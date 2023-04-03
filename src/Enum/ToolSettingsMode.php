<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

enum ToolSettingsMode: string
{

    /**
     * Settings at all levels mode.
     */
    case All = 'all';

    /**
     * Settings with distinct names at all levels mode.
     */
    case Distinct = 'distinct';

}
