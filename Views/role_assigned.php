<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }

    if (empty($_GET["intern_id"]) || empty($_GET["role_id"])) {
        redirect("assign_roles.php");
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

    if ($admin_roles_count != 0) {
        $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*,
        intern_wsap_information.*, intern_accounts.*, departments.*
        FROM intern_personal_information, intern_wsap_information, intern_accounts, departments
        WHERE intern_personal_information.id = intern_wsap_information.id AND
        intern_personal_information.id = intern_accounts.id AND
        intern_wsap_information.department_id = departments.id AND
        intern_personal_information.id=:intern_id");
        $db->setInternId($_GET["intern_id"]);
        $db->execute();
        $value = $db->fetch();
        $intern_count = $db->rowCount();

        if ($intern_count == 0) {
            redirect("assign_roles.php");
            exit();
        }

        $db->query("SELECT * FROM roles WHERE id=:id");
        $db->setId($_GET["role_id"]);
        $db->execute();
        $role = $db->fetch();

        $assign_role = array($_GET["intern_id"],
        $_GET["role_id"],
        0);

        $db->query("INSERT INTO intern_roles
        VALUES(null, :intern_id, :role_id, :role_type)");
        $db->assignRole($assign_role);
        $db->execute();
        $db->closeStmt();
                    
        $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
            " (".$admin_info["name"].") assigned the ".$role["name"]." role to ".$value["last_name"].", ".$value["first_name"].".";

        $log = array($date->getDateTime(),
        strtoupper($_SESSION["intern_id"]),
        $log_value);

        $db->query("INSERT INTO audit_logs
        VALUES (null, :timestamp, :intern_id, :log)");
        $db->log($log);
        $db->execute();
        $db->closeStmt();
        
        $_SESSION["role_success"] = "Successfully assigned a role to intern.";

        redirect("assign_roles.php?intern_id=".$_GET["intern_id"]);
        exit();
    } else {
        require_once "../Templates/header_view.php";
        setTitle("Role Assigned"); ?>

        <div class="my-container"> <?php
            include_once "access_denied.php"; ?>
        </div> <?php
    }
    
    require_once "../Templates/footer.php";
?>