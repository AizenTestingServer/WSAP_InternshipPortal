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
    
    if (isset($_POST["addIntern"])) {
        $last_name = toProper(fullTrim($_POST["lastName"]));
        $first_name = toProper(fullTrim($_POST["firstName"]));
        $middle_name = toProper(fullTrim($_POST["middleName"]));
        $gender = $_POST["gender"];
        $department_id = $_POST["department"];

        if (!empty($last_name) && !empty($first_name)) {
            $intern_id = $date->getYear()."-".randomWord();

            $personal_info = array($intern_id, $last_name, $first_name, $middle_name, $gender);
    
            $db->query("INSERT INTO intern_personal_information (id, last_name, first_name, middle_name, gender)
            VALUES(:intern_id, :last_name, :first_name, :middle_name, :gender)");
            $db->insertPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();

            $db->query("INSERT INTO intern_wsap_information (id, department_id) VALUES(:intern_id, :department_id)");
            $db->setInternId($intern_id);
            $db->setDeptId($department_id);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") added an account for ".$last_name.", ".$first_name." (".$intern_id.").";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (NULL, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully added an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("offboarding_forecast.php");
        exit();
    }
    
    if (isset($_POST["removeAccount"])) {
        if (!empty($_POST["intern_id"]) && !empty($_POST["fullName"])) {    
            $db->query("DELETE FROM intern_personal_information WHERE id=:intern_id");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();
            $db->closeStmt();
            
            $db->query("DELETE FROM intern_wsap_information WHERE id=:intern_id");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") removed the account of ".$_POST["fullName"].".";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (NULL, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully removed an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("offboarding_forecast.php");
        exit();
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
            redirect("offboarding_forecast.php".$parameters);
        } else {
            redirect("offboarding_forecast.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("offboarding_forecast.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Interns");
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
                <h3>Offboarding Forecast</h3>
            </div>
        </div>        

        <div class="mb-2">
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
                <form>
                
                <div class="col-12">
                    <div class="w-100 d-md-flex">
                        <div class="d-flex mb-2">
                            <!--DEPARTMENT DROPDOWN-->
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
                                    
                                    if (!empty($_GET["sort"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=".$_GET["sort"];
                                    }

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "offboarding_forecast.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "offboarding_forecast.php" ?>" <?php
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
                                            href="<?= "offboarding_forecast.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "offboarding_forecast.php" ?>" <?php
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
                                        }
                                    } ?>
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
                                            href="<?= "offboarding_forecast.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "offboarding_forecast.php" ?>" <?php
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
                                            href="<?= "offboarding_forecast.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "offboarding_forecast.php" ?>" <?php
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
                                            href="<?= "offboarding_forecast.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "offboarding_forecast.php" ?>" <?php
                                        } ?>>Z-A</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
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
        <div class="row"> <?php
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
            status = 1";

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

            $query = "SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, 
            intern_wsap_information.*, intern_accounts.*,  departments.*
            FROM intern_personal_information, intern_wsap_information, intern_accounts, departments";

            if (strlen($conditions) > 6) {
                $db->query($query.$conditions.$sort);

                if (!empty($_GET["search"])) {
                    $db->selectInternName($_GET["search"]);
                }
                if (!empty($_GET["department"])) {
                    $db->selectDepartment($_GET["department"]);
                }
            }

            $limited = 0;

            $selected_month = $date->getMonth();
            $selected_year = $date->getYear();
            $number_of_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
            $start_day = date("j", strtotime("current monday"));
            
            $end_day = $number_of_days;
            
            if ($limited == 1) {
                $min_days = $number_of_days - $start_day;
                if ($min_days > 6) {
                    $min_days = 6;
                }

                $end_day = $start_day + $min_days;
            }

            $att_db = new Database();
        
            $att_db->query("SELECT * FROM attendance ORDER BY id DESC;");

            $row_count = 0;
            for ($i = $start_day; $i <= $end_day; $i++) {
                $db->execute();
                while ($row = $db->fetch()) {
                    $att_db->execute();
            
                    while ($att_row = $att_db->fetch()) {
                        if ($row["intern_id"] == $att_row["intern_id"]) {
                            $row_lts_att = $att_row;
                            break;
                        }
                    }

                    $rendering_days = floor(($row["target_rendering_hours"]-$row["rendered_hours"])/9);

                    $estimated_weekend_days = floor(($rendering_days/5) * 2);
                    $rendering_days += $estimated_weekend_days;

                    if (!empty($row_lts_att) && $row_lts_att["att_date"] == $date->getNumericDate() && !empty($row_lts_att["time_out"])) {
                        $rendering_days += 1;
                    }

                    $est_offboard_date = strtotime($date->getNumericDate()." + ".$rendering_days." days");

                    if (date("F j, Y", $est_offboard_date) == $date->getMonthName()." ".$i.", ".$selected_year) { ?>
                        <div id="educational-info" class="row rounded shadow mt-4 pb-4 position-relative">
                            <div class="rounded shadow px-0 mt-3 mb-2">
                                <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                                    <?= $date->getMonthName()." ".$i.", ".$selected_year." | ".date("l", strtotime($date->getMonthName()." ".$i.", ".$selected_year)) ?>
                                </h6>
                            </div>

                            <div class="interns"> <?php
                                $db->execute();
                                while ($row = $db->fetch()) {
                                    $att_db->execute();
                            
                                    while ($att_row = $att_db->fetch()) {
                                        if ($row["intern_id"] == $att_row["intern_id"]) {
                                            $row_lts_att = $att_row;
                                            break;
                                        }
                                    }

                                    $rendering_days = floor(($row["target_rendering_hours"]-$row["rendered_hours"])/9);

                                    $estimated_weekend_days = floor(($rendering_days/5) * 2);
                                    $rendering_days += $estimated_weekend_days;

                                    if (!empty($row_lts_att) && $row_lts_att["att_date"] == $date->getNumericDate() && !empty($row_lts_att["time_out"])) {
                                        $rendering_days += 1;
                                    }

                                    $est_offboard_date = strtotime($date->getNumericDate()." + ".$rendering_days." days");

                                    if (date("F j, Y", $est_offboard_date) == $date->getMonthName()." ".$i.", ".$selected_year) {
                                        $row_count++;
                                        
                                        if ($admin_roles_count != 0) { ?>
                                            <a class="clickable-card" href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>"
                                                draggable="false"> <?php
                                        } ?>
                                                <div class="h-100 intern text-center position-relative pb-5">
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
                                                        if ($row["status"] == 0 || $row["status"] == 5) { ?>
                                                            <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                                                if ($row["status"] == 0) {
                                                                    echo "Inactive";
                                                                } else {
                                                                    echo "Suspended";
                                                                } ?>
                                                            </p> <?php
                                                        } else if ($row["status"] == 1 || $row["status"] == 4) { ?>
                                                            <p class="bg-success text-light rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                                                if ($row["status"] == 1) {
                                                                    echo "Active";
                                                                } else {
                                                                    echo "Extended";
                                                                } ?>
                                                            </p> <?php
                                                        } else if ($row["status"] == 2) { ?>
                                                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                                                Offboarded
                                                            </p> <?php
                                                        } else if ($row["status"] == 3) { ?>
                                                            <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                                                Withdrawn
                                                            </p> <?php
                                                        } else if ($row["status"] == 6) { ?>
                                                            <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                                                Terminated
                                                            </p> <?php
                                                        } ?>
                                                    </div>
                                                </div> <?php
                                        if ($admin_roles_count != 0) { ?>
                                            </a> <?php
                                        }
                                    }
                                } ?>
                            </div>
                        </div><?php
                        break;
                    }
                }
            }

            if ($row_count == 0) { ?>
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