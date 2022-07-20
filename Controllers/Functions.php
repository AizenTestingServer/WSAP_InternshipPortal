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

    function time_in_enabled() { ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" 
        data-bs-target="#timeInModal">Time in</button> <?php
    }  

    function time_in_disabled() { ?>
         <button type="button" class="btn btn-success" disabled>Time in</button> <?php
    }

    function time_out_enabled() { ?>
        <button type="button" class="btn btn-smoke" data-bs-toggle="modal" 
        data-bs-target="#timeOutModal">Time out</button> <?php
    }

    function time_out_disabled() { ?>
        <button type="button" class="btn btn-smoke" disabled>Time out</button> <?php
    }

    function time_out_overtime_enabled() { ?>
        <button type="button" class="btn btn-indigo" data-bs-toggle="modal" 
        data-bs-target="#timeOutOvertimeModal">Time out (Overtime)</button> <?php
    }

    function time_out_overtime_disabled() { ?>
        <button type="button" class="btn btn-indigo" disabled>Time out (Overtime)</button> <?php
    }

    function atMorningShift($time_out) {
        $date = new Date();

        if (empty($time_out)) {
            $time_out = $date->getDateTimeValue();
        } else {
            $time_out = strtotime($time_out);
        }
        
        return $time_out < $date->morning_shift_end();
    }

    function atAfternoonShift($time_out) {
        $date = new Date();

        if (empty($time_out)) {
            $time_out = $date->getDateTimeValue();
        } else {
            $time_out = strtotime($time_out);
        }
        
        return $time_out >= $date->afternoon_shift_start() && $time_out < $date->time_out_start();
    }

    function atOvertime() {
        $date = new Date();
        
        $time_out = $date->getDateTimeValue();
        
        return $time_out >= $date->time_out_overtime_start() && $time_out < $date->time_out_overtime_end();
    }

    function atEndOfDay($time_out) {
        $date = new Date();

        if (empty($time_out)) {
            $time_out = $date->getDateTimeValue();
        } else {
            $time_out = strtotime($time_out);
        }
        
        return $time_out >= $date->late_time_out_end();
    }

    function atAfternoonTimeIn($time_in, $time_out) {
        $date = new Date();

        if (empty($time_out)) {
            $time_out = $date->getDateTimeValue();
        } else {
            $time_out = strtotime($time_out);
        }

        return strtotime($time_in) >= $date->morning_shift_end() && strtotime($time_in) < $date->afternoon_shift_start() &&
            $time_out >= $date->morning_shift_end() && $time_out < $date->afternoon_shift_start();
    }

    function isTimeInEnabled($att_date) {
        $date = new Date();
        return $att_date != $date->getDate() && date("N", strtotime($date->getDate())) != 7 &&
            (($date->getDateTimeValue() >= $date->time_in_start() &&  $date->getDateTimeValue() < $date->time_in_end()) ||
            ($date->getDateTimeValue() >= $date->morning_shift_end() && $date->getDateTimeValue() < $date->afternoon_shift_start()));
    }

    function isTimeOutEnabled($time_in, $time_out) {
        $date = new Date();

        if ($time_out == "NTO") {
            $time_out = null;
        }

        return !(!empty($time_out) || atMorningShift($time_out) || atAfternoonShift($time_out) ||
            atEndOfDay($time_out) || atAfternoonTimeIn($time_in, $time_out));
    }

    function isTimeOutOvertimeEnabled($time_out) {
        $date = new Date();

        if ($time_out == "NTO") {
            $time_out = null;
        }

        return !empty($time_out) && atOvertime() && !isOT($time_out);
    }

    function isAU($time) {
        return str_contains($time, "AU");
    }

    function isAE($time) {
        return str_contains($time, "AE");
    }

    function isMS($time) {
        return str_contains($time, "MS");
    }

    function isAS($time) {
        return str_contains($time, "AS");
    }

    function isOT($time) {
        return str_contains($time, "OT");
    }

    function isOD($time) {
        return str_contains($time, "OD");
    }

    function isL($time) {
        return str_contains($time, "L");
    }

    function isNTO($time) {
        return str_contains($time, "NTO");
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

    function isLateTimeOut($time_out) {
        $date = new Date();
        return strtotime($time_out) >= $date->time_out_end() &&
            strtotime($time_out) < $date->late_time_out_end();
    }

    function isOvertime($time_out) {
        $date = new Date();
        return strtotime($time_out) >= $date->time_out_overtime_start() &&
            strtotime($time_out) < $date->time_out_overtime_end();
    }

    function isActiveIntern($onboard_date, $offboard_date, $att_date) {
        return strtotime($onboard_date) <= strtotime($att_date) &&
            (empty($offboard_date) || strtotime($offboard_date) >= strtotime($att_date));
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

    function lateTimeOutSchedule() {
        $date = new Date();
        return date("g:i a", $date->time_out_end())." to ".
            date("g:i a", strtotime(date("g:i a", $date->late_time_out_end())." - 1 minutes"));
    }

    function overTimeTimeOutSchedule() {
        $date = new Date();
        return date("g:i a", $date->time_out_overtime_start())." to ".
            date("g:i a", strtotime(date("g:i a", $date->time_out_overtime_end())." - 1 minutes"));
    }

    function isDATEnabled() {
        $date = new Date();
        return $date->getDateTimeValue() >= $date->dat_start() &&
            $date->getDateTimeValue() < $date->dat_end();
    }

    function ordinalValue($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            return $number. 'th';
        else
            return $number. $ends[$number % 10];
    }
?>