<?php
    require_once "../Controllers/Database.php";

    $dbProfile = new Database();

    $dbProfile->query("SELECT intern_wsap_information.*, intern_personal_information.*
    FROM intern_wsap_information, intern_personal_information
    WHERE intern_wsap_information.id=:intern_id AND intern_personal_information.id=:intern_id");
    $dbProfile->setInternId($_SESSION["intern_id"]);
    $dbProfile->execute();
    
    $value = $dbProfile->fetch();
?>

<ul class="profile_settings">
    <li class="user_toggler">
        <a class="fs-e text-secondary fw-bold" href="#">
            <img width="35" class="me-2 fs-inter" style="border-radius: 50%;"
            height="100%" src="<?php {
                if ($value["img"] == null || strlen($value["img"]) == 0) {
                    if ($value["gender"] == 0) {
                        echo "../Assets/img/profile_imgs/default_male.png";
                    } else {
                        echo "../Assets/img/profile_imgs/default_female.png";
                    }
                } else {
                    echo $value["img"];
                }
            } ?>" alt="">
            <span>
                <?php 
                    if (isset($_SESSION["intern_id"])) {
                        echo $value["last_name"].", ".$value["first_name"]." ".$value["middle_name"];
                    }
                ?>
            </span>
            <i class="fa-solid fa-caret-down ms-2 mt-1"></i>
        </a>
        <ul class="profile_list">
            <li><a href="profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
            <li><a href="attendance.php"><i class="fa-solid fa-stopwatch me-2"></i>My Attendance</a></li>
            <li><a href="#"><i class="fa-solid fa-calendar-days me-2"></i>My Schedule</a></li>
            <li><a href="sign_out.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
    </li>
</ul>