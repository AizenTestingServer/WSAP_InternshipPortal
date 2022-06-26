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

    if (isset($_SESSION["intern_id"])) {
        $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1;");
        $db->setInternId($_SESSION["intern_id"]);
        $db->execute();
        $lts_att = $db->fetch();

        if ($date->getDate() != $lts_att["att_date"]) {
            if (empty($lts_att["time_out"])) {
                $attendance = array(
                    "No Time out",
                    $_SESSION["intern_id"],
                    $lts_att["id"]
                );

                $db->query("UPDATE attendance SET time_out=:time_out 
                WHERE intern_id=:intern_id && id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();
            }
        }

        $time_in_enabled = false;
        $time_out_enabled = false;
        if ($db->rowCount() != 0 && $date->getDateValue() == strtotime($lts_att["att_date"])) {
            if (!empty($lts_att["time_out"]) || atMorningShift() || atAfternoonShift() ||
            atOvertime() || atEndOfDay() || atAfternoonTimeIn($lts_att["time_in"])) {
                $time_out_enabled = false;
            } else {
                $time_out_enabled = true;
            }
        } else {
            if (($date->getDateTimeValue() >= $date->time_in_start() &&  $date->getDateTimeValue() < $date->time_in_end()) ||
            ($date->getDateTimeValue() >= $date->morning_shift_end() && $date->getDateTimeValue() < $date->afternoon_shift_start())) {
                $time_in_enabled = true;
            }
        }

        if (isset($_POST["timeIn"])) { 
            if ($date->getDateTimeValue() > $date->morning_briefing() &&
            $date->getDateTimeValue() < $date->morning_shift_end()) {
                $attendance = array(
                    strtoupper($_SESSION["intern_id"]),
                    $date->getDate(),
                    $date->getTime()." Late",
                    null,
                );
            } else {
                $attendance = array(
                    strtoupper($_SESSION["intern_id"]),
                    $date->getDate(),
                    $date->getTime(),
                    null,
                );
            }

            if ($db->rowCount() != 0 && $date->getDateValue() == strtotime($lts_att["att_date"])) {
                $_SESSION['error'] = "You are already timed in!";
                redirect('attendance.php');
                exit();
            }
            
            $db->query("INSERT INTO attendance
            VALUES (NULL, :intern_id, :att_date, :time_in, :time_out);");
            $db->timeIn($attendance);
            $db->execute();
            $db->closeStmt();
        
            redirect('attendance.php');
            exit();
        }
    
        if (isset($_POST["timeOut"])) {
            if (!empty($lts_att["time_in"]) && empty($lts_att["time_out"])) {
                if ($date->getDateTimeValue() >= $date->time_out_overtime_start()) {
                    $attendance = array(
                        $date->getTime()." Overtime",
                        $_SESSION["intern_id"],
                        $lts_att["id"]
                    );
                } else {
                    $attendance = array(
                        $date->getTime(),
                        $_SESSION["intern_id"],
                        $lts_att["id"]
                    );
                }
    
                $db->query("UPDATE attendance SET time_out=:time_out 
                WHERE intern_id=:intern_id && id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();

                $db->query("SELECT * FROM intern_wsap_information WHERE id=:intern_id;");
                $db->setInternId($_SESSION["intern_id"]);
                $db->execute();
                $lts_att = $db->fetch();
                
                $time_in = $lts_att["time_in"];
                $time_out = $date->getTime();

                if (strlen($time_out) > 8) {
                    $time_out = substr($time_out, 0, 8);
                }

                $time_in = new DateTime(date('G:i', strtotime($time_in)));
                $time_out = new DateTime(date('G:i', strtotime($time_out)));

                $rendered_hours = $time_in->diff($time_out)->format('%h');
                $rendered_minutes = $time_in->diff($time_out)->format('%i');
                $rendered_hours += round($rendered_minutes/60);

                if ($rendered_hours > 4) { $rendered_hours -= 1; }
                $rendered_hours += $lts_att["rendered_hours"];

                $computed_rendered_hours = array(
                    $rendered_hours,
                    $_SESSION["intern_id"]
                );
    
                $db->query("UPDATE intern_wsap_information SET rendered_hours=:rendered_hours 
                WHERE id=:intern_id");
                $db->updateRenderedHours($computed_rendered_hours);
                $db->execute();
                $db->closeStmt();

                redirect('attendance.php');
                exit();
            } else {
                $_SESSION['error'] = "You are already timed out!";
                redirect('attendance.php');
                exit();
            }
        }
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Attendance");
?>

<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("attendance");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
            <div class="row rounded bg-">
                <div class="col-md-12 p-4">
                    <h5 class="fs-intern fw-bold">Attendance Legend</h5>
                    <ul class="attendance_legend">
                        <li class="bg-danger text-light">AU - Absent Unexcused</li>
                        <li class="bg-primary text-light">AE - Absent Excused</li>
                        <!--
                        <li class="bg-info">MS - Morning Shift</li>
                        <li class="bg-secondary text-light">AS - Afternoon Shift</li>
                        <li class="bg-dark text-light">OD - Off-Duty</li>
                        -->
                    </ul>
                </div>
            </div>
        </div>

        <h3>My Attendance</h3>
        <div class="col-md-12">
            <div id="time-in-time-out-layout" class="d-flex">
                <?php
                    if (isset($_SESSION["intern_id"])) {
                        if ($time_in_enabled) {
                            $date->time_in_enabled();
                        } else {
                            $date->time_in_disabled();
                        }
                        
                        if ($time_out_enabled) {
                            $date->time_out_enabled();
                        } else {
                            $date->time_out_disabled();
                        }
                    }
                ?>
            </div> <?php
                if (isset($_SESSION['error'])) { ?>
                    <div class="alert alert-danger attendance-alert text-danger my-2">
                        <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="col-md-12 text-center">
                            <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                            src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                            width="200" height="90" frameborder="0" seamless>
                            </iframe>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="timeIn" class="btn btn-success">Time in</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Time out Modal -->
        <div class="modal fade" id="timeOutModal" tabindex="-1" aria-labelledby="timeOutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeOutModalLabel">Time Out</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="col-md-12 text-center">
                            <iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                            src="https://www.zeitverschiebung.net/clock-widget-iframe-v2?language=en&size=small&timezone=Asia%2FManila"
                            width="200" height="90" frameborder="0" seamless>
                            </iframe>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="timeOut" class="btn btn-danger">Time out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <table id="attendance-table" class="table caption-top fs-d text-center mt-2">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date</th>
                    <th scope="col">Day</th>
                    <th scope="col">Time in</th>
                    <th scope="col">Time out</th>
                    <th scope="col">Rendered Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($_SESSION["intern_id"])) {
                    $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC;");
                    $db->setInternId($_SESSION["intern_id"]);
                    $db->execute();

                    $count = 0;
                    $conditions = array("AU", "AE", "MS", "AS", "OD", "Late", "No Time out");
                    while ($row = $db->fetch()) {
                        $count++;  ?>
                        <tr>
                            <th scope="row"><?= $count ?></th>
                            <td><?= $row["att_date"] ?></td>
                            <td><?= date("l", strtotime($row["att_date"])); ?></td>
                            <td> <?php
                                if (strlen($row["time_in"]) > 0) {
                                    if ($row["time_in"] == $conditions[0]) { ?>
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if ($row["time_in"] == $conditions[1]) { ?>
                                        <p class="bg-primary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }  else if (str_contains($row["time_in"], $conditions[5])) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    } else { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_in"] ?>
                                        </p> <?php
                                    }
                                } ?>
                            </td>
                            <td> <?php 
                                if (strlen($row["time_out"]) > 0) {
                                    if ($row["time_out"] == $conditions[0]) { ?>
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if ($row["time_out"] == $conditions[1]) { ?>
                                        <p class="bg-primary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else if ($row["time_out"] == $conditions[6]) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }  else { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                            <?= $row["time_out"] ?>
                                        </p> <?php
                                    }
                                } ?>
                            </td>
                            <td><?php
                                $time_in = $row["time_in"];
                                $time_out = $row["time_out"];

                                $rendered_hours = 0;
                                if ($time_out != "No Time out") {
                                    if(!empty($time_in) && !empty($time_out)) {
                                        if (strlen($time_in) > 8) {
                                            $time_in = substr($time_in, 0, 8);
                                        }
                                        
                                        if (strlen($time_out) > 8) {
                                            $time_out = substr($time_out, 0, 8);
                                        }
    
                                        $time_in = new DateTime(date('G:i', strtotime($time_in)));
                                        $time_out = new DateTime(date('G:i', strtotime($time_out)));
    
                                        $rendered_hours = $time_in->diff($time_out)->format('%h');
                                        $rendered_minutes = $time_in->diff($time_out)->format('%i');
                                        $rendered_hours += round($rendered_minutes/60);
    
                                        if ($rendered_hours > 4) { $rendered_hours -= 1; }
                                    }
                                }
                                
                                echo $rendered_hours; ?>
                            </td>
                        </tr> <?php
                    }
                } ?>
            </tbody>
        </table>> <?php
        if ($db->rowCount() == 0) { ?>
            <div class="w-100 text-center my-5">
                <h3>No Record</h3>
            </div> <?php
        } ?>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>