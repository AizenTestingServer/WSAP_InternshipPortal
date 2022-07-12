<?php
    session_start();
    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
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
    $admin_roles_count = $db->rowCount();

    $db->query("SELECT * FROM intern_wsap_information WHERE id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();

    $intern_wsap_info = $db->fetch();

    $i_am_active = isActiveIntern($intern_wsap_info["onboard_date"], $intern_wsap_info["offboard_date"], $date->getDate());

    $db->query("SELECT COUNT(*) AS total_interns FROM intern_personal_information");
    $db->execute();

    $total_interns = 0;
    if ($value = $db->fetch()) {
        $total_interns = $value["total_interns"];
    }

    $db->query("SELECT COUNT(*) AS active_interns
    FROM intern_wsap_information, intern_accounts
    WHERE intern_wsap_information.status = 1 AND
    intern_wsap_information.id = intern_accounts.id");
    $db->execute();

    $active_interns = 0;
    if ($value = $db->fetch()) {
        $active_interns = $value["active_interns"];
    }

    $db->query("SELECT COUNT(*) AS brand_count FROM brands");
    $db->execute();

    $brand_count = 0;
    if ($value = $db->fetch()) {
        $brand_count = $value["brand_count"];
    }

    $db->query("SELECT roles.*
    FROM roles
    WHERE max_overtime_hours=(SELECT MAX(roles.max_overtime_hours)
    FROM roles, intern_roles
    WHERE roles.id = intern_roles.role_id AND
    intern_roles.intern_id=:intern_id) AND
    EXISTS (SELECT * FROM intern_roles
    WHERE roles.id = intern_roles.role_id AND
    intern_roles.intern_id=:intern_id)
    ORDER BY admin_level DESC LIMIT 1");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();

    $max_overtime_hours = $db->fetch();

    if ($db->rowCount() != 0) {
        $overtime_hours_left = $max_overtime_hours["max_overtime_hours"];
    } else {
        $overtime_hours_left = 10;
    }

    $db->query("SELECT * FROM overtime_hours WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();

    $overtime_hours = $db->fetch();

    $day = "friday";

	if (strtotime("today") < strtotime($day)) {
	  $start_week_date = date("F j, Y", strtotime("last ".$day));
	} else {
	  $start_week_date = date("F j, Y", strtotime($day));
	}

    if ($db->rowCount() == 0 || $overtime_hours["start_week_date"] != $start_week_date) {
        $overtime_data = array(
            strtoupper($_SESSION["intern_id"]),
            $start_week_date,
            $overtime_hours_left
        );

        $db->query("INSERT INTO overtime_hours VALUES (null, :intern_id, :start_week_date, :overtime_hours_left)");
        $db->setOvertimeData($overtime_data);
        $db->execute();
        $db->closeStmt();

        $db->query("SELECT * FROM overtime_hours WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1");
        $db->setInternId($_SESSION["intern_id"]);
        $db->execute();

        $overtime_hours = $db->fetch();
    }

    $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1;");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    $lts_att = $db->fetch();

    $remind_time_out = false;
    if ($db->rowCount() != 0 && $date->getDateValue() == strtotime($lts_att["att_date"])) {
        $remind_time_out = isTimeOutEnabled($lts_att["time_in"], $lts_att["time_out"]);
    }

    $db->query("SELECT * FROM intern_wsap_information WHERE status=1");
    $db->execute();

    $offboarding_interns = 0;
    while ($row = $db->fetch()) {
        $rendering_days = floor(($row["target_rendering_hours"]-$row["rendered_hours"])/9);

        $estimated_weekend_days = floor(($rendering_days/5) * 2);
        $rendering_days += $estimated_weekend_days;

        $est_offboard_date = strtotime($date->getDate()." + ".$rendering_days." days");

        if ($est_offboard_date >= strtotime("current monday") && $est_offboard_date <= strtotime("sunday")) {
            $offboarding_interns++;
        }
    }

    if (isset($_POST["addNew"])) {
        $title = fullTrim($_POST["title"]);
        $start_date = $_POST["start_date"];
        $description = fullTrim($_POST["description"]);
        $progress = $_POST["progress"];

        if (!empty($title) && !empty($start_date) && !empty($description)) {
            $task = array(strtoupper($_SESSION["intern_id"]), $title, $description, $start_date, $progress);

            $db->query("INSERT INTO tasks VALUES(null, :intern_id, :title, :description, :start_date, :progress)");
            $db->insertTask($task);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully added a task.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("dashboard.php");
        exit();
    }

    if (isset($_POST["editTask"])) {
        $id = $_POST["id"];
        $title = fullTrim($_POST["title"]);
        $start_date = $_POST["start_date"];
        $description = fullTrim($_POST["description"]);
        $progress = $_POST["progress"];

        if (!empty($title) && !empty($start_date) && !empty($description)) {
            $task = array($title, $description, $start_date, $progress, $id);

            $db->query("UPDATE tasks SET title=:title, description=:description,
            start_date=:start_date, progress=:progress WHERE id=:id");
            $db->updateTask($task);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully updated the task.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("dashboard.php");
        exit();
    }

    if (isset($_POST["removeTask"])) {
        $id = $_POST["id"];

        if (!empty($id)) {
            $db->query("DELETE FROM tasks WHERE id=:id");
            $db->setId($id);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully removed the task.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("dashboard.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Dashboard");
?>
<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("dashboard");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>

        <div class="row mb-3">
            <div>
                <h3>Dashboard</h3>
            </div> <?php

            if (isset($_SESSION["setup_success"])) { ?>
                <div class="alert alert-success text-success">
                    <?php
                        echo $_SESSION["setup_success"];
                        unset($_SESSION["setup_success"]);
                    ?>
                </div> <?php
            } $bg_color_class = array("bg-primary", "bg-secondary", "bg-red-palette", "bg-success", "bg-orange"); ?>

            <div class="summary">
                <a class="clickable-card" href="attendance.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Rendered Hours
                                </div>
                                <div class="summary-total">
                                    <h3><?= $intern_wsap_info["rendered_hours"]."/".
                                    $intern_wsap_info["target_rendering_hours"] ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-clock <?= $bg_color_class[0] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="attendance.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Remaining Hours
                                </div>
                                <div class="summary-total">
                                    <h3><?= $intern_wsap_info["target_rendering_hours"] -
                                        $intern_wsap_info["rendered_hours"] ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-clock <?= $bg_color_class[1] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="attendance.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Overtime Hours Left since <?= $overtime_hours["start_week_date"] ?>
                                </div>
                                <div class="summary-total">
                                    <h3><?= $overtime_hours["overtime_hours_left"] ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-clock <?= $bg_color_class[2] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="profile.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Days in WSAP
                                </div>
                                <div class="summary-total">
                                    <h3><?php
                                        $date1 = date_create(date("Y-m-d", strtotime($intern_wsap_info["onboard_date"])));
                                        $date2 = date_create(date("Y-m-d"));
                                        $diff = date_diff($date1,$date2);
                                        $days_in_wsap = $diff->format("%a");
                                        echo $days_in_wsap; ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-calendar <?= $bg_color_class[3] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="profile.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Weeks in WSAP
                                </div>
                                <div class="summary-total">
                                    <h3><?= intval($days_in_wsap/7); ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-calendar <?= $bg_color_class[4] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="profile.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2"> <?php
                                    if (empty($intern_wsap_info["offboard_date"])) {
                                        echo "Est. Offboard Date";
                                    } else {
                                        echo "Offboard Date";
                                    } ?>
                                </div>
                                <div class="summary-total">
                                    <h5> <?php
                                    if (empty($intern_wsap_info["offboard_date"])) {
                                        $rendering_days = floor(($intern_wsap_info["target_rendering_hours"]-$intern_wsap_info["rendered_hours"])/9);

                                        $estimated_weekend_days = floor(($rendering_days/5) * 2);
                                        $rendering_days += $estimated_weekend_days;

                                        echo date("j M Y", strtotime($date->getDate()." + ".$rendering_days." days"));
                                    } else {
                                        echo date("j M Y", strtotime($intern_wsap_info["offboard_date"]));
                                    } ?></h5>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-graduation-cap <?= $bg_color_class[0] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="interns.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Total Interns
                                </div>
                                <div class="summary-total">
                                    <h3><?= $total_interns ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-user-group <?= $bg_color_class[1] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="interns.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Active Interns
                                </div>
                                <div class="summary-total">
                                    <h3><?= $active_interns ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-user-tie <?= $bg_color_class[2] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="offboarding_forecast.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Offboarding Interns for this Week
                                </div>
                                <div class="summary-total">
                                    <h3><?= $offboarding_interns ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-graduation-cap <?= $bg_color_class[3] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>

                <a class="clickable-card" href="brands.php" draggable="false">
                    <div class="summary-boxes">
                        <div class="top">
                            <div class="left">
                                <div class="subheader my-2">
                                    Total Websites
                                </div>
                                <div class="summary-total">
                                    <h3><?= $brand_count ?></h3>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-globe <?= $bg_color_class[4] ?> text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>
            </div>

            <div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addNewModalLabel">Add</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-sm-12 col-md-6 user_input my-1">
                                        <label class="mb-2" for="title">Title
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="title" class="form-control" maxLength="32">
                                    </div>
                                    <div class="col-sm-12 col-md-6 user_input my-1">
                                        <label class="mb-2" for="title">Start Date
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="start_date" class="form-control"
                                            value="<?= date("Y-m-d", $date->getDateValue()) ?>">
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="mb-2" for="title">Description
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea type="multiline" name="description" class="form-control"
                                            rows="5" maxLength="512"></textarea>
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label for="progress" class="form-label">Progress</label>
                                        <div class="row align-items-center range-inputs">
                                            <div class="col-sm-12 col-md-3">
                                                <input type="number" class="form-control" min="0" max="100" step="1"
                                                    id="progressInput" name="progress">
                                            </div>
                                            <div class="col-sm-12 col-md-9">
                                                <input type="range" class="form-range" min="0" max="100" step="1"
                                                    id="progressTrackbar" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" name="addNew" class="btn btn-success">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <?php
            $db->query("SELECT * FROM tasks WHERE intern_id=:intern_id");
            $db->setInternid($_SESSION["intern_id"]);
            $db->execute();
            
            $tasks_count = $db->rowCount();

            $db->query("SELECT * FROM tasks WHERE intern_id=:intern_id AND progress = 100");
            $db->setInternid($_SESSION["intern_id"]);
            $db->execute();
            
            $completed_tasks_count = $db->rowCount(); ?>
            <div class="section-content my-2">
                <div class="col-md-12 p-4" id="tasks">
                    <div class="d-lg-flex d-sm-inline-block justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <h4 class="fw-bold m-0 me-2">Tasks and Reminders</h4>
                            <a class="btn btn-secondary btn-sm d-none" href="tasks.php">
                                Show All<i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="d-none">
                            <button class="btn btn-primary mb-2" data-bs-toggle="modal" 
                                data-bs-target="#addNewModal">
                                <i class="fa-solid fa-plus me-2"></i>Add New
                            </button>
                            <button class="btn btn-success mb-2" <?php
                                if ($completed_tasks_count == 0 || !isDATEnabled()) { ?>
                                    disabled <?php
                                } ?>>Submit Completed
                            </button>
                            <button class="btn btn-smoke border-dark mb-2" <?php
                                if ($tasks_count == 0 || !isDATEnabled()) { ?>
                                    disabled <?php
                                } ?>>
                                <i class="fa-solid fa-circle-question me-1"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                title="The on-going tasks will still remain after you submit all."></i>
                                Submit All
                            </button>
                        </div>
                    </div> <?php
                    if (isset($_SESSION["success"])) { ?>
                        <div class="alert alert-success text-success">
                            <?php
                                echo $_SESSION["success"];
                                unset($_SESSION["success"]);
                            ?>
                        </div> <?php
                    }

                    if (isset($_SESSION["failed"])) { ?>
                        <div class="alert alert-danger text-danger">
                            <?php
                                echo $_SESSION["failed"];
                                unset($_SESSION["failed"]);
                            ?>
                        </div> <?php
                    }
                    $record_count = 0; ?>
                    <div class="daily_task"> <?php

                        $intern_db = new Database();

                        $intern_db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, 
                            intern_wsap_information.*, intern_accounts.*,  departments.*
                            FROM intern_personal_information, intern_wsap_information, intern_accounts, departments
                            WHERE intern_personal_information.id = intern_wsap_information.id AND
                            intern_personal_information.id = intern_accounts.id AND
                            intern_wsap_information.department_id = departments.id
                            ORDER BY last_name");
                        $intern_db->execute();

                        while ($birthday_celebrant = $intern_db->fetch()) {
                            if (date("m-d", strtotime($birthday_celebrant["birthdate"])) == date("m-d", $date->getDateValue())) {
                                $record_count++; ?>
                                <div class="task-box position-relative">
                                    <img src="../Assets/img/emojis/confetti-ball_1f38a.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(50% + 64px); top: 20%;">
                                    <img src="../Assets/img/emojis/confetti-ball_1f38a.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(60% + 64px); top: 25%;">
                                    <img src="../Assets/img/emojis/partying-face_1f973.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(50% + 64px); top: 35%; transform: scaleX(-1);">
                                    <img src="../Assets/img/emojis/partying-face_1f973.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(60% + 64px); top: 40%; transform: scaleX(-1);">
                                    <img src="../Assets/img/emojis/party-popper_1f389.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(50% + 64px); top: 50%; transform: scaleX(-1);">
                                    <img src="../Assets/img/emojis/party-popper_1f389.png" class="position-absolute"
                                        style="height: 32px; width: 32px; left: calc(60% + 64px); top: 55%; transform: scaleX(-1);">

                                    <img src="../Assets/img/emojis/confetti-ball_1f38a.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(50% + 64px); top: 20%;">
                                    <img src="../Assets/img/emojis/confetti-ball_1f38a.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(60% + 64px); top:25%;">
                                    <img src="../Assets/img/emojis/partying-face_1f973.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(50% + 64px); top: 35%;">
                                    <img src="../Assets/img/emojis/partying-face_1f973.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(60% + 64px); top: 40%;">
                                    <img src="../Assets/img/emojis/party-popper_1f389.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(50% + 64px); top: 50%;">
                                    <img src="../Assets/img/emojis/party-popper_1f389.png" class="position-absolute"
                                        style="height: 32px; width: 32px; right: calc(60% + 64px); top: 55%;">

                                    <div class="task-box-status">
                                        <h5 class="task-title fw-bold">
                                            Birthday Celebrant
                                        </h5>
                                    </div>
                                    <div class="text-center">
                                        <div class="top">
                                            <img class="img-intern mx-auto" src="<?php {
                                                if ($birthday_celebrant["image"] == null || strlen($birthday_celebrant["image"]) == 0) {
                                                    if ($birthday_celebrant["gender"] == 0) {
                                                        echo "../Assets/img/profile_imgs/default_male.png";
                                                    } else {
                                                        echo "../Assets/img/profile_imgs/default_female.png";
                                                    }
                                                } else {
                                                    echo $birthday_celebrant["image"];
                                                }
                                            } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                                        </div>
                                        <div class="summary-total mt-2 w-fit mx-auto">
                                            <h5 class="mb-0 text-dark fs-regular">
                                                <?= $birthday_celebrant["last_name"].", ".$birthday_celebrant["first_name"] ?>
                                            </h5>
                                            <h6 class="fs-f" style="color: var(--text-2);"><?= $birthday_celebrant["name"] ?></h6>
                                        </div>
                                    </div>
                                </div> <?php
                            }
                        }
                        
                        if (!empty($lts_att)) {
                            $att_date = $lts_att["att_date"];
                        } else {
                            $att_date = "";
                        }

                        if (isTimeInEnabled($att_date) && $intern_wsap_info["status"] == 1 && $i_am_active) {
                            $record_count++; ?>
                            <div class="task-box position-relative">
                                <div class="task-box-status">
                                    <h5 class="task-title fw-bold">
                                        Time in
                                    </h5>
                                    <div class="digi-time text-center d-flex align-items-center justify-content-center">
                                        <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                        width="200" height="90" frameborder="0" seamless>
                                        </iframe>
                                    </div>
                                </div>
                                <div class="task-box-action d-flex justify-content-end align-items-center">
                                    <a class="btn btn-success" href="attendance.php">Time in</a>
                                </div>
                            </div> <?php
                        }

                        if ($remind_time_out && $intern_wsap_info["status"] == 1 && $i_am_active) {
                            $record_count++; ?>
                            <div class="task-box position-relative">
                                <div class="task-box-status">
                                    <h5 class="task-title fw-bold">
                                        Time out
                                    </h5>
                                    <div class="digi-time text-center d-flex align-items-center justify-content-center">
                                        <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                        width="200" height="90" frameborder="0" seamless>
                                        </iframe>
                                    </div>
                                </div>
                                <div class="task-box-action d-flex justify-content-end align-items-center">
                                    <a class="btn btn-danger" href="attendance.php">Time out</a>
                                </div>
                            </div> <?php
                        }

                        $sort = " ORDER BY id DESC";
                        if (!empty($_GET["sort"])) {
                            switch ($_GET["sort"]) {
                                case "1":
                                    $sort = " ORDER BY id DESC";
                                    break;
                                case "2":
                                    $sort = " ORDER BY id";
                                    break;
                            }
                        }
        
                        $conditions = " WHERE intern_id=:intern_id";
        
                        if (!empty($_GET["search"])) {
                            if (strlen($conditions) > 6) {
                                $conditions = $conditions." AND";
                            }
                            $conditions = $conditions." title LIKE CONCAT( '%', :search, '%') OR
                            description LIKE CONCAT( '%', :search, '%')";
                        }
        
                        $query = "SELECT * FROM tasks";
        
                        if (strlen($conditions) > 6) {
                            $db->query($query.$conditions.$sort);
        
                            if (!empty($_GET["search"])) {
                                $db->search($_GET["search"]);
                            }
                        } else {
                            $db->query($query.$sort);
                        }
                        $db->setInternid($_SESSION["intern_id"]);
                        $db->execute();
        
                        while ($row = $db->fetch()) {
                            $record_count++; ?>
                            <div class="modal fade" id="editModal<?= $row["id"] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row["id"] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?= $row["id"] ?>">Update</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                            <div class="modal-body">
                                                <div class="row">
                                                    <input type="text" name="id" class="form-control d-none" value=<?= $row["id"] ?>>
                                                    <div class="col-sm-12 col-md-6 user_input my-1">
                                                        <label class="mb-2" for="title">Title
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" name="title" class="form-control"
                                                            value=<?= $row["title"] ?> maxLength="32">
                                                    </div>
                                                    <div class="col-sm-12 col-md-6 user_input my-1">
                                                        <label class="mb-2" for="title">Start Date
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" name="start_date" class="form-control"
                                                            value="<?= date("Y-m-d", strtotime( $row["start_date"])) ?>">
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="title">Description
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <textarea type="multiline" name="description" class="form-control"
                                                            rows="5" maxLines="10" maxLength="512"><?= $row["description"] ?></textarea>
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label for="progress" class="form-label">Progress</label>
                                                        <div class="row align-items-center range-inputs">
                                                            <div class="col-sm-12 col-md-3">
                                                                <input type="number" class="form-control" min="0" max="100" step="1"
                                                                    id="progressInput" name="progress" value=<?= $row["progress"] ?>>
                                                            </div>
                                                            <div class="col-sm-12 col-md-9">
                                                                <input type="range" class="form-range" min="0" max="100" step="1"
                                                                    id="progressTrackbar" value=<?= $row["progress"] ?>>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="editTask" class="btn btn-success">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="removeTaskModal<?= $row["id"] ?>" tabindex="-1"
                                aria-labelledby="removeTaskModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="removeTaskModalLabel">Remove Task</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                            <div class="modal-body">
                                                <input type="text" name="id" class="form-control d-none" value=<?= $row["id"] ?>>
                                                <div class="task-box position-relative">
                                                    <div class="task-box-status">
                                                        <div>
                                                            <h5 class="task-title fw-bold mb-0">
                                                                <?= $row["title"] ?>
                                                            </h5> <?php
                                                            if ($row["progress"] == 100) { ?>
                                                                <p class="bg-success text-light rounded w-fit px-2 py-1 mt-2 fs-d">
                                                                    Completed
                                                                </p> <?php
                                                            } else { ?>
                                                                <p class="bg-warning rounded w-fit px-2 py-1 mt-2 fs-d">
                                                                    On-going
                                                                </p> <?php
                                                            } ?>
                                                        </div>
                                                    </div>
                                                    <div class="scrollbar scrollbar-primary fs-e mb-5">
                                                        <?= $row["description"] ?>
                                                    </div>
                                                    <div class="top-right">
                                                        <div class="circular-progress" value="<?= $row["progress"] ?>">
                                                            <span class="progress-value"><?= $row["progress"]."%" ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="task-box-action">
                                                        <span><?= date("F j, Y", strtotime($row["start_date"])) ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="removeTask" class="btn btn-danger">Remove</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="task-box position-relative">
                                <div class="task-box-status">
                                    <div>
                                        <h5 class="task-title fw-bold mb-0">
                                            <?= $row["title"] ?>
                                        </h5> <?php
                                        if ($row["progress"] == 100) { ?>
                                            <p class="bg-success text-light rounded w-fit px-2 py-1 mt-2 fs-d">
                                                Completed
                                            </p> <?php
                                        } else { ?>
                                            <p class="bg-warning rounded w-fit px-2 py-1 mt-2 fs-d">
                                                On-going
                                            </p> <?php
                                        } ?>
                                    </div>
                                </div>
                                <div class="scrollbar scrollbar-primary fs-e mb-5">
                                    <?= $row["description"] ?>
                                </div>
                                <div class="top-right">
                                    <div class="circular-progress" value="<?= $row["progress"] ?>">
                                        <span class="progress-value"><?= $row["progress"]."%" ?></span>
                                    </div>
                                </div>
                                <div class="task-box-action d-flex align-items-center">
                                    <span><?= date("F j, Y", strtotime($row["start_date"])) ?></span>
                                    <div class="ms-auto">
                                        <button class="btn btn-success btn-sm" <?php
                                            if ($row["progress"] != 100 || !isDATEnabled()) { ?>
                                                disabled <?php
                                            } ?>>
                                            Submit
                                        </button>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $row["id"] ?>">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#removeTaskModal<?= $row["id"] ?>">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div> <?php
                        } ?>
                    </div> <?php
                    if ($record_count == 0) { ?>
                        <div class="w-100 text-center my-5">
                            <h3>Empty</h3>
                        </div> <?php
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    const circularProgressItems = document.querySelectorAll(".circular-progress");

    var progressEndValues = [];
    for (const circularProgress of circularProgressItems) {
        const value = circularProgress.getAttribute("value");
        progressEndValues.push(value);
    }

    for (var index = 0; index < circularProgressItems.length; index++) {
        const circularProgress = circularProgressItems[index];
        const progressValue = circularProgress.querySelector(".progress-value");

        progressValue.textContent = `${progressEndValues[index]}%`
        circularProgress.style.background = `conic-gradient(#0d6efd ${progressEndValues[index] * 3.6}deg, #fff 0deg)`
    }

    const rangeInputs = document.querySelectorAll(".range-inputs");

    for (const rangeInput of rangeInputs) {        
        const progressInput = rangeInput.children[0].children[0];
        const progressTrackbar = rangeInput.children[1].children[0];

        if (progressInput.value.length == 0) {
            progressInput.value = 0;
        }

        progressInput.addEventListener("change", function (e) {
            if (progressInput.value > 100) {
                progressInput.value = 100;
            }
            progressTrackbar.value = progressInput.value;
        });

        progressTrackbar.addEventListener("change", function (e) {
            progressInput.value = progressTrackbar.value;
        });
    }

</script>
<?php
    require_once "../Templates/footer.php";
?>