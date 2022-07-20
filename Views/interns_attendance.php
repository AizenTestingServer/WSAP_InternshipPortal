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
                                                
        if (!empty($_GET["view"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."view=".$_GET["view"];
        }

        if (strlen($parameters) > 1) {
            redirect("interns_attendance.php".$parameters);
        } else {
            redirect("interns_attendance.php");
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
            redirect("interns_attendance.php".$parameters);
        } else {
            redirect("interns_attendance.php");
        }
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Interns' Attendance");
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
                <h3>Interns' Attendance</h3>
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
                        
                    <div class="col-12 d-lg-flex d-md-inline-block">
                        <div class="w-lg-fit w-md-100 d-flex align-items-center my-2 me-lg-2 me-md-0">
                            <div class="input-group mb-auto">
                                <input type="text" class="form-control" value="<?= $selected_date ?>" disabled>
                                <div class="input-group-append">
                                    <a class="btn btn-smoke" href="calendar.php">Select Date</a>
                                </div>
                            </div>
                        </div>

                        <div class="w-100 d-md-flex d-sm-inline-block">
                            <!--DEPARTMENT DROPDOWN-->
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                                if (!empty($_GET["view"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
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
                                                
                                            if (!empty($_GET["view"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."view=".$_GET["view"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "interns_attendance.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns_attendance.php" ?>" <?php
                                            } ?>>Earliest</a></li>
                                    </ul>
                                </div>
                            </div>

                            <!--VIEW DROPDOWN-->
                            <div class="ms-auto dropdown my-2">
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
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (!empty($selected_date)) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."date=".$selected_date;
                                        }
                                            
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."view=1";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns_attendance.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns_attendance.php" ?>" <?php
                                        } ?>>Tabular</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                            
                    <div class="w-fit ms-auto">
                        <a type="button" class="btn btn-secondary me-1 mb-1"
                        href="no_time_out_interns.php?date=<?= $selected_date ?>">
                            View No Time out<i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                        <a type="button" class="btn btn-secondary mb-1"
                        href="no_time_in_interns.php?date=<?= $selected_date ?>">
                            View No Time in<i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
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
                        case "3":
                            $sort = " ORDER BY attendance.id DESC";
                            break;
                        case "4":
                            $sort = " ORDER BY attendance.id";
                            break;
                    }
                }

                $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                intern_wsap_information.department_id = departments.id AND
                intern_personal_information.id = attendance.intern_id AND
                att_date=:att_date";

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

                $query = "SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*, attendance.* 
                FROM intern_personal_information, intern_wsap_information, departments, attendance";

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

                if (empty($_GET["view"])) { ?>
                    <div class="interns-attendance"> <?php
                        while ($row = $db->fetch()) { ?>
                            <a class="clickable-card" href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>" draggable="false">
                                <div class="h-100 attendance text-center position-relative" style="padding-bottom: 5rem;">
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
                                    <div class="absolute-bottom absolute-w-100 py-3 d-flex justify-content-evenly" style="bottom: 0;">
                                        <div class="w-50">
                                            <p class="m-0 fw-bold fs-e">Time in</p>
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
                                        <div class="w-50">
                                            <p class="m-0 fw-bold fs-e">Time out</p>
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
                            </a> <?php
                        } ?>
                    </div>  <?php
                } else { ?>
                    <table class="table fs-d text-center">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Department</th>
                                <th scope="col">Time in</th>
                                <th scope="col">Time out</th>
                                <th scope="col">Regular Hours</th>
                                <th scope="col">OT Hours</th>
                                <th scope="col">Valid Rendered Hours</th>
                            </tr>
                        </thead>
                        <tbody> <?php
                            if (isset($_SESSION["intern_id"])) {
                                $count = 0;

                                while ($row = $db->fetch()) {
                                    $count++; ?>
                                    <tr> <?php
                                        if ($row["time_out"] != "NTO") { ?>
                                            <div class="modal fade" id="removeTimeOutModal<?= $row["id"] ?>" tabindex="-1"
                                                aria-labelledby="removeTimeOutModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <div class="modal-title" id="removeTimeOutModalLabel">
                                                                <h5>Remove Time out</h5>
                                                            </div>
                                                            <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                                                <i class="fa-solid fa-close"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <div class="text-center px-5">
                                                                    <h6 class="text-dark mb-0">
                                                                        By removing the time out, the rendered hours on its
                                                                        day will be deducted to the Intern's total rendered hours.<br><br>
                                                                        Do you still want to remove the time out?
                                                                    </h6>
                                                                    <input type="text" name="att_id" class="form-control text-center d-none"
                                                                                value="<?= $row["id"] ?>" readonly>
                                                                    <input type="text" name="rendered_hours" class="form-control text-center d-none"
                                                                                value="<?= $row["rendered_hours"] ?>" readonly>
                                                                    <input type="text" name="att_date" class="form-control text-center d-none"
                                                                                value="<?= $row["att_date"] ?>" readonly>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="submit" name="removeTimeOut" class="btn btn-danger">Remove Time out</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div> <?php
                                        } ?>

                                        <div class="modal fade" id="gpsImageModal<?= $row["id"] ?>" tabindex="-1"
                                            aria-labelledby="gpsImageModalModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div class="modal-title" id="gpsImageModalModalLabel">
                                                            <h5><?= $row["last_name"].", ".$row["first_name"] ?></h5>
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
                                        <td><?= $row["last_name"].", ".$row["first_name"] ?></td>
                                        <td><?= $row["name"] ?></td>
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
                                            <div class="d-flex justify-content-center">
                                                <div class="w-fit p-0 me-1" data-bs-toggle="tooltip" data-bs-placement="left"
                                                    title="View GPS Image">
                                                    <button class="btn btn-primary btn-sm"
                                                        data-bs-toggle="modal"  data-bs-target="#gpsImageModal<?= $row["id"] ?>">
                                                        <i class="fa-solid fa-image fs-a"></i>
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

                if ($db->rowCount() == 0) { ?>
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
    require_once "../Templates/footer.php";
?>