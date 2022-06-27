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

    $selected_date = $date->getDate();
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
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (!empty($selected_date)) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."date=".$selected_date;
        }

        if (strlen($parameters) > 1) {
            redirect("interns_attendance.php".$parameters);
        } else {
            redirect("interns_attendance.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        if (!empty($selected_date)) {
            redirect("interns_attendance.php?date=".$selected_date);
        } else {  
            redirect("interns_attendance.php");
        }
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Interns' Attendance");
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
        
        <div class="row align-items-center mb-2">
            <div class="col-md-12">
                <h3>Interns' Attendance</h3>
            </div>
        </div>
        
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
                                <button class="btn btn-danger" type="submit" name="reset">Reset</button>
                            </div>
                        </div>
                    </div>
                    <!--DEPARTMENT DROPDOWN-->
                    <div class="col-12 d-md-flex d-sm-inline-block">
                        <div class="w-lg-fit w-md-100 d-flex align-items-center my-2 me-lg-2 me-md-0">
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= $selected_date ?>" disabled>
                                <div class="input-group-append">
                                    <a class="btn btn-smoke border-dark" href="calendar.php">Select Date</a>
                                </div>
                            </div>                        
                        </div>

                        <div class="w-fit d-flex my-2">
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

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "interns_attendance.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns_attendance.php" ?>" <?php
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
                                            case "3":
                                                echo "Latest";
                                                break;
                                            case "4":
                                                echo "Earliest";
                                                break;
                                        }
                                    }?>
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

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
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

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
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

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
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

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
                                        } ?>>Latest</a></li>
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

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
                                        } ?>>Earliest</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
        <div class="row mb-3">
            <div class="interns-attendance"> <?php
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
                                $sort = " ORDER BY attendance.id DESC";
                                break;
                            case "4":
                                $sort = " ORDER BY attendance.id";
                                break;
                        }
                    }


                    if (!empty($_GET["department"]) && !empty($_GET["search"])) {
                        $interns_attendance = array($selected_date, $_GET["department"], $_GET["search"]);
                        
                        $db->query("SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                        FROM intern_personal_information, intern_wsap_information, departments, attendance
                        WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date AND name=:dept_name AND
                        (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                        CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))".$sort);
                        $db->selectInternsAttendance3($interns_attendance);
                    } else if (!empty($_GET["department"])) {
                        $interns_attendance = array($selected_date, $_GET["department"]);
                        
                        $db->query("SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                        FROM intern_personal_information, intern_wsap_information, departments, attendance
                        WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date AND name=:dept_name".$sort);
                        $db->selectInternsAttendance($interns_attendance);
                    } else if (!empty($_GET["search"])) {
                        $interns_attendance = array($selected_date, $_GET["search"]);
                        
                        $db->query("SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                        FROM intern_personal_information, intern_wsap_information, departments, attendance
                        WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date AND
                        (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                        CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))".$sort);
                        $db->selectInternsAttendance2($interns_attendance);
                    } else {
                        $db->query("SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                        FROM intern_personal_information, intern_wsap_information, departments, attendance
                        WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date".$sort);
                        $db->setAttDate($selected_date);
                    }
                    $db->execute();
                    
                    $conditions = array("AU", "AE", "MS", "AS", "OD", "Late", "No Time out");

                    while ($row = $db->fetch()) { ?>
                        <a class="clickable-card <?php
                            if ($admin_roles_count == 0) { 
                                echo "no-link-card";
                            } ?>" <?php
                            if ($admin_roles_count != 0) { ?>
                                href="attendance_record.php?intern_id=<?= $row["intern_id"] ?>" <?php
                            } ?> draggable="false">
                            <div class="attendance text-center">
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
                                <div class="bottom d-flex justify-content-evenly mt-3">
                                    <div class="w-100">
                                        <p class="m-0 fw-bold fs-e">Time in</p> <?php
                                        if (strlen($row["time_in"]) > 0) {
                                            if ($row["time_in"] == $conditions[0]) { ?>
                                                <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if ($row["time_in"] == $conditions[1]) { ?>
                                                <p class="bg-primary text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_in"], $conditions[5])) { ?>
                                                <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            } else { ?>
                                                <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }
                                        } ?>
                                    </div>
                                    <div class="w-100">
                                        <p class="m-0 fw-bold fs-e">Time out</p> <?php 
                                        if (strlen($row["time_out"]) > 0) {
                                            if ($row["time_out"] == $conditions[0]) { ?>
                                                <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if ($row["time_out"] == $conditions[1]) { ?>
                                                <p class="bg-primary text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if ($row["time_out"] == $conditions[6]) { ?>
                                                <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else { ?>
                                                <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1 fs-d">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </a> <?php
                    } ?>
            </div> <?php
            if ($db->rowCount() == 0) { ?>
                <div class="w-100 text-center my-5">
                    <h3>No Record</h3>
                </div> <?php
            } ?>
        </div>
        
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>