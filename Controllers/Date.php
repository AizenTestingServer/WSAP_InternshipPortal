<?php
    class Date {
        function __construct(){
            date_default_timezone_set("Asia/Manila");
        }

        function getTime() {
            return date("g:i a");
        }

        function getDateTime() {
            return date("F j, Y g:i a");
        }

        function getHour() {
            return date("g");
        }

        function getMin() {
            return date("i");
        }

        function getTimeType() {
            return date("a");
        }

        function getDate() {
            return date("F j, Y");
        }

        function getNumericDate() {
            return date("Y-m-d");
        }

        function getYear() {
            return date("Y");
        }

        function getMonth() {
            return date("m");
        }

        function getMonthName() {
            return date("F");
        }

        function getDay() {
            return date("j");
        }

        function getDateValue() {
            return strtotime(date("F j, Y"));
        }

        function getDateTimeValue() {
            return strtotime(date("F j, Y g:i a"));
        }

        // time in start & end
        function time_in_start() {
            return strtotime(date("F j, Y")."6:00 am"); //  7:00 am
        }

        function morning_briefing(){
            return strtotime(date("F j, Y")."8:16 am"); //  8:00 am
        }

        function time_in_end(){
            return strtotime(date("F j, Y")."12:00 pm"); //  8:25 am
        }

        function morning_shift_end() {
            return strtotime(date("F j, Y")."12:00 pm"); //  12:00 pm
        }

        function afternoon_shift_start() {
            return strtotime(date("F j, Y")."1:16 pm"); //  1:00 pm
        }

        function time_out_start() {
            return strtotime(date("F j, Y")."5:00 pm"); //  5:00 pm
        }

        function time_out_end() {
            return strtotime(date("F j, Y")."6:01 pm"); //  5:25 pm
        }

        function late_time_out_end() {
            return strtotime(date("F j, Y")."10:01 pm"); //  5:25 pm
        }

        function time_out_overtime_start() {
            return strtotime(date("F j, Y")."5:15 pm"); //  6:00 pm
        }

        function time_out_overtime_end() {
            return strtotime(date("F j, Y")."9:16 pm"); //  9:25 pm
        }

        function dat_start() {
            return strtotime(date("F j, Y")."4:00 pm"); //  4:00 pm
        }

        function dat_end() {
            return strtotime(date("F j, Y")."8:00 pm"); //  8:00 pm
        }
    }


?>