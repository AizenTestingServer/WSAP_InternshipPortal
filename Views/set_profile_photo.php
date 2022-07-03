<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }

    if (empty($_GET["intern_id"]) || empty($_GET["image_id"])) {
        redirect("profile.php");
        exit();
    }

    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    $db = new Database();
    $date = new Date();
    
    $db->query("SELECT intern_personal_information.*, intern_roles.*, roles.*
    FROM intern_personal_information, intern_roles, roles
    WHERE intern_personal_information.id=intern_roles.intern_id AND
    intern_roles.role_id=roles.id AND roles.admin=1 AND
    intern_personal_information.id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    $admin_info = $db->fetch();
    $admin_roles_count = $db->rowCount();

    if (!empty($_GET["intern_id"])) {
        $db->query("SELECT intern_wsap_information.*, intern_personal_information.*, intern_educational_information.*, intern_accounts.*
        FROM intern_wsap_information, intern_personal_information, intern_educational_information, intern_accounts
        WHERE intern_wsap_information.id=:intern_id AND
        intern_personal_information.id=:intern_id AND
        intern_educational_information.id=:intern_id AND
        intern_accounts.id=:intern_id");
        $db->setInternId($_GET["intern_id"]);
        $db->execute();
        $value = $db->fetch();
        $intern_count = $db->rowCount();

        if ($intern_count == 0) {
            redirect("profile.php");
            exit();
        }
    }

    if ($admin_roles_count != 0 || $_GET["intern_id"] == $_SESSION["intern_id"]) {
        $db->query("SELECT * FROM images WHERE id=:id");
        $db->setId($_GET["image_id"]);
        $db->execute();

        $image = $db->fetch();
      
        $profile_image = array(
            $image["image_path"],
            strtoupper($_GET["intern_id"]),
        );

        $db->query("UPDATE intern_wsap_information
        SET image=:image WHERE id=:intern_id");
        $db->setProfileImage($profile_image);
        $db->execute();
        $db->closeStmt();
        
        if ($_GET["intern_id"] != $_SESSION["intern_id"]) {
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
            " (".$admin_info["name"].") set an image for ".$value["last_name"].", ".$value["first_name"].".";

            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);

            $db->query("INSERT INTO audit_logs
            VALUES (null, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
        }

        $_SESSION["upload_success"] = "The image has been set successfully.";

        if ($_GET["intern_id"] == strtoupper($_SESSION["intern_id"])) {
            redirect("profile.php");
        } else {
            redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]);
        }
        exit();
    } else {
        require_once "../Templates/header_view.php";
        setTitle("Set Profile Photo"); 
        
        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]);
        exit();
    }
    
    require_once "../Templates/footer.php";
?>