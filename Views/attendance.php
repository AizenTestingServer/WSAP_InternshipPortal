<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"])) {
        redirect("../index");
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
        $row = $db->fetch();

        if ($date->getDate() != $row["att_date"]) {
            if (empty($row["time_out"])) {
                $attendance = array(
                    "No Time out",
                    $_SESSION["intern_id"],
                    $row["id"]
                );

                $db->query("UPDATE attendance SET time_out=:time_out 
                WHERE intern_id=:intern_id && id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();
            }
        }

        if (isset($_POST["timeIn"])) { 
            if ($date->getStringDateTime() >= $date->morning_briefing()) {
                $attendance = array(
                    $_SESSION["intern_id"],
                    $date->getDate(),
                    $date->getTime()." Late",
                    null,
                );
            } else {
                $attendance = array(
                    $_SESSION["intern_id"],
                    $date->getDate(),
                    $date->getTime(),
                    null,
                );
            }

            if ($db->rowCount() != 0 && $date->getStringDate() == strtotime($row["att_date"])) {
                $_SESSION['error'] = "You are already timed in!";
                redirect('attendance');
                exit();
            }
            
            $db->query("INSERT INTO attendance
            VALUES (NULL, :intern_id, :att_date, :time_in, :time_out);");
            $db->timeIn($attendance);
            $db->execute();
            $db->closeStmt();
        
            redirect('attendance');
            exit();
        }
    
        if (isset($_POST["timeOut"])) {
            if (!empty($row["time_in"]) && empty($row["time_out"])) {
                $attendance = array(
                    $date->getTime(),
                    $_SESSION["intern_id"],
                    $row["id"]
                );
    
                $db->query("UPDATE attendance SET time_out=:time_out 
                WHERE intern_id=:intern_id && id=:id");
                $db->timeOut($attendance);
                $db->execute();
                $db->closeStmt();

                redirect('attendance');
                exit();
            } else {
                $_SESSION['error'] = "You are already timed out!";
                redirect('attendance');
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
            <?php include_once "profile_settings.php"; ?>
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
                        if (($date->getStringDateTime() >= $date->time_in_start() && 
                        $date->getStringDateTime() < $date->time_in_end()) ||
                        ($date->getStringDateTime() > $date->morning_shift_out() &&
                        $date->getStringDateTime() < $date->afternoon_shift_start())) {
                            $date->time_in_enabled();
                        } else {
                            $date->time_in_disabled();
                        }
                        
                        if (($date->getStringDateTime() >= $date->time_out_start() &&
                        $date->getStringDateTime() < $date->time_out_end()) ||
                        ($date->getStringDateTime() > $date->morning_shift_out() &&
                        $date->getStringDateTime() < $date->afternoon_shift_start())) {
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
        <div class="modal fade" id="timeinModal" tabindex="-1" aria-labelledby="timeinModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeinModalLabel">Time in</h5>
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
        <div class="modal fade" id="timeoutModal" tabindex="-1" aria-labelledby="timeoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="timeoutModalLabel">Time Out</h5>
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

        <table id="attendance-table" class="table caption-top fs-d text-center">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Date</th>
                    <th scope="col">Day</th>
                    <th scope="col">Time in</th>
                    <th scope="col">Time out</th>
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
                            <td> <?php if (strlen($row["time_in"]) > 0) {
                                        if( $row["time_in"] == $conditions[0]) { ?>
                                            <p class="bg-danger text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else if ($row["time_in"] == $conditions[1]) { ?>
                                            <p class="bg-primary text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_in"], $conditions[5])) { ?>
                                            <p class="bg-warning text-dark rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        } else { ?>
                                            <p class="bg-success text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }
                                    } ?>
                            </td>
                            <td> <?php if (strlen($row["time_out"]) > 0) {
                                        if ($row["time_out"] == $conditions[0]) { ?>
                                            <p class="bg-danger text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }  else if ($row["time_out"] == $conditions[1]) { ?>
                                            <p class="bg-primary text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }  else if ($row["time_out"] == $conditions[6]) { ?>
                                            <p class="bg-warning text-dark rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }  else { ?>
                                            <p class="bg-success text-light rounded d-inline px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }
                                    } ?>
                            </td>
                        </tr> <?php
                    }
                } ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>