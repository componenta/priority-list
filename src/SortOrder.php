<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

/**
 * Defines the iteration order for priority-based collections.
 */
enum SortOrder
{
    /**
     * Iterate from lowest to highest priority.
     */
    case ASC;

    /**
     * Iterate from highest to lowest priority.
     */
    case DESC;
}
