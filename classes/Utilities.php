<?php

use Pagination\PaginationDate;
use Pagination\PaginationDates;

class Utilities {

    public static function randomString(int $length, $possible = NULL): string
    {
        $randString = "";
        // define possible characters
        if (!isset($possible)) {
            $possible = "0123456789abcdfghjkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ";
        }
        // add random characters
        for ($i = 0; $i < $length; $i++) {
            // pick a random character from the possible ones
            $char = substr($possible, random_int(0, strlen($possible) - 1), 1);
            $randString .= $char;
        }
        return $randString;
    }

    public static function getNavigationDates(string $year, string $month): PaginationDates
    {
        // Validate the year and month
        if (!preg_match('/^\d{4}$/', $year) || !preg_match('/^\d{2}$/', $month) || !checkdate(month: (int) $month, day: 1, year: (int) $year)) {
            throw new InvalidArgumentException(message: "Invalid year or month provided. Year must be 4 digits, month must be 2 digits.");
        }

        // Create a DateTime object for the given year/month
        $date = DateTime::createFromFormat(format: 'Y-m-d', datetime: "$year-$month-01");

        if ($date === false) {
            throw new Exception(message: "Failed to create DateTime object.");
        }

        return self::calculateNavigationDates(date: $date);
    }

    private static function calculateNavigationDates(DateTime $date): PaginationDates
    {
        // Previous year (<<)
        $prevYearDate = clone $date;
        $prevYearDate->modify(modifier: '-1 year');
        $prevYearYear = $prevYearDate->format(format: 'Y');  // String "2024"
        $prevYearMonth = $date->format(format: 'm');         // String "04"

        // Previous month (<)
        $prevMonthDate = clone $date;
        $prevMonthDate->modify(modifier: '-1 month');
        $prevMonthYear = $prevMonthDate->format(format: 'Y');
        $prevMonthMonth = $prevMonthDate->format(format: 'm');

        // Next month (>)
        $nextMonthDate = clone $date;
        $nextMonthDate->modify(modifier: '+1 month');
        $nextMonthYear = $nextMonthDate->format(format: 'Y');
        $nextMonthMonth = $nextMonthDate->format(format: 'm');

        // Next year (>>)
        $nextYearDate = clone $date;
        $nextYearDate->modify(modifier: '+1 year');
        $nextYearYear = $nextYearDate->format(format: 'Y');
        $nextYearMonth = $date->format(format: 'm');

        return new PaginationDates(
            prevYear: new PaginationDate(year: $prevYearYear, month: $prevYearMonth),
            prevMonth: new PaginationDate(year: $prevMonthYear, month: $prevMonthMonth),
            nextMonth: new PaginationDate(year: $nextMonthYear, month: $nextMonthMonth),
            nextYear: new PaginationDate(year: $nextYearYear, month: $nextYearMonth)
        );
    }

    public static function renderPaginationLinks(PaginationDates $pagination_dates): string
    {
        $html = '';

        // Previous Year (<<)
        $prevYearYear = $pagination_dates->prevYear->year;
        $prevYearMonth = $pagination_dates->prevYear->month;
        $html .= "<a href=\"/list/?year=$prevYearYear&month=$prevYearMonth\">&lt;&lt;</a> ";

        // Previous Month (<)
        $prevMonthYear = $pagination_dates->prevMonth->year;
        $prevMonthMonth = $pagination_dates->prevMonth->month;
        $html .= "<a href=\"/list/?year=$prevMonthYear&month=$prevMonthMonth\">&lt;</a> ";

        // Current Month
        $html .= "<a href=\"/list/\">-</a> ";

        // Next Month (>)
        $nextMonthYear = $pagination_dates->nextMonth->year;
        $nextMonthMonth = $pagination_dates->nextMonth->month;
        $html .= "<a href=\"/list/?year=$nextMonthYear&month=$nextMonthMonth\">&gt;</a> ";

        // Next Year (>>)
        $nextYearYear = $pagination_dates->nextYear->year;
        $nextYearMonth = $pagination_dates->nextYear->month;
        $html .= "<a href=\"/list/?year=$nextYearYear&month=$nextYearMonth\">&gt;&gt;</a>";

        return $html;
    }
}
