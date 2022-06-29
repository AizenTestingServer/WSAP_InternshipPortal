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
        $value = $db->fetch();
    }

    if (!empty($_GET["id"])) {    
        $db->query("SELECT * FROM attendance WHERE id=:id");
        $db->setId($_GET["id"]);
        $db->execute();
        $selected_att = $db->fetch();
        
        $time_in = $selected_att["time_in"];
        $time_out = $selected_att["time_out"];

        if ($time_out == "NTO") {
            $time_out_hr = $date->getHour();
            $time_out_min = $date->getMin();
            $time_out_time_type = $date->getTimeType();
        } else {
            if (strlen($time_out) > 8) {
                $time_out = substr($time_out, 0, 8);
            }

            $time_out_hr = date("g", strtotime($time_out));
            $time_out_min = date("i", strtotime($time_out));
            $time_out_time_type = date("a", strtotime($time_out));
        }

        if (strlen($time_in) > 8) {
            $time_in = substr($time_in, 0, 8);
        }

        $time_in_hr = date("g", strtotime($time_in));
        $time_in_min = date("i", strtotime($time_in));
        $time_in_time_type = date("a", strtotime($time_in));
    }

    if (isset($_POST["search"])) {
        $parameters = "?";
        if (!empty($_POST["search_intern"])) {
            $parameters = $parameters."search=".$_POST["search_intern"];
        }

        if (!empty($_GET["department"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."department=".$_GET["department"];
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (strlen($parameters) > 1) {
            redirect("daily_time_record.php".$parameters);
        } else {
            redirect("daily_time_record.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("daily_time_record.php");
        exit();
    }

    if (isset($_POST["submit"])) {
       $time_out = $_POST["time_out_hr"].":".$_POST["time_out_min"]." ".$_POST["time_out_time_type"];
        
       $tmp_time_out = $time_out;
        if (isMorningShift($selected_att["time_in"], $time_out)) {
            $tmp_time_out =  $tmp_time_out." MS";
        }
        if (isAfternoonShift($selected_att["time_in"], $time_out)) {
            $tmp_time_out =  $tmp_time_out." AS";
        }
        if (isOvertime($time_out)) {
            $tmp_time_out =  $tmp_time_out." OT";
        }
        $time_out = $tmp_time_out;
        
        $attendance = array(
            $time_out,
            $selected_att["id"]
        );

        $db->query("UPDATE attendance SET time_out=:time_out WHERE id=:id");
        $db->timeOut($attendance);
        $db->execute();
        $db->closeStmt();
        
        $time_in = $selected_att["time_in"];

        if (strlen($time_out) > 8) {
            $time_out = substr($time_out, 0, 8);
        }
                            
        if (isMorningShift($time_in, $time_out) || isAfternoonShift($time_in, $time_out)) {
            $rendered_hours = 4;
        } else {
            $rendered_hours = 8;
        }

        if (isOvertime($time_out)) {
            $dt_time_out_start = new DateTime(date("G:i", $date->time_out_start()));
            $dt_time_out = new DateTime(date("G:i", strtotime($time_out)));
            $rendered_hours += $dt_time_out_start->diff($dt_time_out)->format("%h");
            $rendered_minutes = $dt_time_out_start->diff($dt_time_out)->format("%i");
            $rendered_hours += round($rendered_minutes/60, 1);
        }

        $db->query("SELECT * FROM intern_wsap_information WHERE id=:intern_id;");
        $db->setInternId($_GET["intern_id"]);
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
        
        $_SESSION["time_out_success"] = "Successfully setup the time out.";
        unset($_SESSION["time_out_hr"]);
        unset($_SESSION["time_out_min"]);
        unset($_SESSION["time_out_time_type"]);

        redirect("daily_time_record.php?intern_id=".$_GET["intern_id"]);
        exit();
    }

    if (isset($_POST["cancel"])) {
        redirect("daily_time_record.php?intern_id=".$_GET["intern_id"]);
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Daily Time Record");
?>

<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("attendance");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php";
            if ($admin_roles_count != 0 && !empty($_GET["intern_id"])) { ?>
                <div class="row rounded bg-">
                    <div class="col-md-12 p-4">
                        <h5 class="fs-intern fw-bold">Attendance Legend</h5>
                        <ul class="attendance_legend">
                            <li class="bg-morning text-light">MS - Morning Shift</li>
                            <li class="bg-afternoon text-light">AS - Afteroon Shift</li>
                            <li class="bg-indigo text-light">OT - Overtime</li>
                            <li class="bg-warning">L - Late | NTO - No Time out</li>
                            <li class="bg-danger text-light">AU - Absent Unexcused</li>
                            <li class="bg-primary text-light">AE - Absent Excused</li>
                        </ul>
                    </div>
                </div> <?php
            } ?>
        </div>

        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Daily Time Record</h3>
            </div>
        </div> <?php
        if ($admin_roles_count != 0) {
            if (!empty($_GET["intern_id"])) { ?>
                <div class="intern info d-md-flex w-fit" style="height: 230px">
                    <div class="top me-md-2">
                        <img class="img-intern mx-auto d-block" src="<?php {
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
                        <div class="summary-total w-fit text-md-start text-center mx-auto ms-md-0 mt-2">
                            <h5 class="mb-0 text-dark">
                                <?= $value["last_name"].", ".$value["first_name"] ?>
                            </h5>
                            <h6><?= $value["name"] ?></h6>
                        </div>
                        <div class="bottom w-md-fit w-sm-100"> <?php
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
                                
                <div class="w-fit my-2 ms-auto">
                    <a class="btn btn-primary"
                        href="preview_pdf.php?intern_id=<?= $_GET["intern_id"] ?>"
                        target="window">
                        Preview DTR as PDF
                    </a>
                </div> <?php
                        
                if (!empty($_GET["id"]) && $selected_att["time_out"] == "NTO" &&
                    $selected_att["intern_id"] == $_GET["intern_id"]) { ?>
                    <div class="row rounded shadow mb-4 pb-4 position-relative">
                        <div class="rounded shadow px-0">
                            <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                               <?=  $selected_att["att_date"]." | ".date("l", strtotime($selected_att["att_date"])) ?>
                            </h6>
                        </div>
                        <div class="col-12 p-4">
                            <form method="post">
                                <div class="row mb-4">
                                    <div class="col-md-12 col-lg-6 user_input my-1">
                                        <label class="mb-2" for="timeIn">Time out</label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" name="time_out_hr"> <?php
                                                    for ($i = 1; $i <= 12; $i++) { ?>
                                                        <option value="<?= $i ?>" <?php
                                                        if ($time_out_hr == $i) { ?>
                                                            selected <?php
                                                        } ?>><?= $i ?></option><?php
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="time_out_min"> <?php
                                                    for ($i = 1; $i <= 60; $i++) { ?>
                                                        <option value="<?= $i ?>" <?php
                                                        if ($time_out_min == $i) { ?>
                                                            selected <?php
                                                        } ?>><?= $i ?></option><?php
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select" name="time_out_time_type">
                                                    <option value="am" <?php
                                                        if ($time_out_time_type == "am") { ?>
                                                            selected <?php
                                                        } ?>>AM</option>
                                                    <option value="pm" <?php
                                                        if ($time_out_time_type == "pm") { ?>
                                                            selected <?php
                                                        } ?>>PM</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bottom-right">
                                    <button class="btn btn-indigo" type="submit" name="submit">Submit</button>
                                    <button class="btn btn-secondary" name="cancel">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div> <?php
                }

                if (isset($_SESSION["time_out_success"])) { ?>
                    <div class="alert alert-success text-success">
                        <?php
                            echo $_SESSION["time_out_success"];
                            unset($_SESSION["time_out_success"]);
                        ?>
                    </div> <?php
                } ?>

                <table class="table caption-top fs-d text-center">
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
                            $conditions = array("AU", "AE", "MS", "AS", "OT", "OD", "L", "NTO");
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
                                            }  else if (strlen($row["time_out"]) > 0 && str_contains($row["time_out"], $conditions[2])) { ?>
                                                <p class="bg-morning text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if (strlen($row["time_out"]) > 0 && str_contains($row["time_out"], $conditions[3])) { ?>
                                                <p class="bg-afternoon text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_in"], $conditions[4])) { ?>
                                                <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_in"], $conditions[6])) { ?>
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
                                            }  else if (str_contains($row["time_out"], $conditions[4])) { ?>
                                                <p class="bg-indigo text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_out"], $conditions[2])) { ?>
                                                <p class="bg-morning text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_out"], $conditions[3])) { ?>
                                                <p class="bg-afternoon text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_out"], $conditions[5])) { ?>
                                                <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if ($row["time_out"] == $conditions[7]) { ?>
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
                                        if ($time_out != "NTO") {
                                            if(!empty($time_in) && !empty($time_out)) {
                                                if (strlen($time_in) > 8) {
                                                    $time_in = substr($time_in, 0, 8);
                                                }                                    
                                                if (strlen($time_out) > 8) {
                                                    $time_out = substr($time_out, 0, 8);
                                                }
                                                if (isMorningShift($time_in, $time_out) || isAfternoonShift($time_in, $time_out)) {
                                                    $rendered_hours = 4;
                                                } else {
                                                    $rendered_hours = 8;
                                                }
            
                                                if (isOvertime($time_out)) {
                                                    $dt_time_out_start = new DateTime(date("G:i", $date->time_out_start()));
                                                    $dt_time_out = new DateTime(date("G:i", strtotime($time_out)));
                                                    $rendered_hours += $dt_time_out_start->diff($dt_time_out)->format("%h");
                                                    $rendered_minutes = $dt_time_out_start->diff($dt_time_out)->format("%i");
                                                    $rendered_hours += round($rendered_minutes/60, 1);
                                                }
                                            }
                                        }
                                        
                                        echo $rendered_hours; ?>
                                    </td> <?php
                                    if ($time_out == "NTO") { ?>
                                        <td>
                                            <a class="btn btn-secondary btn-sm"
                                            href="daily_time_record.php?intern_id=<?= $_GET["intern_id"] ?>&id=<?= $row["id"] ?>">
                                                <i class="fa-solid fa-pen fs-a"></i>
                                            </a>
                                        </td> <?php
                                    } ?>
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
                <div>
                    <form method="post">
                        <div class="row">
                            <!--SEARCH BUTTON/TEXT-->
                            <div class="col-lg-8 col-md-10 col-sm-12">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" placeholder="Search Intern" name="search_intern" value="<?php
                                    if (!empty($_GET["search"])) {
                                        echo $_GET["search"];
                                    } ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-indigo" type="submit" name="search">Search</button>
                                        <button class="btn btn-danger" name="reset">Reset</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 d-lg-flex d-md-inline-block">
                                <div class="w-100 d-md-flex d-sm-flex">
                                    <div class="d-flex my-2">
                                        <!--DEPARTMENT DROPDOWN-->
                                        <div class="dropdown align-center me-2">
                                            <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                            data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                                                if (empty($_GET["department"])) {
                                                    echo "All Departments";
                                                } else {
                                                    echo $_GET["department"];
                                                }?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                $parameters = "?";
                                                if (!empty($_GET["search"])) {
                                                    $parameters = $parameters."search=".$_GET["search"];
                                                }
                                                
                                                if (!empty($_GET["sort"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=".$_GET["sort"];
                                                }

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "daily_time_record.php" ?>" <?php
                                                } ?>> All Departments </a></li> <?php
                                                
                                                $db->query("SELECT * FROM departments ORDER BY name");
                                                $db->execute();
                                                
                                                while ($row = $db->fetch()) { ?>
                                                    <li><a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($row["name"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$row["name"];
                                                    }
                                                    
                                                    if (!empty($_GET["sort"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."sort=".$_GET["sort"];
                                                    }

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>> <?= $row["name"] ?>
                                                    </a></li> <?php
                                                } ?>
                                            </ul>
                                        </div>
                                        <!--SORTING DROPDOWN-->
                                        <div class="dropdown me-2">
                                            <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                            data-bs-toggle="dropdown" aria-expanded="false"> <?php
                                                if (empty($_GET["sort"])) {
                                                    echo "Default";
                                                } else {
                                                    switch ($_GET["sort"]) {
                                                        case "1":
                                                            echo "A-Z";
                                                            break;
                                                        case "2":
                                                            echo "Z-A";
                                                            break;
                                                        case "3":
                                                            echo "Oldest Intern";
                                                            break;
                                                        case "4":
                                                            echo "Newest Intern";
                                                            break;
                                                    }
                                                }?>
                                            </button>
                                            <ul class="dropdown-menu me-2z" aria-labelledby="dropdownMenuButton1" name="sort">
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>>Default</a></li>
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }

                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=1";

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>>A-Z</a></li>
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=2";

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>>Z-A</a></li>
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=3";

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>>Oldest Intern</a></li>
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }

                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=4";

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "daily_time_record.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "daily_time_record.php" ?>" <?php
                                                    } ?>>Newest Intern</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="row">
                    <div class="interns"> <?php
                        $sort = " ORDER BY intern_personal_information.last_name";
                        if(!empty($_GET["sort"])) {
                            switch ($_GET["sort"]) {
                                case "1":
                                    $sort = " ORDER BY intern_personal_information.last_name";
                                    break;
                                case "2":
                                    $sort = " ORDER BY intern_personal_information.last_name DESC";
                                    break;
                                case "3":
                                    $sort = " ORDER BY intern_wsap_information.onboard_date";
                                    break;
                                case "4":
                                    $sort = " ORDER BY intern_wsap_information.onboard_date DESC";
                                    break;
                            }
                        }

                        if (!empty($_GET["department"]) && !empty($_GET["search"])) {
                            $interns = array($_GET["department"], $_GET["search"]);
                            
                            $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                            FROM intern_personal_information, intern_wsap_information, departments
                            WHERE intern_personal_information.id = intern_wsap_information.id AND
                            intern_wsap_information.department_id = departments.id AND name=:dept_name AND
                            (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                            CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))".$sort);
                            $db->selectInterns3($interns);
                        } else if (!empty($_GET["department"])) {                        
                            $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                            FROM intern_personal_information, intern_wsap_information, departments
                            WHERE intern_personal_information.id = intern_wsap_information.id AND
                            intern_wsap_information.department_id = departments.id AND name=:dept_name".$sort);
                            $db->selectInterns2($_GET["department"]);
                        } else if (!empty($_GET["search"])) {                        
                            $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                            FROM intern_personal_information, intern_wsap_information, departments
                            WHERE intern_personal_information.id = intern_wsap_information.id AND
                            intern_wsap_information.department_id = departments.id AND
                            (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                            CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))".$sort);
                            $db->selectInterns($_GET["search"]);
                        } else {
                            $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                            FROM intern_personal_information, intern_wsap_information, departments
                            WHERE intern_personal_information.id = intern_wsap_information.id AND
                            intern_wsap_information.department_id = departments.id".$sort);
                        }
                        $db->execute();

                        while ($row = $db->fetch()) { ?>
                            <a class="clickable-card" href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>"
                                draggable="false">
                                <div class="intern text-center">
                                    <div class="top">
                                        <img class="img-intern mx-auto" src="<?php {
                                            if ($row["image"] == null || strlen($row["image"]) == 0) {
                                                if ($row["gender"] == 0) {
                                                    echo "../Assets/img/profile_imgs/default_male.png";
                                                } else {
                                                    echo "../Assets/img/profile_imgs/default_female.png";
                                                }
                                            } else {
                                                echo $row["image"];
                                            }
                                        } ?>">
                                    </div>
                                    <div class="summary-total mt-2 w-fit mx-auto">
                                        <h5 class="mb-0 text-dark fs-regular">
                                            <?= $row["last_name"].", ".$row["first_name"] ?>
                                        </h5>
                                        <h6 class="fs-f"><?= $row["name"] ?></h6>
                                    </div>
                                    <div class="bottom w-100 mt-3"> <?php
                                        if ($row["status"] == 0 || $row["status"] == 5) { ?>
                                            <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1 fs-d"> <?php
                                                if ($row["status"] == 0) {
                                                    echo "Inactive";
                                                } else {
                                                    echo "Suspended";
                                                } ?>
                                            </p> <?php
                                        }  else if ($row["status"] == 1 || $row["status"] == 4) { ?>
                                            <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d"> <?php
                                                if ($row["status"] == 1) {
                                                    echo "Active";
                                                } else {
                                                    echo "Extended";
                                                } ?>
                                            </p> <?php
                                        }   else if ($row["status"] == 2) { ?>
                                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                            Offboarded
                                            </p> <?php
                                        }   else if ($row["status"] == 4) { ?>
                                            <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                            Withdrew
                                            </p> <?php
                                        }   else if ($row["status"] == 6) { ?>
                                            <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                                Terminated
                                            </p> <?php
                                        } ?>
                                    </div>
                                </div>
                            </a> <?php
                        } ?>
                    </div>
                     <?php
                    if ($db->rowCount() == 0) { ?>
                        <div class="w-100 text-center my-5">
                            <h3>No Record</h3>
                        </div> <?php
                    } ?>
                </div> <?php
            }           
        } else {
            include_once "access_denied.php";
        } ?>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>