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

    if (!empty($_GET["intern_id"])) {
        $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
        FROM intern_personal_information, intern_wsap_information, departments
        WHERE intern_personal_information.id = intern_wsap_information.id AND
        intern_wsap_information.department_id = departments.id AND
        intern_personal_information.id=:intern_id");
        $db->setInternId($_GET["intern_id"]);
        $db->execute();
    }

    $value = $db->fetch();

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

        <h3>Attendance History</h3> <?php
        if ($admin_roles_count != 0) { ?>
            <div class="intern info d-md-flex w-sm-100">
                <div class="top">
                    <img class="img-intern mx-auto" src="<?php {
                        if ($value["image"] == null || strlen($value["image"]) == 0) {
                            if ($value["gender"] == 0) {
                                echo "../Assets/img/profile_imgs/default_male.png";
                            } else {
                                echo "../Assets/img/profile_imgs/default_female.png";
                            }
                        } else {
                            echo $value["image"];
                        }
                    } ?>">
                </div>
                <div class="w-100">
                    <div class="summary-total w-fit text-center mx-auto ms-md-0 mt-2">
                        <h5 class="mb-0 text-dark fs-regular">
                            <?= $value["last_name"].", ".$value["first_name"] ?>
                        </h5>
                        <h6 class="fs-f"><?= $value["name"] ?></h6>
                    </div>
                    <div class="bottom w-md-fit w-sm-100 mt-3"> <?php
                        if ($value["status"] == 0 || $value["status"] == 5) { ?>
                            <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1 fs-d"> <?php
                                if ($value["status"] == 0) {
                                    echo "Inactive";
                                } else {
                                    echo "Suspended";
                                } ?>
                            </p> <?php
                        }  else if ($value["status"] == 1 || $value["status"] == 4) { ?>
                            <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d"> <?php
                                if ($value["status"] == 1) {
                                    echo "Active";
                                } else {
                                    echo "Extended";
                                } ?>
                            </p> <?php
                        }   else if ($value["status"] == 2) { ?>
                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                Offboarded
                            </p> <?php
                        }   else if ($value["status"] == 4) { ?>
                            <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                Withdrew
                            </p> <?php
                        }   else if ($value["status"] == 6) { ?>
                            <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                Terminated
                            </p> <?php
                        } ?>
                    </div>
                </div>
            </div>

            <table class="table caption-top fs-d text-center mt-5">
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
                        $db->setInternId($_GET["intern_id"]);
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
                                        }  else if (str_contains($row["time_in"], $conditions[2])) { ?>
                                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_in"], $conditions[3])) { ?>
                                            <p class="bg-info text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_in"], $conditions[4])) { ?>
                                            <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_in"], $conditions[5])) { ?>
                                            <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_in"] ?>
                                            </p> <?php
                                        }  else { ?>
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
                                        }  else if (str_contains($row["time_out"], $conditions[2])) { ?>
                                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_out"], $conditions[3])) { ?>
                                            <p class="bg-info text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                                <?= $row["time_out"] ?>
                                            </p> <?php
                                        }  else if (str_contains($row["time_out"], $conditions[4])) { ?>
                                            <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1">
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
                                <td> <?php
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
            </table> <?php
            if ($db->rowCount() == 0) { ?>
                <div class="w-100 text-center my-5">
                    <h3>No Record</h3>
                </div> <?php
            }
        } else { ?>
            <div class="d-flex justify-content-center align-items-center" style="height: 90vh;">
                <div class="text-center">
                    <i class="fa-solid fa-lock fa-3x text-warning mb-4"></i>
                    <h3 class="fw-bold">Access Denied</h3>
                    <p>
                        <pre>Only Admin of WSAP IP can access this feature.</pre>
                    </p>
                    <a class="btn btn-secondary" href="dashboard.php">Return to Dashboard</a>
                </div> 
            </div> <?php
        } ?>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>