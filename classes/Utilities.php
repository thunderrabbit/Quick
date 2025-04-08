<?php

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

    public static function getNavigationDates(int $year, int $month): array
    {
        // Validate the year and month
        if (!checkdate(month: $month, day: 1, year: $year)) {
            throw new InvalidArgumentException(message: "Invalid year or month provided.");
        }

        // Create a DateTime object for the given year/month
        $date = DateTime::createFromFormat(format: 'Y-m-d', datetime: "$year-$month-01");

        if ($date === false) {
            throw new Exception(message: "Failed to create DateTime object.");
        }

        return self::calculateNavigationDates(date: $date);
    }
    private static function calculateNavigationDates(DateTime $date): array
    {
        // Previous year (<<)
        $prevYearDate = clone $date;
        $prevYearDate->modify(modifier: '-1 year');
        $prevYearYear = $prevYearDate->format(format: 'Y');
        $prevYearMonth = $date->format(format: 'm'); // Month stays the same

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
        $nextYearMonth = $date->format(format: 'm'); // Month stays the same

        return [
            'prevYear' => [
                'year' => $prevYearYear,
                'month' => $prevYearMonth,
            ],
            'prevMonth' => [
                'year' => $prevMonthYear,
                'month' => $prevMonthMonth,
            ],
            'nextMonth' => [
                'year' => $nextMonthYear,
                'month' => $nextMonthMonth,
            ],
            'nextYear' => [
                'year' => $nextYearYear,
                'month' => $nextYearMonth,
            ],
        ];
    }

    public static function renderPaginationLinks(array $pagination_dates)
    {
        $html = '';

        // Previous Year (<<)
        $prevYearYear = $pagination_dates['prevYear']['year'];
        $prevYearMonth = $pagination_dates['prevYear']['month'];
        $html .= "<a href=\"/list/?year=$prevYearYear&month=$prevYearMonth\">&lt;&lt;</a> ";

        // Previous Month (<)
        $prevMonthYear = $pagination_dates['prevMonth']['year'];
        $prevMonthMonth = $pagination_dates['prevMonth']['month'];
        $html .= "<a href=\"/list/?year=$prevMonthYear&month=$prevMonthMonth\">&lt;</a> ";

        // Next Month (>)
        $nextMonthYear = $pagination_dates['nextMonth']['year'];
        $nextMonthMonth = $pagination_dates['nextMonth']['month'];
        $html .= "<a href=\"/list/?year=$nextMonthYear&month=$nextMonthMonth\">&gt;</a> ";

        // Next Year (>>)
        $nextYearYear = $pagination_dates['nextYear']['year'];
        $nextYearMonth = $pagination_dates['nextYear']['month'];
        $html .= "<a href=\"/list/?year=$nextYearYear&month=$nextYearMonth\">&gt;&gt;</a>";

        return $html;
    }
}
