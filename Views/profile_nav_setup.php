<?php
    require_once "../Controllers/Database.php";

    $dbProfile = new Database();

    $dbProfile->query("SELECT * FROM intern_personal_information WHERE id=:intern_id");
    $dbProfile->setInternId($_SESSION["intern_id"]);
    $dbProfile->execute();
    
    $profileValue = $dbProfile->fetch();
?>

<ul class="profile_nav">
    <li class="user_toggler">
        <a class="fs-e text-secondary fw-bold" href="#">
            <img width="35" class="img-nav me-2 fs-inter" style="border-radius: 50%;"
            src="../Assets/img/profile_imgs/default_male.png" alt="">
            <span>
                <?php 
                    if (isset($_SESSION["intern_id"])) {
                        echo $profileValue["last_name"].", ".$profileValue["first_name"]." ".$profileValue["middle_name"];
                    }
                ?>
            </span>
            <i class="fa-solid fa-caret-down ms-2 mt-1"></i>
        </a>
        <ul class="profile_list">
            <li><a href="sign_out.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
    </li>
</ul>