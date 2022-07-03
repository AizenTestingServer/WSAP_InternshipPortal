<?php
    require_once "../Controllers/Database.php";

    $dbProfile = new Database();

    if (isset($_SESSION["intern_id"])) {
        $dbProfile->query("SELECT * FROM intern_personal_information WHERE id=:intern_id");
        $dbProfile->setInternId($_SESSION["intern_id"]);
    } else if (isset($_SESSION["intern_id_2"])) {
        $dbProfile->query("SELECT intern_personal_information.*, intern_wsap_information.*
        FROM intern_personal_information, intern_wsap_information
        WHERE intern_personal_information.id=:intern_id");
        $dbProfile->setInternId($_SESSION["intern_id_2"]);
    }
    $dbProfile->execute();
    
    $profileValue = $dbProfile->fetch();
?>

<ul class="profile_nav">
    <li class="user_toggler">
        <a class="fs-e text-secondary fw-bold" href="#">
            <img width="35" class="img-nav me-2 fs-inter" style="border-radius: 50%;"
            src="<?php
            if (isset($_SESSION["intern_id"])) {
                echo "../Assets/img/profile_imgs/default_male.png";
            } else if (isset($_SESSION["intern_id_2"])) { 
                if ($profileValue["image"] == null || strlen($profileValue["image"]) == 0) {
                    if ($profileValue["gender"] == 0) {
                        echo "../Assets/img/profile_imgs/default_male.png";
                    } else {
                        echo "../Assets/img/profile_imgs/default_female.png";
                    }
                } else {
                    echo $profileValue["image"];
                }
            } ?>" alt="">
            <span>
                <?php 
                    if (isset($_SESSION["intern_id"]) || isset($_SESSION["intern_id_2"])) {
                        echo $profileValue["last_name"].", ".$profileValue["first_name"]." ".$profileValue["middle_name"];
                    }
                ?>
            </span>
            <i class="fa-solid fa-caret-down ms-2 mt-1"></i>
        </a>
        <ul class="profile_list">
            <li><a class="text-danger" href="sign_out.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
    </li>
</ul>