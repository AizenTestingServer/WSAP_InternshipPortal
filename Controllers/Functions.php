<?php
    require_once "Date.php";

    function redirect($location) {
        header("Location: ".$location);
    }

    if (!function_exists('str_contains')) {
        function str_contains(string $haystack, string $needle): bool {
            return '' === $needle || false !== strpos($haystack, $needle);
        }
    }

    function randomWord($len = 5) {
        $result = array_merge(range('0', '9'), range('A', 'Z'));
        shuffle($result);
        return substr(implode($result), 0, $len);
    }

    function atMorningShift() {
        $date = new Date();
        return $date->getDateTimeValue() < $date->morning_shift_end();
    }

    function atAfternoonShift() {
        $date = new Date();
        return $date->getDateTimeValue() >= $date->afternoon_shift_start() && $date->getDateTimeValue() < $date->time_out_start();
    }

    function atOvertime() {
        $date = new Date();
        return $date->getDateTimeValue() >= $date->time_out_end() && $date->getDateTimeValue() < $date->time_out_overtime_start();
    }

    function atEndOfDay() {
        $date = new Date();
        return $date->getDateTimeValue() >= $date->time_out_overtime_end();
    }

    function atAfternoonTimeIn($time_in) {
        $date = new Date();
        return strtotime($time_in) >= $date->morning_shift_end() && strtotime($time_in) < $date->afternoon_shift_start() &&
            $date->getDateTimeValue() >= $date->morning_shift_end() && $date->getDateTimeValue() < $date->afternoon_shift_start();
    }

?>