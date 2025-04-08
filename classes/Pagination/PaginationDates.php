<?php
declare(strict_types=1);

namespace Pagination;
/**
 * Class PaginationDates
 *
 * This class represents a set of dates used for pagination purposes.
 * It contains the previous year, previous month, next month, and next year.
 */
use Pagination\PaginationDate;

class PaginationDates {
    public function __construct(
        public PaginationDate $prevYear,
        public PaginationDate $prevMonth,
        public PaginationDate $nextMonth,
        public PaginationDate $nextYear
    ) {}
}