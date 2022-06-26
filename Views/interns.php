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
    
    if (isset($_POST["btnAddIntern"])) {
        if (!empty($_POST["lastName"]) && !empty($_POST["firstName"])) {
            $intern_id = $date->getYear()."-".randomWord();

            $personal_info = array($intern_id,
            ucwords($_POST["lastName"]),
            ucwords($_POST["firstName"]),
            ucwords($_POST["middleName"]));
    
            $db->query("INSERT INTO intern_personal_information (id, last_name, first_name, middle_name)
            VALUES(:intern_id, :last_name, :first_name, :middle_name)");
            $db->insertPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION['personal_success'] = "Successfully added a record.";
        } else {
            $_SESSION['personal_failed'] = "Please fill-out the required fields!";
        }
        redirect('interns.php');
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
            redirect("interns.php".$parameters);
        } else {
            redirect("interns.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("interns.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Interns");
?>
<div class="my-container"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("interns");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>
        
        <div class="row align-items-center mb-2">
            <div class="col-md-12">
                <h3>Interns</h3>
            </div>
        </div> <?php
        if ($admin_roles_count != 0) {
            if (isset($_SESSION['personal_success'])) { ?>
                <div class="alert alert-success text-success">
                    <?php
                        echo $_SESSION['personal_success'];
                        unset($_SESSION['personal_success']);
                    ?>
                </div> <?php
            }

            if (isset($_SESSION['personal_failed'])) { ?>
                <div class="alert alert-danger text-danger">
                    <?php
                        echo $_SESSION['personal_failed'];
                        unset($_SESSION['personal_failed']);
                    ?>
                </div> <?php
            } ?>
            <div class="modal fade" id="addInternModal" tabindex="-1" aria-labelledby="addInternModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addInternModalLabel">Add Intern</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                            <div class="modal-body">
                                <div class="row">
                                    <!-- <div class="col-6 user_input my-1">
                                        <label class="text-indigo mb-2" for="intern_id">Intern ID</label>
                                        <div class="input-group">
                                            <input type="text" name="intern_id" class="form-control" disabled>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-smoke border-dark">Regen</button>
                                            </div>
                                        </div>
                                    </div> -->
                                    <div class="col-12 user_input my-1">
                                        <label class="mb-2" for="lastName">Last Name
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="lastName" class="form-control" maxLength="32">
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="text-indigo mb-2" for="firstName">First Name
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="firstName" class="form-control" maxLength="32">
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="text-indigo mb-2" for="middleName">Middle Name</label>
                                        <input type="text" name="middleName" class="form-control" maxLength="32">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="btnAddIntern" class="btn btn-success">Submit</button>
                                </form>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <?php
        } ?>
        

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
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
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
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
                                            } ?>>Newest Intern</a></li>
                                    </ul>
                                </div>
                            </div> <?php
                            if ($admin_roles_count != 0) { ?>
                                <div class="w-fit my-2 ms-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#addInternModal">Add Intern</button>
                                </div> <?php
                            } ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="row"> <?php
            if ($admin_roles_count != 0) { ?>
                <div class="rounded shadow px-0 mt-3 mb-2">
                    <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                        Unactivated Accounts
                    </h6>
                </div>

                <div class="interns"> <?php
                    $db->query("SELECT * FROM intern_personal_information
                    WHERE (SELECT COUNT(*) FROM intern_accounts
                    WHERE intern_accounts.id=intern_personal_information.id) = 0
                    ORDER BY last_name");
                    $db->execute();

                    while ($row = $db->fetch()) { ?>
                        <div>
                            <div class="intern text-center">
                                <div class="top">
                                    <img class="img-intern mx-auto" src="<?php {
                                        if ($row["gender"] == 0) {
                                            echo "../Assets/img/profile_imgs/default_male.png";
                                        } else {
                                            echo "../Assets/img/profile_imgs/default_female.png";
                                        }
                                    } ?>">
                                </div>
                                <div class="summary-total mt-2 w-fit mx-auto">
                                    <h5 class="mb-0 text-dark">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6><?= $row["id"] ?></h6>
                                </div>
                                <div class="bottom w-100 mt-3">
                                </div>
                            </div>
                        </div><?php
                    } ?>
                </div> <?php
                if ($db->rowCount() == 0) { ?>
                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } else { ?>
                    <a class="btn btn-secondary mx-auto" href="interns_unactivated_accounts.php" style="max-width: 200px;">
                        Show All<i class="fa-solid fa-arrow-right ms-2"></i>
                    </a> <?php
                }
            } ?>
            
            <div class="rounded shadow px-0 mt-3 mb-2">
                <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                    Activated Accounts
                </h6>
            </div>

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
                        <a class="clickable-card" href="profile.php?intern_id=<?= $row["intern_id"] ?>"
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
                                    <h5 class="mb-0 text-dark">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6><?= $row["name"] ?></h6>
                                </div>
                                <div class="bottom w-100 mt-3"> <?php
                                    if ($row["status"] == 0 || $row["status"] == 5) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 pt-1 pb-1"> <?php
                                            if ($row["status"] == 0) {
                                                echo "Inactive";
                                            } else {
                                                echo "Suspended";
                                            } ?>
                                        </p> <?php
                                    }  else if ($row["status"] == 1 || $row["status"] == 4) { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1"> <?php
                                            if ($row["status"] == 1) {
                                                echo "Active";
                                            } else {
                                                echo "Extended";
                                            } ?>
                                        </p> <?php
                                    }   else if ($row["status"] == 2) { ?>
                                        <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                           Offboarded
                                        </p> <?php
                                    }   else if ($row["status"] == 4) { ?>
                                        <p class="bg-dark text-light rounded w-fit m-auto px-2 pt-1 pb-1">
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