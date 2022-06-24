<?php
    class Date {
        function __construct(){
            date_default_timezone_set('Asia/Manila');
        }

        function getTime() {
            return date('g:i a');
        }

        function getDate() {
            return date('F j, Y');
        }

        function getYear() {
            return date('Y');
        }

        function getMonth() {
            return date('m');
        }

        function getDay() {
            return date('d');
        }

        function getStringDate() {
            return strtotime(date('F j, Y'));
        }

        function getStringDateTime() {
            return strtotime(date('F j, Y g:i a'));
        }

        // time in start & end
        function time_in_start() {
            return strtotime(date('F j, Y').'7:00 am'); //  7:00 am
        }

        function morning_briefing(){
            return strtotime(date('F j, Y').'8:00 am'); //  8:00 am
        }

        function time_in_end(){
            return strtotime(date('F j, Y').'8:15 am'); //  8:15 am
        }

        function morning_shift_out() {
            return strtotime(date('F j, Y').'12:00 pm'); //  12:00 pm
        }

        function afternoon_shift_start() {
            return strtotime(date('F j, Y').'1:00 pm'); //  1:00 pm
        }

        function time_out_start() {
            return strtotime(date('F j, Y').'5:00 pm'); //  5:00 pm
        }

        function time_out_end() {
            return strtotime(date('F j, Y').'6:00 pm'); //  6:00 pm
        }

        function time_out_overtime_start() {
            return strtotime(date('F j, Y').'7:00 pm'); //  7:00 pm
        }

        function time_out_overtime_end() {
            return strtotime(date('F j, Y').'10:00 pm'); //  7:00 pm
        }

        function time_in_enabled() { ?>
            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" 
            data-bs-target="#timeinModal">Time in</button> <?php
        }  

        function time_in_disabled() { ?>
             <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" 
            data-bs-target="#timeinModal" disabled>Time in</button> <?php
        }    

        function time_out_enabled() { ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" 
            data-bs-target="#timeoutModal">Time out</button> <?php
        }

        function time_out_disabled() { ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" 
            data-bs-target="#timeoutModal" disabled>Time out</button> <?php
        }
    }


?>