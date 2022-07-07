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
            VALUES (null, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully added an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("unactivated_interns_accounts.php");
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
            VALUES (null, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully removed an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("unactivated_interns_accounts.php");
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
            redirect("unactivated_interns_accounts.php".$parameters);
        } else {
            redirect("unactivated_interns_accounts.php");
        }
        
        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("unactivated_interns_accounts.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Unacivated Interns' Accounts");
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
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Unactivated Interns' Accounts</h3>
            </div>
        </div> <?php
        if ($admin_roles_count != 0) { ?>
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
                                        <label class="mb-2" for="intern_id">Intern ID</label>
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
                                        <label class="mb-2" for="firstName">First Name
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="firstName" class="form-control" maxLength="32">
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="mb-2" for="middleName">Middle Name</label>
                                        <input type="text" name="middleName" class="form-control" maxLength="32">
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="mb-2" for="gender">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="0">Male</option>
                                            <option value="1">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-12 user_input my-1">
                                        <label class="mb-2" for="department">Department</label>
                                        <select name="department" class="form-select"> <?php
                                            $db->query("SELECT * FROM departments ORDER BY name");
                                            $db->execute();
                                            
                                            while ($row = $db->fetch()) { ?>
                                                <option value="<?= $row["id"] ?>"><?= $row["name"] ?> </option> <?php
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" name="addIntern" class="btn btn-success">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

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
                    
                    <div class="col-12">
                        <div class="w-100 d-lg-flex">
                            <div class="d-md-flex mb-2">
                                <div class="mb-2">
                                    <a class="btn btn-secondary me-2" href="interns.php">
                                        <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns
                                    </a>
                                </div>

                                <div class="d-flex">
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
                                                href="<?= "unactivated_interns_accounts.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "unactivated_interns_accounts.php" ?>" <?php
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
                                                    href="<?= "unactivated_interns_accounts.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "unactivated_interns_accounts.php" ?>" <?php
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
                                                    href="<?= "unactivated_interns_accounts.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "unactivated_interns_accounts.php" ?>" <?php
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
                                                    href="<?= "unactivated_interns_accounts.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "unactivated_interns_accounts.php" ?>" <?php
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
                                                    href="<?= "unactivated_interns_accounts.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "unactivated_interns_accounts.php" ?>" <?php
                                                } ?>>Z-A</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-fit ms-auto">
                                <button type="button" class="btn btn-primary mb-1" data-bs-toggle="modal" 
                                    data-bs-target="#addInternModal">
                                    <i class="fa-solid fa-plus me-2"></i>Add Intern
                                </button>
                                <button class="btn btn-secondary mb-1" onclick="copyRecords()">
                                    Copy Records as Text
                                </button>
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
            <div class="row">
                <div class="interns"> <?php
                        $sort = " ORDER BY intern_personal_information.last_name";
                        if (!empty($_GET["sort"])) {
                            switch ($_GET["sort"]) {
                                case "1":
                                    $sort = " ORDER BY last_name";
                                    break;
                                case "2":
                                    $sort = " ORDER BY last_name DESC";
                                    break;
                            }
                        }

                        $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_wsap_information.department_id = departments.id AND
                        (SELECT COUNT(*) FROM intern_accounts WHERE intern_accounts.id=intern_personal_information.id) = 0";
    
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
    
                        $query = "SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                        FROM intern_personal_information, intern_wsap_information, departments";
    
                        if (strlen($conditions) > 6) {
                            $db->query($query.$conditions.$sort);
        
                            if (!empty($_GET["search"])) {
                                $db->selectInternName($_GET["search"]);
                            }
                            if (!empty($_GET["department"])) {
                                $db->selectDepartment($_GET["department"]);
                            }
                        }
                        $db->execute();

                        $interns_info_text = "\"Unactivated Interns' Accounts\\n\\n\"\n";

                        if (empty($_GET["department"])) {
                            $interns_info_text .= "+ \"All Departments:\\n\"\n";
                        } else {
                            $interns_info_text .= "+ \"".$_GET["department"]." Department:\\n\"\n";
                        }

                        while ($row = $db->fetch()) {
                            $interns_info_text .= "+ \"".$row["last_name"].", ".$row["first_name"]." - ".$row["intern_id"];
                            if (empty($_GET["department"])) {
                                $interns_info_text .= " - ".$row["name"]."\\n\"\n";
                            } else {
                                $interns_info_text .= "\\n\"\n";
                            } ?>
                            <div class="modal fade" id="removeAccountModal<?= $row["intern_id"] ?>" tabindex="-1"
                                aria-labelledby="removeAccountModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="removeAccountModalLabel">Remove Account</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                            <div class="modal-body">
                                                <div class="intern text-center">
                                                    <div class="top">
                                                        <img class="img-intern mx-auto" src="<?php {
                                                            if ($row["gender"] == 0) {
                                                                echo "../Assets/img/profile_imgs/default_male.png";
                                                            } else {
                                                                echo "../Assets/img/profile_imgs/default_female.png";
                                                            }
                                                        } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                                                    </div>
                                                    <div class="summary-total mt-2 w-fit mx-auto">
                                                        <h5 class="text-dark fs-regular mb-0">
                                                            <?= $row["last_name"].", ".$row["first_name"] ?>
                                                        </h5>
                                                        <h6 class="fs-f mb-0"><?= $row["name"] ?></h6>
                                                        <h6 class="fs-d fw-bold"><?= $row["intern_id"] ?></h6>
                                                        <input type="text" name="intern_id" class="form-control text-center d-none mt-2"
                                                            value="<?= $row["intern_id"] ?>" readonly>
                                                        <input type="text" name="fullName" class="form-control text-center d-none mt-2"
                                                            value="<?= $row["last_name"].", ".$row["first_name"] ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="removeAccount" class="btn btn-danger">Remove</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative">
                                <div class="intern text-center">
                                    <div class="top">
                                        <img class="img-intern mx-auto" src="<?php
                                            if ($row["gender"] == 0) {
                                                echo "../Assets/img/profile_imgs/default_male.png";
                                            } else {
                                                echo "../Assets/img/profile_imgs/default_female.png";
                                            } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                                    </div>
                                    <div class="summary-total mt-2 w-fit mx-auto">
                                        <h5 class="mb-0 text-dark fs-regular">
                                            <?= $row["last_name"].", ".$row["first_name"] ?>
                                        </h5>
                                        <h6 class="fs-f mb-0"><?= $row["name"] ?></h6>
                                        <h6 class="fs-d fw-bold"><?= $row["intern_id"] ?></h6>
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-sm top-right" data-bs-toggle="modal" 
                                    data-bs-target="#removeAccountModal<?= $row["intern_id"] ?>">
                                    <i class="fa-solid fa-xmark fs-c"></i>
                                </button>
                            </div> <?php
                        } ?>
                </div> <?php
                if ($db->rowCount() == 0) { 
                    $interns_info_text .= "+ \"No Record\"\n"; ?>
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
    function copyRecords() {
        var copyText = <?= "\"WSAP Internship Portal: https://wsapinternshipportal.com/\\n\\n\" +\n".$interns_info_text; ?>;
        navigator.clipboard.writeText(copyText.trim());
        alert("The records are copied to clipboard.");
    }
</script>
<?php
    require_once "../Templates/footer.php";
?>