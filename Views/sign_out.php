<?php
    include_once "../Controllers/Functions.php";
    session_start();
    session_unset();
    session_destroy();
    redirect("../index");
?>