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
    $admin_info = $db->fetch();
    $admin_roles_count = $db->rowCount();

    $selected_date = $date->getNumericDate();
    if (!empty($_GET["date"])) {
        $selected_date = $_GET["date"];
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

        if (!empty($selected_date)) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."date=".$selected_date;
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }
                                                
        if (!empty($_GET["view"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."view=".$_GET["view"];
        }

        if (strlen($parameters) > 1) {
            redirect("no_time_in_interns.php".$parameters);
        } else {
            redirect("no_time_in_interns.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        $parameters = "?";
        if (!empty($selected_date)) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."date=".$selected_date;
        }
                                                
        if (!empty($_GET["view"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."view=".$_GET["view"];
        }

        if (strlen($parameters) > 1) {
            redirect("no_time_in_interns.php".$parameters);
        } else {  
            redirect("no_time_in_interns.php");
        }
        exit();
    }

    if (isset($_POST["editStatus"])) {
        $status = $_POST["status"];

        $att_date = $_GET["date"];
        $intern_id = $_POST["intern_id"];
        $first_name = $_POST["first_name"];
        $last_name = $_POST["last_name"];

        if (isset($status)) {
            if ($status == 0) {
                $db->query("DELETE FROM attendance WHERE intern_id=:intern_id AND att_date=:att_date");
                $db->setInternId($intern_id);
                $db->setAttDate($att_date);
                $db->execute();
                $db->closeStmt();
            } else {
                if ($status == 1) {
                    $time_in = "AE";
                    $time_out = "AE";
                } else if ($status == 2) {
                    $time_in = "AU";
                    $time_out = "AU";
                }

                $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id AND att_date=:att_date");
                $db->setInternId($intern_id);
                $db->setAttDate($att_date);
                $db->execute();
                $db->closeStmt();

                if ($db->rowCount() == 0) {
                    $attendance = array(
                        $intern_id,
                        $att_date,
                        $time_in,
                        $time_out,
                        0,
                        0,
                        0,
                        null,
                        null
                    );
                    
                    $db->query("INSERT INTO attendance
                    VALUES (NULL, :intern_id, :att_date, :time_in, :time_out,
                    :regular_hours, :ot_hours, :rendered_hours,
                    :time_in_gps_image, :time_out_gps_image);");
                    $db->timeIn($attendance);
                } else {
                    $attendance = array(
                        $intern_id,
                        $att_date,
                        $time_in,
                        $time_out
                    );

                    $db->query("UPDATE attendance SET time_in=:time_in, time_out=:time_out
                    WHERE intern_id=:intern_id AND att_date=:att_date");
                    $db->setAttendance($attendance);
                }

                $db->execute();
                $db->closeStmt();
            }
            
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") updated the ".date("F j, Y", strtotime($att_date))." attendance status of ".$last_name.", ".$first_name.".";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (NULL, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully update the attendance status.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }

        redirect("no_time_in_interns.php?date=".$_GET["date"]."&view=1");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("No Time in Interns");
?>
<div class="my-container"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("attendance");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>No Time in Interns</h3>
            </div>
        </div> <?php

        if ($admin_roles_count != 0) { ?>
            <div>
                <div class="row">
                    <!--SEARCH BUTTON/TEXT-->
                    <form method="post">
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
                    </form>
                        
                    <div class="col-12 d-xl-flex d-lg-inline-block">
                        <div class="mb-2">
                            <a class="btn btn-secondary me-2" href="interns_attendance.php?date=<?= $selected_date ?>">
                                <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns' Attendance
                            </a>
                        </div>

                        <div class="d-md-flex d-sm-inline-block">
                            <div class="w-md-fit w-sm-100 d-flex align-items-center mb-2 me-md-2 me-sm-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= date("F j, Y", strtotime($selected_date)) ?>" disabled>
                                    <div class="input-group-append">
                                        <a class="btn btn-smoke" href="calendar.php?destination=no_time_in_interns">Select Date</a>
                                    </div>
                                </div>
                            </div>

                            <!--DEPARTMENT DROPDOWN-->
                            <div class="w-fit d-flex mb-2">
                                <div class="dropdown align-center me-2">
                                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                    data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                                        if (empty($_GET["department"])) {
                                            echo "All Departments";
                                        } else {
                                            echo $_GET["department"];
                                        } ?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?";
                                            if (!empty($_GET["search"])) {
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }
                                            
                                            if (!empty($_GET["sort"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=".$_GET["sort"];
                                            }
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_in_interns.php" ?>" <?php
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

                                                if (!empty($selected_date)) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."date=".$selected_date;
                                                }
                                                
                                                if (!empty($_GET["sort"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=".$_GET["sort"];
                                                }
                                                
                                                if (!empty($_GET["view"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."view=".$_GET["view"];
                                                }

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "no_time_in_interns.php" ?>" <?php
                                                } ?>> <?= $row["name"] ?>
                                            </a></li> <?php
                                        } ?>
                                    </ul>
                                </div>
                                <!--SORTING DROPDOWN-->
                                <div class="dropdown">
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
                                            }
                                        } ?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" name="sort">
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?";
                                            if (!empty($_GET["search"])) {
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_in_interns.php" ?>" <?php
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }

                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=1";
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_in_interns.php" ?>" <?php
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=2";
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_in_interns.php" ?>" <?php
                                            } ?>>Z-A</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex w-fit ms-auto">
                        <!--VIEW DROPDOWN-->
                        <div class="ms-auto dropdown me-2">
                            <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false"> <?php
                                if (empty($_GET["view"])) {
                                    echo "Grid";
                                } else {
                                    echo "Tabular";
                                } ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" name="sort">
                                <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                    if (!empty($_GET["search"])) {
                                        $parameters = $parameters."search=".$_GET["search"];
                                    }

                                    if (!empty($_GET["department"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."department=".$_GET["department"];
                                    }

                                    if (!empty($selected_date)) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."date=".$selected_date;
                                    }
                                                
                                    if (!empty($_GET["sort"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=".$_GET["sort"];
                                    }

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "no_time_in_interns.php" ?>" <?php
                                    } ?>>Grid</a></li>
                                <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                    if (!empty($_GET["search"])) {
                                        $parameters = $parameters."search=".$_GET["search"];
                                    }

                                    if (!empty($_GET["department"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."department=".$_GET["department"];
                                    }

                                    if (!empty($selected_date)) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."date=".$selected_date;
                                    }
                                                
                                    if (!empty($_GET["sort"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=".$_GET["sort"];
                                    }
                                        
                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                    $parameters = $parameters."view=1";

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "no_time_in_interns.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "no_time_in_interns.php" ?>" <?php
                                    } ?>>Tabular</a></li>
                            </ul>
                        </div>

                        <button class="btn btn-secondary mb-1" onclick="copyRecords()">
                            Copy Records as Text
                        </button>
                    </div>
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
            } ?>

            <div class="row mb-3"> <?php
                $sort = " ORDER BY intern_personal_information.last_name";
                if (!empty($_GET["sort"])) {
                    switch ($_GET["sort"]) {
                        case "1":
                            $sort = " ORDER BY intern_personal_information.last_name";
                            break;
                        case "2":
                            $sort = " ORDER BY intern_personal_information.last_name DESC";
                            break;
                    }
                }

                $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                intern_personal_information.id = intern_accounts.id AND
                intern_wsap_information.department_id = departments.id AND
                (NOT EXISTS (SELECT * FROM attendance
                WHERE intern_personal_information.id = attendance.intern_id AND
                att_date=:att_date) OR
                EXISTS (SELECT * FROM attendance
                WHERE intern_personal_information.id = attendance.intern_id AND
                att_date=:att_date AND
                time_out LIKE CONCAT('%', 'A', '%') AND
                time_out NOT LIKE CONCAT('%', 'AS', '%')))";

                if (!empty($_GET["search"])) {
                    if (strlen($conditions) > 6) {
                        $conditions = $conditions." AND";
                    }
                    $conditions = $conditions." (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                    CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))";
                }
                if (!empty($_GET["department"])) {
                    if (strlen($conditions) > 6) {
                        $conditions = $conditions." AND";
                    }
                    $conditions = $conditions." departments.name=:dept_name";
                }

                $query = "SELECT intern_personal_information.*, intern_personal_information.id AS intern_id,
                intern_wsap_information.*, intern_accounts.*, departments.*
                FROM intern_personal_information, intern_wsap_information, intern_accounts, departments";

                if (strlen($conditions) > 6) {
                    $db->query($query.$conditions.$sort);
                    $db->setAttDate($selected_date);

                    if (!empty($_GET["search"])) {
                        $db->selectInternName($_GET["search"]);
                    }
                    if (!empty($_GET["department"])) {
                        $db->selectDepartment($_GET["department"]);
                    }
                }
                $db->execute();

                $text = "\"No Time in Interns: ".$selected_date."\\n\\n\"\n";

                if (empty($_GET["department"])) {
                    $text .= "+ \"All Departments:\\n\"\n";
                } else {
                    $text .= "+ \"".$_GET["department"]." Department:\\n\"\n";
                }

                $absent_db = new Database();
                $absent_db->query("SELECT * FROM attendance
                WHERE att_date=:att_date AND
                time_out LIKE CONCAT('%', 'A', '%') AND
                time_out NOT LIKE CONCAT('%', 'AS', '%')");
                $absent_db->setAttDate($selected_date); 
                
                if (empty($_GET["view"])) { ?>
                    <div class="interns-attendance"> <?php
                        while ($row = $db->fetch()) {
                            if (isActiveIntern($row["onboard_date"], $row["offboard_date"], $selected_date) && 
                            $row["status"] != 3 && $row["status"] != 6) {
                                $text .= "+ \"".$row["last_name"].", ".$row["first_name"]." - ".$row["intern_id"];
                                if (empty($_GET["department"])) {
                                    $text .= " - ".$row["name"]."\\n\"\n";
                                } else {
                                    $text .= "\\n\"\n";
                                } ?>
                                <a class="clickable-card" href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>" draggable="false">
                                    <div class="h-100 attendance text-center position-relative pb-5">
                                        <div class="top" style="height: 100px;">
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
                                            } ?>" onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                        </div>
                                        <div class="summary-total mt-2 w-fit mx-auto">
                                            <h5 class="mb-0 text-dark fs-regular">
                                                <?= $row["last_name"].", ".$row["first_name"] ?>
                                            </h5>
                                            <h6 class="fs-f"><?= $row["name"] ?></h6>
                                        </div>
                                        <div class="absolute-bottom absolute-w-100 py-3 d-flex justify-content-center" style="bottom: 0;"> <?php
                                            $absent_db->execute();

                                            $off_duty = true;
                                            $absent = false;

                                            while ($absent_row = $absent_db->fetch()) {
                                                if ($absent_row["intern_id"] == $row["intern_id"]) {
                                                    if ($absent_row["time_out"] == "AE") { ?>
                                                        <p class="bg-primary text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                                            <?= $absent_row["time_out"] ?>
                                                        </p> <?php
                                                    } else if ($absent_row["time_out"] == "AU") { ?>
                                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                                            <?= $absent_row["time_out"] ?>
                                                        </p> <?php
                                                    }

                                                    $absent = true;
                                                    break;
                                                }
                                            }
                                            
                                            if ($off_duty && !$absent) { ?>
                                                <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                                    <?= "Off-duty" ?>
                                                </p> <?php 
                                            } ?>
                                        </div>
                                    </div>
                                </a> <?php
                            }
                        } ?>
                    </div> <?php
                }  else { ?>
                    <table class="table fs-d text-center">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Department</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody> <?php
                            $count = 0;
                            while ($row = $db->fetch()) {
                                if (isActiveIntern($row["onboard_date"], $row["offboard_date"], $selected_date) && 
                                $row["status"] != 3 && $row["status"] != 6) {
                                    $text .= "+ \"".$row["last_name"].", ".$row["first_name"]." - ".$row["intern_id"];
                                    if (empty($_GET["department"])) {
                                        $text .= " - ".$row["name"]."\\n\"\n";
                                    } else {
                                        $text .= "\\n\"\n";
                                    }
                                    
                                    $absent_db->execute();

                                    $off_duty = true;
                                    $absent = false;

                                    while ($absent_row = $absent_db->fetch()) {
                                        if ($absent_row["intern_id"] == $row["intern_id"]) {
                                            $time_out = $absent_row["time_out"];
                                            $absent = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($off_duty && !$absent) { 
                                        $time_out = "Off-duty";
                                    } 

                                    $count++; ?>
                                    <tr>
                                        <div class="modal fade" id="editStatusModal<?= $row["intern_id"] ?>" tabindex="-1"
                                            aria-labelledby="editStatusModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div class="modal-title" id="editStatusModalLabel">
                                                            <h5><?= $row["last_name"].", ".$row["first_name"] ?></h5>
                                                        </div>
                                                        <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                                            <i class="fa-solid fa-close"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-12 user_input my-1">
                                                                    <label class="mb-2" for="status">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="0" <?php
                                                                            if ($time_out == "Off-duty") { ?>
                                                                                selected <?php
                                                                            } ?>>Off-duty</option>
                                                                        <option value="1" <?php
                                                                            if ($time_out == "AE") { ?>
                                                                                selected <?php
                                                                            } ?>>Absent-Excused</option>
                                                                        <option value="2" <?php
                                                                            if ($time_out == "AU") { ?>
                                                                                selected <?php
                                                                            } ?>>Absent-Unexcused</option>
                                                                    </select>
                                                                </div>
                                                                <div class="text-center px-5">
                                                                    <input type="text" name="intern_id" class="form-control text-center d-none"
                                                                                value="<?= $row["intern_id"] ?>" readonly>
                                                                    <input type="text" name="first_name" class="form-control text-center d-none"
                                                                                value="<?= $row["first_name"] ?>" readonly>
                                                                    <input type="text" name="last_name" class="form-control text-center d-none"
                                                                                value="<?= $row["last_name"] ?>" readonly>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="editStatus" class="btn btn-success">Submit</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <th scope="row"><?= $count ?></th>
                                        <td><?= $row["last_name"].", ".$row["first_name"] ?></td>
                                        <td><?= $row["name"] ?></td>
                                        <td> <?php
                                            if ($time_out == "AE") { ?>
                                                <p class="bg-primary text-light rounded w-fit m-auto px-2 py-1">
                                                    <?= $time_out ?>
                                                </p> <?php
                                            } else if ($time_out == "AU") { ?>
                                                <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                                    <?= $time_out ?>
                                                </p> <?php
                                            } else { ?>
                                                <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1">
                                                    <?= $time_out ?>
                                                </p> <?php
                                            } ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <div class="w-fit p-0 me-1" data-bs-toggle="tooltip" data-bs-placement="left"
                                                    title="Edit Status">
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#editStatusModal<?= $row["intern_id"] ?>">
                                                        <i class="fa-solid fa-pen fs-a"></i>
                                                    </button>
                                                </div>
                                                <div class="w-fit p-0 me-1" data-bs-toggle="tooltip" data-bs-placement="left"
                                                    title="View DTR">
                                                    <a class="btn btn-secondary btn-sm"
                                                        href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>">
                                                        <i class="fa-solid fa-arrow-right fs-a"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr> <?php
                                }
                            } ?>
                        </tbody>
                    </table> <?php
                }

                if ($db->rowCount() == 0) { 
                    $text .= "+ \"No Record\"\n"; ?>
                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } ?>
            </div> <?php
        } else {
            include_once "access_denied.php";
        } ?>        
    </div>
</div>
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
</script>
<?php
    require_once "../Controllers/PHP_JS.php";
    copyFunction($text);
    require_once "../Templates/footer.php";
?>