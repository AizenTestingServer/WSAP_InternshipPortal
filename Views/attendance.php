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

    $db->query("SELECT * FROM intern_wsap_information
    WHERE id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();

    $intern_wsap_info = $db->fetch();

    $i_am_active = isActiveIntern($intern_wsap_info["onboard_date"], $intern_wsap_info["offboard_date"], $date->getDate());

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

    if (isset($_SESSION["intern_id"])) {
        $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1;");
        $db->setInternId($_SESSION["intern_id"]);
        $db->execute();
        $lts_att = $db->fetch();

        if (!empty($lts_att) && $date->getDate() != $lts_att["att_date"]) {
            if (empty($lts_att["time_out"])) {
                $attendance = array(
                    "NTO",
                    $lts_att["id"]
                );

                $db->query("UPDATE attendance SET time_out=:time_out WHERE id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();
            }
        }

        $time_out_enabled = false;
        if ($db->rowCount() != 0 && $date->getDateValue() == strtotime($lts_att["att_date"])) {
            $time_out_enabled = isTimeOutEnabled($lts_att["time_in"], $lts_att["time_out"]);
        }

        $overtime_time_out_enabled = false;
        if ($db->rowCount() != 0 && $date->getDateValue() == strtotime($lts_att["att_date"])) {
            $overtime_time_out_enabled = isTimeOutOvertimeEnabled($lts_att["time_out"]);
        }

        if (isset($_POST["timeIn"]) && isset($_FILES["time_in_gps_image"])) {
            $image_name = $_FILES["time_in_gps_image"]["name"];
            $image_size = $_FILES["time_in_gps_image"]["size"];
            $tmp_name = $_FILES["time_in_gps_image"]["tmp_name"];
            $error = $_FILES["time_in_gps_image"]["error"];

            if (!empty($image_name)) {
                if ($error == 0) {
                    $img_ex = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                    $allowed_exs = array("jpg", "jpeg", "png");
        
                    if (in_array($img_ex, $allowed_exs)) {
                        $output_dir = "../Assets/img/gps_images/";
                        $folder_name = strtoupper($_SESSION["intern_id"]);

                        $image_name = date("m-d-y")."_".$folder_name."_Time-in.".$img_ex;

                        $image_path = $output_dir.$folder_name."/".$image_name;

                        if (!file_exists($output_dir.$folder_name)) {
                            @mkdir($output_dir.$folder_name, 0777);
                        }

                        move_uploaded_file($tmp_name, $image_path);

                        $upload_image = array(
                            strtoupper($_SESSION["intern_id"]),
                            $image_path,
                            $image_name
                        );

                        if ($date->getDateTimeValue() > $date->morning_briefing() && $date->getDateTimeValue() < $date->morning_shift_end()) {
                            $time_in = $date->getTime()." L";
                        } else {
                            $time_in = $date->getTime();
                        }
            
                        $attendance = array(
                            strtoupper($_SESSION["intern_id"]),
                            $date->getDate(),
                            $time_in,
                            null,
                            0,
                            0,
                            0,
                            $image_path,
                            null
                        );
                        
                        $db->query("INSERT INTO attendance
                        VALUES (NULL, :intern_id, :att_date, :time_in, :time_out,
                        :regular_hours, :ot_hours, :rendered_hours,
                        :time_in_gps_image, :time_out_gps_image);");
                        $db->timeIn($attendance);
                        $db->execute();
                        $db->closeStmt();
                    } else {
                        $_SESSION["error"] = "The file must be an image!";
                    }
                } else {
                    $_SESSION["error"] = "There is an error occurred!";
                }
            } else {
                $_SESSION["error"] = "You must select your GPS image for the time in first!";
            }
        
            redirect("attendance.php");
            exit();
        }
    
        if (isset($_POST["timeOut"]) && isset($_FILES["time_out_gps_image"])) {
            $image_name = $_FILES["time_out_gps_image"]["name"];
            $image_size = $_FILES["time_out_gps_image"]["size"];
            $tmp_name = $_FILES["time_out_gps_image"]["tmp_name"];
            $error = $_FILES["time_out_gps_image"]["error"];

            if (!empty($image_name)) {
                if ($error == 0) {
                    $img_ex = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                    $allowed_exs = array("jpg", "jpeg", "png");
        
                    if (in_array($img_ex, $allowed_exs)) {
                        $output_dir = "../Assets/img/gps_images/";
                        $folder_name = strtoupper($_SESSION["intern_id"]);

                        $image_name = date("m-d-y")."_".$folder_name."_Time-out.".$img_ex;

                        $image_path = $output_dir.$folder_name."/".$image_name;

                        if (!file_exists($output_dir.$folder_name)) {
                            @mkdir($output_dir.$folder_name, 0777);
                        }

                        move_uploaded_file($tmp_name, $image_path);

                        $upload_image = array(
                            strtoupper($_SESSION["intern_id"]),
                            $image_path,
                            $image_name
                        );

                        if (!empty($lts_att["time_in"]) && (empty($lts_att["time_out"]) || $lts_att["time_out"] == "NTO")) {
                            $time_in = $lts_att["time_in"];
                            $time_out = $date->getTime();

                            if (strlen($time_in) > 8) {
                                $time_in = trim(substr($time_in, 0, 8));
                            }
                        
                            if (isMorningShift($time_in, $date->getTime())) {
                                $time_out =  $time_out." MS";
                            }
                            if (isAfternoonShift($time_in, $date->getTime())) {
                                $time_out =  $time_out." AS";
                            }
                            if (isLateTimeOut($date->getTime())) {
                                $time_out =  $time_out." L";
                            }
                            
                            $attendance = array(
                                $time_out,
                                $lts_att["id"]
                            );

                            $db->query("UPDATE attendance SET time_out=:time_out WHERE id=:id");
                            $db->timeOut($attendance);
                            $db->execute();
                            $db->closeStmt();
                            
                            $time_out_gps_image = array(
                                $image_path,
                                $lts_att["id"]
                            );
                            
                            $db->query("UPDATE attendance SET time_out_gps_image=:time_out_gps_image WHERE id=:id");
                            $db->setTimeOutGPSImage($time_out_gps_image);
                            $db->execute();
                            $db->closeStmt();
                            
                            $time_out = $date->getTime();
                                                
                            if (isMorningShift($time_in, $time_out) || isAfternoonShift($time_in, $time_out)) {
                                $rendered_hours = 4;
                            } else {
                                $rendered_hours = 8;
                            }
                            
                            $attendance = array(
                                $rendered_hours - $rendered_overtime_hours,
                                $rendered_overtime_hours,
                                $rendered_hours,
                                $lts_att["id"]
                            );

                            $db->query("UPDATE attendance
                            SET regular_hours=:regular_hours, ot_hours=:ot_hours, rendered_hours=:rendered_hours
                            WHERE id=:id");
                            $db->setAttHours($attendance);
                            $db->execute();
                            $db->closeStmt();

                            $db->query("SELECT * FROM intern_wsap_information WHERE id=:intern_id;");
                            $db->setInternId($_SESSION["intern_id"]);
                            $db->execute();
                            $wsap_info = $db->fetch();
                            
                            $rendered_hours += $wsap_info["rendered_hours"];

                            $computed_rendered_hours = array(
                                $rendered_hours,
                                $_SESSION["intern_id"]
                            );
                
                            $db->query("UPDATE intern_wsap_information SET rendered_hours=:rendered_hours 
                            WHERE id=:intern_id");
                            $db->updateRenderedHours($computed_rendered_hours);
                            $db->execute();
                            $db->closeStmt();

                            if ($rendered_hours >= $wsap_info["target_rendering_hours"]) {
                                $offboard_date = date("Y-m-d", strtotime($date->getDate()));

                                $offboard = array(
                                    $offboard_date,
                                    2,
                                    $_SESSION["intern_id"]
                                );
                
                                $db->query("UPDATE intern_wsap_information
                                SET offboard_date=:offboard_date, status=:status
                                WHERE id=:intern_id");
                                $db->setOffboard($offboard);
                                $db->execute();
                                $db->closeStmt();
                            } else {
                                $offboard_date = null;
                
                                $offboard = array(
                                    $offboard_date,
                                    1,
                                    $_SESSION["intern_id"]
                                );
                
                                $db->query("UPDATE intern_wsap_information
                                SET offboard_date=:offboard_date, status=:status
                                WHERE id=:intern_id");
                                $db->setOffboard($offboard);
                                $db->execute();
                                $db->closeStmt();
                            }

                            $computed_overtime_hours = array(
                                $computed_overtime_hours_left,
                                $_SESSION["intern_id"],
                                $start_week_date
                            );
                        }
                    } else {
                        $_SESSION["error"] = "The file must be an image!";
                    }
                } else {
                    $_SESSION["error"] = "There is an error occurred!";
                }
            } else {
                $_SESSION["error"] = "You must select your GPS image for the time out first!";
            }
            
            redirect("attendance.php");
            exit();
        }

        if (isset($_POST["timeOutOvertime"])) {
            if (!empty($lts_att["time_in"])) {
                $time_in = $lts_att["time_in"];
                $time_out = $date->getTime();

                if (strlen($time_in) > 8) {
                    $time_in = trim(substr($time_in, 0, 8));
                }
               
                if (isMorningShift($time_in, $date->getTime())) {
                    $time_out =  $time_out." MS";
                }
                if (isAfternoonShift($time_in, $date->getTime())) {
                    $time_out =  $time_out." AS";
                }
                if (isOvertime($date->getTime())) {
                    $time_out =  $time_out." OT";
                }
                
                $attendance = array(
                    $time_out,
                    $lts_att["id"]
                );

                $db->query("UPDATE attendance SET time_out=:time_out WHERE id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();
                
                $time_out = $date->getTime();

                $rendered_overtime_hours = 0;
                if (isOvertime($time_out)) {
                    $dt_time_out_start = new DateTime(date("G:i", $date->time_out_start()));
                    $dt_time_out = new DateTime(date("G:i", strtotime($time_out)));
                    $rendered_overtime_hours += $dt_time_out_start->diff($dt_time_out)->format("%h");
                    $rendered_minutes = $dt_time_out_start->diff($dt_time_out)->format("%i");
                    $rendered_overtime_hours += round($rendered_minutes/60, 1);

                    if ($rendered_overtime_hours > $overtime_hours["overtime_hours_left"]) {
                        $rendered_overtime_hours = $overtime_hours["overtime_hours_left"];
                    }

                    if ($rendered_overtime_hours > 4) {
                        $rendered_overtime_hours = 4;
                    }
                }
                
                $computed_overtime_hours_left = $overtime_hours["overtime_hours_left"] - $rendered_overtime_hours;
                
                $attendance = array(
                    $rendered_overtime_hours,
                    $lts_att["id"]
                );

                $db->query("UPDATE attendance SET ot_hours=:ot_hours WHERE id=:id");
                $db->setOTHours($attendance);
                $db->execute();
                $db->closeStmt();

                $computed_overtime_hours = array(
                    $computed_overtime_hours_left,
                    $_SESSION["intern_id"],
                    $start_week_date
                );
    
                $db->query("UPDATE overtime_hours SET overtime_hours_left=:overtime_hours_left 
                WHERE intern_id=:intern_id AND start_week_date=:start_week_date");
                $db->updateOvertimeData($computed_overtime_hours);
                $db->execute();
                $db->closeStmt();
            }

            redirect("attendance.php");
            exit();
        }
    }

    require_once "../Templates/header_view.php";
    setTitle("My Attendance");
?>

<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("attendance");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
            <div class="row bg-orange m-2 mt-4" style="border-radius: 18px;">
                <div class="col-md-12 p-1 pt-2 text-center">
                    <h5 class="fw-bold text-light mx-4 mb-1">Attendance Legend</h5>
                    <ul class="attendance_legend m-0">
                        <li class="bg-morning text-light" style="border-radius: 18px 18px 0 0;">MS - Morning Shift</li>
                        <li class="bg-afternoon text-light">AS - Afternoon Shift</li>
                        <li class="bg-indigo text-light">OT - Overtime</li>
                        <li class="bg-warning">L - Late | NTO - No Time out</li>
                        <li class="bg-danger text-light">AU - Absent Unexcused</li>
                        <li class="bg-primary text-light" style="border-radius: 0 0 18px 18px;">AE - Absent Excused</li>
                    </ul>
                </div>
            </div>
            <div class="row bg-orange m-2" style="border-radius: 18px;">
                <div class="col-md-12 p-1 pt-2 text-center">
                    <h5 class="fw-bold text-light mx-4 mb-1">Schedule Guide</h5>
                    <ul class="attendance_legend m-0">
                        <li class="bg-success text-light" style="border-radius: 18px 18px 0 0;">
                            Regular Time in - <?= regularTimeInSchedule() ?>
                        </li>
                        <li class="bg-warning">
                            Late Time in - <?= lateTimeInSchedule() ?>
                        </li>
                        <li class="bg-success text-light">
                            Regular Time out - <?= regularTimeOutSchedule() ?>
                        </li>
                        <li class="bg-warning">
                            Late Time out - <?= lateTimeOutSchedule() ?>
                        </li>
                        <li class="bg-morning text-light">
                            MS Time out - <?= morningShiftTimeOutSchedule() ?>
                        </li>
                        <li class="bg-afternoon text-light">
                            AS Time in - <?= afternoonShiftTimeInSchedule() ?>
                        </li>
                        <li class="bg-indigo text-light" style="border-radius: 0 0 18px 18px;">
                            OT Time out - <?= overTimeTimeOutSchedule() ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>My Attendance</h3>
            </div>
        </div>

        <div>
            <div id="time-in-time-out-layout" class="d-md-flex d-sm-inline-block">
                <div class="mt-2">
                    <?php
                        if (isset($_SESSION["intern_id"])) {
                            if (!empty($lts_att)) {
                                $att_date = $lts_att["att_date"];
                            } else {
                                $att_date = "";
                            }

                            if (isTimeInEnabled($att_date) && $intern_wsap_info["status"] == 1 && $i_am_active) {
                                time_in_enabled();
                            } else {
                                time_in_disabled();
                            }
                            
                            if ($time_out_enabled && $intern_wsap_info["status"] == 1 && $i_am_active) {
                                time_out_enabled();
                            } else {
                                time_out_disabled();
                            }
                            
                            if ($overtime_time_out_enabled && $intern_wsap_info["status"] == 1 && $i_am_active) {
                                time_out_overtime_enabled();
                            } else {
                                time_out_overtime_disabled();
                            }
                        }
                    ?>
                </div>
            </div> <?php

            if ($intern_wsap_info["status"] != 1) { ?>
                <div class="w-fit my-2 ms-2">
                    <p class="text-danger fw-bold">Only an active intern can time in and time out.</p>
                </div> <?php
            } else { ?>
                <div class="w-fit my-2 ms-2">
                    <h6 class="<?php
                        if ($overtime_hours["overtime_hours_left"] == $overtime_hours_left) {
                            ?> text-success <?php
                        } else if ($overtime_hours["overtime_hours_left"] == 0 ||
                            $overtime_hours["overtime_hours_left"]  > $overtime_hours_left) {
                            ?> text-danger <?php
                        } else {
                            ?> text-primary <?php
                        }
                    ?>">
                        <b><?= $overtime_hours["overtime_hours_left"] ?> Overtime Hours Left</b>
                        since <?= $overtime_hours["start_week_date"] ?>
                        <i>(Resets every <?= ucwords($day) ?>)</i>.
                    </h6>
                </div> <?php
            }
            if (isset($_SESSION["error"])) { ?>
                <div class="alert alert-danger attendance-alert text-danger my-2">
                    <?php
                        echo $_SESSION["error"];
                        unset($_SESSION["error"]);
                    ?>
                </div> <?php
            }
            ?>
        </div>

        <!-- Time in Modal -->
        <div class="modal fade" id="timeInModal" tabindex="-1" aria-labelledby="timeInModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeInModalLabel">Time in</h5>
                        <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                            <i class="fa-solid fa-close"></i>
                        </button>
                    </div>

                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="w-100 text-center">
                                <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                width="200" height="90" frameborder="0" seamless>
                                </iframe>
                                <h6 class="fw-bold my-2">GPS Image</h6>
                                <img class="w-75 my-3" id="time-in-gps-image" src="../Assets/img/no_image_found.jpeg">
                                <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                                    onchange="loadFileTimeIn(event)" name="time_in_gps_image" style="max-width: 350px;">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" name="timeIn" class="btn btn-success">Time in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Time out Modal -->
        <div class="modal fade" id="timeOutModal" tabindex="-1" aria-labelledby="timeOutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeOutModalLabel">Time Out</h5>
                        <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                            <i class="fa-solid fa-close"></i>
                        </button>
                    </div>

                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="w-100 text-center">
                                <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                                src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                                width="200" height="90" frameborder="0" seamless>
                                </iframe>
                                <h6 class="fw-bold my-2">GPS Image</h6>
                                <img class="w-75 my-3" id="time-out-gps-image" src="../Assets/img/no_image_found.jpeg">
                                <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                                    onchange="loadFileTimeOut(event)" name="time_out_gps_image" style="max-width: 350px;">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" name="timeOut" class="btn btn-smoke">Time out</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Time out (Overtime) Modal -->
        <div class="modal fade" id="timeOutOvertimeModal" tabindex="-1" aria-labelledby="timeOutOvertimeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeOutOvertimeModalLabel">Time Out (Overtime)</h5>
                        <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                            <i class="fa-solid fa-close"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="w-100 text-center">
                            <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                            src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                            width="200" height="90" frameborder="0" seamless>
                            </iframe>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <button type="submit" name="timeOutOvertime" class="btn btn-indigo">Time out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="att-ctrl-buttons d-flex align-items-center mb-3">
            <div class="dropdown align-center me-2">
                <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                    if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                        echo "Custom";
                    } else {
                        echo "All Records";
                    } ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li>
                        <a class="dropdown-item btn-smoke" href="attendance.php">
                            All Records
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item btn-smoke"
                            href="attendance.php?month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>">
                            Custom
                        </a>
                    </li>
                </ul>
            </div> <?php
            if (!empty($_GET["month"]) && !empty($_GET["year"])) { ?>
                <div class="dropdown align-center me-2">
                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false" name="department">
                        <?= $_GET["month"] ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                        foreach (getMonths() as $value) { ?>
                            <li>
                                <a class="dropdown-item btn-smoke"
                                    href="attendance.php?month=<?= $value ?>&year=<?= $_GET["year"] ?>">
                                    <?= $value ?>
                                </a>
                            </li> <?php
                        } ?>
                    </ul>
                </div>
                <div class="dropdown align-center me-2">
                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false" name="department">
                        <?= $_GET["year"] ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                        for ($i = 2018; $i <= $date->getYear(); $i++) { ?>
                            <li>
                                <a class="dropdown-item btn-smoke"
                                    href="attendance.php?month=<?= $_GET["month"] ?>&year=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li> <?php
                        } ?>
                    </ul>
                </div> <?php
            }
            
            $nto_array = array($_SESSION["intern_id"], "NTO");
            $db->query("SELECT COUNT(*) as count FROM attendance
            WHERE intern_id=:intern_id AND time_out=:time_out");
            $db->selectInternIdAndTimeOut($nto_array);
            $db->execute();
            $nto_value = $db->fetch(); ?>
                                
            <div class="w-fit ms-auto"> <?php
                if ($nto_value["count"] == 0) { ?>
                    <a class="btn btn-pdf"
                        href="preview_pdf.php?intern_id=<?= strtoupper($_SESSION["intern_id"]) ?>"
                        target="preview_pdf.php?intern_id=<?= strtoupper($_SESSION["intern_id"]) ?>">
                        Preview DTR as PDF
                    </a> <?php
                } else { ?>
                    <a class="btn btn-pdf disabled">
                        Preview DTR as PDF
                    </a> <?php
                } ?>
            </div>
        </div> <?php
                
        if ($nto_value["count"] != 0) { ?>
            <div class="att-ctrl-buttons">
                <p class="text-danger w-fit ms-auto fw-bold">Please settle the NTO first.</p>
            </div> <?php
        } ?>

        <table id="attendance-table" class="table caption-top fs-d text-center mt-2">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date</th>
                    <th scope="col">Day</th>
                    <th scope="col">Time in</th>
                    <th scope="col">Time out</th>
                    <th scope="col">Regular Hours</th>
                    <th scope="col">OT Hours</th>
                    <th scope="col">Valid Rendered Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($_SESSION["intern_id"])) {                
                    if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                        $month_year = array($_GET["month"], $_GET["year"]);
                        
                        $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id AND
                        att_date LIKE CONCAT(:month, '%', :year) ORDER BY id DESC");
                        $db->setMonthYear($month_year);
                    } else {
                        $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC");
                    }
                    $db->setInternId($_SESSION["intern_id"]);
                    $db->execute();

                    $count = 0;
                    
                    while ($row = $db->fetch()) {
                        $count++;  ?>
                        <tr>
                            <div class="modal fade" id="gpsImageModal<?= $row["id"] ?>" tabindex="-1"
                                aria-labelledby="gpsImageModalModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="modal-title" id="gpsImageModalModalLabel">
                                                <h5><?= $row["att_date"]." | ".date("l", strtotime($row["att_date"])) ?></h5>
                                            </div>
                                            <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-close"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-sm-12 col-md-6 text-center p-1">
                                                    <h6 class="fw-bold">TIME IN</h6>
                                                    <img class="w-100 mb-2" src="<?=  $row["time_in_gps_image"] ?>"
                                                    onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                                    <div class="d-flex align-items-center"> <?php
                                                        if (strlen($row["time_in"]) > 0) {
                                                            if (isAU($row["time_in"])) { ?>
                                                                <p class="bg-danger text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else if (isAE($row["time_in"])) { ?>
                                                                <p class="bg-primary text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else if (strlen($row["time_out"]) > 0 && isMS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                                                <p class="bg-morning text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else if (strlen($row["time_out"]) > 0 && isAS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                                                <p class="bg-afternoon text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else if (isOD($row["time_in"])) { ?>
                                                                <p class="bg-dark text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else if (isL($row["time_in"])) { ?>
                                                                <p class="bg-warning text-dark rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }  else { ?>
                                                                <p class="bg-success text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_in"] ?>
                                                                </p> <?php
                                                            }
                                                        } ?>
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 col-md-6 text-center p-1 mt-3 mt-md-0">
                                                    <h6 class="fw-bold">TIME OUT</h6>
                                                    <img class="w-100 mb-2" src="<?=  $row["time_out_gps_image"] ?>"
                                                    onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                                    <div class="d-flex align-items-center"> <?php
                                                        if (strlen($row["time_out"]) > 0) {
                                                            if (isAU($row["time_out"])) { ?>
                                                                <p class="bg-danger text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isAE($row["time_out"])) { ?>
                                                                <p class="bg-primary text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isOT($row["time_out"])) { ?>
                                                                <p class="bg-indigo text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isMS($row["time_out"])) { ?>
                                                                <p class="bg-morning text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isAS($row["time_out"])) { ?>
                                                                <p class="bg-afternoon text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isOD($row["time_out"])) { ?>
                                                                <p class="bg-dark text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else if (isL($row["time_out"]) || isNTO($row["time_out"])) { ?>
                                                                <p class="bg-warning text-dark rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }  else { ?>
                                                                <p class="bg-success text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                    <?= $row["time_out"] ?>
                                                                </p> <?php
                                                            }
                                                        } else { ?>
                                                            <p class="bg-secondary text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                                Pending
                                                            </p> <?php
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <th scope="row"><?= $count ?></th>
                            <td><?= $row["att_date"] ?></td>
                            <td><?= date("l", strtotime($row["att_date"])) ?></td>
                            <td> <?php
                                if (strlen($row["time_in"]) > 0) {
                                    if (isAU($row["time_in"])) { ?>
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (isAE($row["time_in"])) { ?>
                                        <p class="bg-primary text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (strlen($row["time_out"]) > 0 && isMS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                        <p class="bg-morning text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (strlen($row["time_out"]) > 0 && isAS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                        <p class="bg-afternoon text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (isOD($row["time_in"])) { ?>
                                        <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (isL($row["time_in"])) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }
                                } ?>
                            </td>
                            <td> <?php
                                if (strlen($row["time_out"]) > 0) {
                                    if (isAU($row["time_out"])) { ?>
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isAE($row["time_out"])) { ?>
                                        <p class="bg-primary text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isOT($row["time_out"])) { ?>
                                        <p class="bg-indigo text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isMS($row["time_out"])) { ?>
                                        <p class="bg-morning text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isAS($row["time_out"])) { ?>
                                        <p class="bg-afternoon text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isOD($row["time_out"])) { ?>
                                        <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if (isL($row["time_out"]) || isNTO($row["time_out"])) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 py-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }
                                } else { ?>
                                    <p class="bg-secondary text-light rounded w-fit m-auto px-2 py-1">
                                        Pending
                                    </p> <?php
                                } ?>
                            </td>
                            <td><?= $row["regular_hours"] ?></td>
                            <td><?= $row["ot_hours"] ?></td>
                            <td><?= $row["rendered_hours"] ?></td>
                            <td>
                                <div class="w-fit p-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                    title="View GPS Image">
                                    <button class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal"  data-bs-target="#gpsImageModal<?= $row["id"] ?>">
                                        <i class="fa-solid fa-image fs-a"></i>
                                    </button>
                                </div>
                            </td>
                        </tr> <?php
                    }
                } ?>
            </tbody>
        </table> <?php
        if ($db->rowCount() == 0) { ?>
            <div class="att-no-record text-center my-5">
                <h3>No Record</h3>
            </div> <?php
        } ?>
    </div>
</div>
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    var loadFileTimeIn = function (event) {
        var timeInGPSImage = document.getElementById("time-in-gps-image");
        timeInGPSImage.src = URL.createObjectURL(event.target.files[0]);
        timeInGPSImage.onload = function () {
            URL.revokeObjectURL(timeInGPSImage.src)
        }
    };

    var loadFileTimeOut = function (event) {
        var timeOutGPSImage = document.getElementById("time-out-gps-image");
        timeOutGPSImage.src = URL.createObjectURL(event.target.files[0]);
        timeOutGPSImage.onload = function () {
            URL.revokeObjectURL(timeOutGPSImage.src)
        }
    };
</script>
<?php
    require_once "../Templates/footer.php";
?>