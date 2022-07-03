<?php
    require_once "../Controllers/Database.php";

    $dbProfile = new Database();

    $dbProfile->query("SELECT intern_wsap_information.*, intern_personal_information.*, intern_educational_information.*, intern_accounts.*
    FROM intern_wsap_information, intern_personal_information, intern_educational_information, intern_accounts
    WHERE intern_wsap_information.id=:intern_id AND
    intern_personal_information.id=:intern_id AND
    intern_educational_information.id=:intern_id AND
    intern_accounts.id=:intern_id");
    $dbProfile->setInternId($_SESSION["intern_id"]);
    $dbProfile->execute();
    
    $profileValue = $dbProfile->fetch();
?>

<ul class="profile_nav">
    <li class="user_toggler">
        <a class="fs-e text-secondary fw-bold" href="#">
            <img class="img-nav me-2 fs-inter" style="border-radius: 50%;"
            src="<?php {
                if ($profileValue["image"] == null || strlen($profileValue["image"]) == 0) {
                    if ($profileValue["gender"] == 0) {
                        echo "../Assets/img/profile_imgs/default_male.png";
                    } else {
                        echo "../Assets/img/profile_imgs/default_female.png";
                    }
                } else {
                    echo $profileValue["image"];
                }
            } ?>" alt="" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
            <span class="me-1">
                <?php 
                    if (isset($_SESSION["intern_id"])) {
                        echo $profileValue["last_name"].", ".$profileValue["first_name"]." ".$profileValue["middle_name"];
                    }
                ?>
            </span>
            <i class="fa-solid fa-caret-down mb-1"></i>
        </a>
        <ul class="profile_list">
            <li><a href="profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
            <li><a href="attendance.php"><i class="fa-solid fa-stopwatch me-2"></i>My Attendance</a></li>
            <li><a class="text-danger" href="sign_out.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
    </li>
</ul>