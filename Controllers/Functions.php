<?php
    require_once "Date.php";

    function redirect($location) {
        header("Location: ".$location);
    }

    if (!function_exists("str_contains")) {
        function str_contains(string $haystack, string $needle): bool {
            return "" === $needle || false !== strpos($haystack, $needle);
        }
    }

    function randomWord($len = 5) {
        $result = array_merge(range("0", "9"), range("A", "Z"));
        shuffle($result);
        return substr(implode($result), 0, $len);
    }

    function isValidEmail($email_address) {
        return filter_var($email_address, FILTER_VALIDATE_EMAIL);
    }

    function isValidMobileNumber($mobile_number) {
        return preg_match("/^[0-9]{10}+$/", $mobile_number);
    }

    function isValidPassword($password) {
        return preg_match("/^[A-Za-z0-9]*$/", $password);
    }

    function fullTrim($string) {
        $splitted_string = explode(" ", $string);
        $trimmed_string = array();
        
        foreach ($splitted_string as $string) {
            if (!empty(trim($string))) {
                array_push($trimmed_string, trim($string));
            }
        }
        return implode(" ", $trimmed_string);
    }

    function toProper($string) {
        return ucwords(strtolower($string));
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

    function isTimeInEnabled($att_date) {
        $date = new Date();
        return $att_date != $date->getDate() && date("N", strtotime($date->getDate())) != 7 &&
            (($date->getDateTimeValue() >= $date->time_in_start() &&  $date->getDateTimeValue() < $date->time_in_end()) ||
            ($date->getDateTimeValue() >= $date->morning_shift_end() && $date->getDateTimeValue() < $date->afternoon_shift_start()));
    }

    function isTimeOutEnabled($time_in, $time_out) {
        $date = new Date();
        return !(!empty($time_out) || atMorningShift() || atAfternoonShift() ||
            atOvertime() || atEndOfDay() || atAfternoonTimeIn($time_in));
    }

    function isMorningShift($time_in, $time_out) {
        $date = new Date();
        return strtotime($time_in) >= $date->time_in_start() && strtotime($time_in) < $date->afternoon_shift_start() &&
            strtotime($time_out) >= $date->time_in_start() && strtotime($time_out) < $date->afternoon_shift_start();
    }

    function isAfternoonShift($time_in, $time_out) {
        $date = new Date();
        return strtotime($time_in) >= $date->morning_shift_end() && strtotime($time_in) < $date->time_out_overtime_end() &&
            strtotime($time_out) >= $date->morning_shift_end() && strtotime($time_out) < $date->time_out_overtime_end();
    }

    function isOvertime($time_out) {
        $date = new Date();
        return strtotime($time_out) >= $date->time_out_overtime_start() && strtotime($time_out) < $date->time_out_overtime_end();
    }

    function getMonths() {
        return array("January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December");
    }

    function regularTimeInSchedule() {
        $date = new Date();
        return date("g:i a", $date->time_in_start())." to ".
            date("g:i a", strtotime(date("g:i a", $date->morning_briefing())." - 1 minutes"));
    }

    function lateTimeInSchedule() {
        $date = new Date();
        return date("g:i a", $date->morning_briefing())." to ".
            date("g:i a", strtotime(date("g:i a", $date->time_in_end())." - 1 minutes"));
    }

    function morningShiftTimeOutSchedule() {
        $date = new Date();
        return date("g:i a", $date->morning_shift_end())." to ".
            date("g:i a", strtotime(date("g:i a", $date->afternoon_shift_start())." - 1 minutes"));
    }

    function afternoonShiftTimeInSchedule() {
        $date = new Date();
        return date("g:i a", $date->morning_shift_end())." to ".
            date("g:i a", strtotime(date("g:i a", $date->afternoon_shift_start())." - 1 minutes"));
    }

    function regularTimeOutSchedule() {
        $date = new Date();
        return date("g:i a", $date->time_out_start())." to ".
            date("g:i a", strtotime(date("g:i a", $date->time_out_end())." - 1 minutes"));
    }

    function overTimeTimeOutSchedule() {
        $date = new Date();
        return date("g:i a", $date->time_out_overtime_start())." to ".
            date("g:i a", strtotime(date("g:i a", $date->time_out_overtime_end())." - 1 minutes"));
    }
?>