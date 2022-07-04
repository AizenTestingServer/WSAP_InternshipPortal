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

    $db->query("SELECT COUNT(*) AS total_interns FROM intern_personal_information");
    $db->execute();

    $total_interns = 0;
    if ($value = $db->fetch()) {
        $total_interns = $value["total_interns"];
    }

    $db->query("SELECT COUNT(*) AS active_interns FROM intern_wsap_information WHERE status = 1");
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
    
    if ($admin_roles_count != 0) {
        $overtime_hours_left = 15;
    } else {
        $overtime_hours_left = 10;
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

    if (isset($_POST["goToAttendance"])) {
        redirect("attendance.php");
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
            } ?>
            
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
                                <i class="fa-solid fa-clock bg-primary text-light circle"></i>
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
                                <i class="fa-solid fa-clock bg-secondary text-light circle"></i>
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
                                <i class="fa-solid fa-calendar bg-success text-light circle"></i>
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
                                <i class="fa-solid fa-calendar bg-red-palette text-light circle"></i>
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
                                        $rendering_days = round(($intern_wsap_info["target_rendering_hours"]-$intern_wsap_info["rendered_hours"])/8);
                                        $estimated_weekends = ceil(($rendering_days/5) * 2);
                                        $rendering_days += $estimated_weekends + 1;
                                        
                                        echo date("j M Y", strtotime($date->getDate()." + ".$rendering_days." days"));
                                    } else {
                                        echo date("j M Y", strtotime($intern_wsap_info["offboard_date"]));
                                    } ?></h5>
                                </div>
                            </div>
                            <div class="right">
                                <i class="fa-solid fa-graduation-cap bg-primary text-light circle"></i>
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
                                <i class="fa-solid fa-globe bg-secondary text-light circle"></i>
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
                                <i class="fa-solid fa-user-group bg-success text-light circle"></i>
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
                                <i class="fa-solid fa-user-tie bg-red-palette text-light circle"></i>
                            </div>
                        </div>
                        <div class="bottom">

                        </div>
                    </div>
                </a>
            </div>
            
            <div class="section-content mt-2">
                <div class="col-md-12 p-4" id="tasks">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="mt-4 fw-bold">Tasks and Reminders</h4>
                        <!-- <div>
                            <button class="btn btn-outline-dark fs-c">
                                <i class="fa-solid fa-plus me-2"></i>Add New
                            </button>
                        </div> -->

                    </div>

                    <?php $record_count = 0; ?>
                    <div class="daily_task"> <?php
                        if (isTimeInEnabled($lts_att["att_date"]) && $intern_wsap_info["status"] == 1) {
                            $record_count++; ?>
                            <div class="task-box">
                                <div class="task-box-status">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="task-title fw-bold">
                                            Time in
                                        </h6>
                                    </div>
                                    <div class="col-md-12 text-center">
                                        <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                        width="200" height="90" frameborder="0" seamless>
                                        </iframe>
                                    </div>
                                </div>
                                <div class="task-box-action mt-2 d-flex justify-content-end align-items-center">
                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                        <button type="submit" name="goToAttendance" class="btn btn-success">Time in</button>
                                    </form>
                                </div>
                            </div> <?php
                        }
                        
                        if ($remind_time_out &&$intern_wsap_info["status"] == 1) {
                            $record_count++; ?>
                            <div class="task-box">
                                <div class="task-box-status">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="task-title fw-bold">
                                            Time out
                                        </h6>
                                    </div>
                                    <div class="col-md-12 text-center">
                                        <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                        src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                        width="200" height="90" frameborder="0" seamless>
                                        </iframe>
                                    </div>
                                </div>
                                <div class="task-box-action mt-2 d-flex justify-content-end align-items-center">
                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                        <button type="submit" name="goToAttendance" class="btn btn-danger">Time out</button>
                                    </form>
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
<?php
    require_once "../Templates/footer.php";
?>