<?php
declare(strict_types=1);
/**
 * Class PaginationDate
 *
 * This class represents a date used for pagination purposes.
 * It contains the year and month as strings.
 */
namespace Pagination;

class PaginationDate {
    public function __construct(
        public string $year,  // Four-digit string, e.g., "2025"
        public string $month  // Two-digit string, e.g., "04"
    ) {}
}
