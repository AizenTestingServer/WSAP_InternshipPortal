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

    if (isset($_POST["search"])) {
        if (!empty($_POST["search_intern"])) {
            if (!empty($_GET["department"])) {
                if (!empty($_GET["sort"])) {
                    redirect("interns_attendance.php?search=".$_POST["search_intern"]."&department=".$_GET["department"]."&sort=".$_GET["sort"]);
                } else {
                    redirect("interns_attendance.php?search=".$_POST["search_intern"]."&department=".$_GET["department"]);
                }
            } else {
                if (!empty($_GET["sort"])) {
                    redirect("interns_attendance.php?search=".$_POST["search_intern"]."&sort=".$_GET["sort"]);
                } else {
                    redirect("interns_attendance.php?search=".$_POST["search_intern"]);
                }
            }
        } else {
            if (!empty($_GET["department"])) {
                if (!empty($_GET["sort"])) {
                    redirect("interns_attendance.php?department=".$_GET["department"]."&sort=".$_GET["sort"]);
                } else {
                    redirect("interns_attendance.php?department=".$_GET["department"]);
                }
            } else {
                if (!empty($_GET["sort"])) {
                    redirect("interns_attendance.php?sort=".$_GET["sort"]);
                } else {
                    redirect("interns_attendance.php");
                }
            }
        }
        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("interns_attendance.php");
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
        
        <div class="control-header">
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
                        <div class="width-lg-fit width-md-100 d-flex align-items-center my-2 me-2">
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= $date->getDate() ?>" disabled>
                                <div class="input-group-append">
                                    <a><button class="btn btn-smoke border-dark">Select Date</button></a>
                                </div>
                            </div>                        
                        </div>

                        <div class="width-fit d-flex my-2">
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
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["sort"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&sort=<?= $_GET["sort"] ?>" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["sort"])) { ?>
                                                href="interns_attendance.php?sort=<?= $_GET["sort"] ?>" <?php
                                            } else { ?>
                                                href="interns_attendance.php" <?php
                                            }
                                        } ?>> All Departments </a></li> <?php
                                    
                                    $db->query("SELECT * FROM departments ORDER BY name");
                                    $db->execute();
                                    
                                    while ($row = $db->fetch()) { ?>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            if (!empty($_GET["search"])) {
                                                if (!empty($_GET["sort"])) { ?>
                                                    href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $row["name"] ?>&sort=<?= $_GET["sort"] ?>" <?php
                                                } else { ?>
                                                    href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $row["name"] ?>" <?php
                                                }
                                            } else {
                                                if (!empty($_GET["sort"])) { ?>
                                                    href="interns_attendance.php?department=<?= $row["name"] ?>&sort=<?= $_GET["sort"] ?>" <?php
                                                } else { ?>
                                                    href="interns_attendance.php?department=<?= $row["name"] ?>" <?php
                                                }
                                            } ?> > <?= $row["name"] ?>
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
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $_GET["department"] ?>" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?department=<?= $_GET["department"] ?>" <?php
                                            } else { ?>
                                                href="interns_attendance.php" <?php
                                            }
                                        } ?>>Default</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $_GET["nadepartmentme"] ?>&sort=1" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&sort=1" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?department=<?= $_GET["department"] ?>&sort=1" <?php
                                            } else { ?>
                                                href="interns_attendance.php?&sort=1" <?php
                                            }
                                        } ?>>A-Z</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $_GET["department"] ?>&sort=2" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&sort=2" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?department=<?= $_GET["department"] ?>&sort=2" <?php
                                            } else { ?>
                                                href="interns_attendance.php?&sort=2" <?php
                                            }
                                        } ?>>Z-A</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $_GET["department"] ?>&sort=3" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&sort=3" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?department=<?= $_GET["department"] ?>&sort=3" <?php
                                            } else { ?>
                                                href="interns_attendance.php?&sort=3" <?php
                                            }
                                        } ?>>Latest</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        if (!empty($_GET["search"])) {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&department=<?= $_GET["department"] ?>&sort=4" <?php
                                            } else { ?>
                                                href="interns_attendance.php?search=<?= $_GET["search"] ?>&sort=4" <?php
                                            }
                                        } else {
                                            if (!empty($_GET["department"])) { ?>
                                                href="interns_attendance.php?department=<?= $_GET["department"] ?>&sort=4" <?php
                                            } else { ?>
                                                href="interns_attendance.php?&sort=4" <?php
                                            }
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
                        $interns_attendance = array($date->getDate(), $_GET["department"], $_GET["search"]);
                        
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
                        $interns_attendance = array($date->getDate(), $_GET["department"]);
                        
                        $db->query("SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                        FROM intern_personal_information, intern_wsap_information, departments, attendance
                        WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date AND name=:dept_name".$sort);
                        $db->selectInternsAttendance($interns_attendance);
                    } else if (!empty($_GET["search"])) {
                        $interns_attendance = array($date->getDate(), $_GET["search"]);
                        
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
                        $db->selectDate($date->getDate());
                    }
                    $db->execute();
                    
                    $conditions = array("AU", "AE", "MS", "AS", "OD", "Late", "No Time out");

                    while ($row = $db->fetch()) { ?>
                        <!--INTERN CARD 1-->
                        <a class="clickable-card" href="profile.php" draggable="false">
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
                                <div class="summary-total mt-2 width-fit mx-auto">
                                    <h5 class="mb-0 text-dark">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6><?= $row["name"] ?></h6>
                                </div>
                                <div class="bottom d-flex justify-content-evenly mt-3">
                                    <div class="w-100">
                                        <p class="m-0 fw-bold">Time in</p> <?php
                                        if (strlen($row["time_in"]) > 0) {
                                            if( $row["time_in"] == $conditions[0]) { ?>
                                                <p class="bg-danger text-light rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if ($row["time_in"] == $conditions[1]) { ?>
                                                <p class="bg-primary text-light rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }  else if (str_contains($row["time_in"], $conditions[5])) { ?>
                                                <p class="bg-warning text-dark rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            } else { ?>
                                                <p class="bg-success text-light rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_in"] ?>
                                                </p> <?php
                                            }
                                        } ?>
                                    </div>
                                    <div class="w-100">
                                        <p class="m-0 fw-bold">Time out</p> <?php 
                                        if (strlen($row["time_out"]) > 0) {
                                            if ($row["time_out"] == $conditions[0]) { ?>
                                                <p class="bg-danger text-light rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if ($row["time_out"] == $conditions[1]) { ?>
                                                <p class="bg-primary text-light rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else if ($row["time_out"] == $conditions[6]) { ?>
                                                <p class="bg-warning text-dark rounded width-fit m-auto px-2 pt-1 pb-1">
                                                    <?= $row["time_out"] ?>
                                                </p> <?php
                                            }  else { ?>
                                                <p class="bg-success text-light rounded width-fit m-auto px-2 pt-1 pb-1">
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
                <div class="w-100 text-center mt-5">
                    <h3>No Record</h3>
                </div> <?php
            } ?>
        </div>
        
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>