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
        if (!empty($_POST["search_intern"])) {
            if (!empty($_GET["sort"])) {
                redirect("unactivated_interns_accounts.php?search=".$_POST["search_intern"]."&sort=".$_GET["sort"]);
            } else {
                redirect("unactivated_interns_accounts.php?search=".$_POST["search_intern"]);
            }
        } else {
            if (!empty($_GET["sort"])) {
                redirect("unactivated_interns_accounts.php?sort=".$_GET["sort"]);
            } else {
                redirect("unactivated_interns_accounts.php");
            }
        }
        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("unactivated_interns_accounts.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Unacivated Interns' Accounts");
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
                <h3>Unactivated Interns' Account</h3>
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
                                <button type="submit" name="btnAddIntern" class="btn btn-success">Submit</button>
                            </div>
                        </form>
                    </div>
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
                                    <button class="btn btn-danger" name="reset">Reset</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 d-lg-flex d-md-inline-block">
                            <div class="w-100 d-md-flex d-sm-flex">
                                <div class="d-flex my-2">
                                    <a class="btn btn-secondary me-2" href="interns.php">
                                        <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns
                                    </a>
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
                                                if (!empty($_GET["search"])) { ?>
                                                    href="unactivated_interns_accounts.php?search=<?= $_GET["search"] ?>" <?php
                                                } else { ?>
                                                    href="unactivated_interns_accounts.php" <?php
                                                } ?>>Default</a></li>
                                            <li><a class="dropdown-item btn-smoke" <?php
                                                if (!empty($_GET["search"])) { ?>
                                                    href="unactivated_interns_accounts.php?search=<?= $_GET["search"] ?>&sort=1" <?php
                                                } else { ?>
                                                    href="unactivated_interns_accounts.php?sort=1" <?php
                                                } ?>>A-Z</a></li>
                                            <li><a class="dropdown-item btn-smoke" <?php
                                                if (!empty($_GET["search"])) { ?>
                                                    href="unactivated_interns_accounts.php?search=<?= $_GET["search"] ?>&sort=2" <?php
                                                } else { ?>
                                                    href="unactivated_interns_accounts.php?sort=2" <?php
                                                } ?>>Z-A</a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="w-fit my-2 ms-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#addInternModal">Add Intern</button>
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
                                    $sort = " ORDER BY last_name";
                                    break;
                                case "2":
                                    $sort = " ORDER BY last_name DESC";
                                    break;
                            }
                        }
                        
                        if (!empty($_GET["search"])) {                        
                            $db->query("SELECT * FROM intern_personal_information
                            WHERE (SELECT COUNT(*) FROM intern_accounts
                            WHERE intern_accounts.id=intern_personal_information.id) = 0 AND
                            (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                            CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))".$sort);
                            $db->selectInterns($_GET["search"]);
                        } else {
                            $db->query("SELECT * FROM intern_personal_information
                            WHERE (SELECT COUNT(*) FROM intern_accounts
                            WHERE intern_accounts.id=intern_personal_information.id) = 0".$sort);
                        }
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
                } ?>
            </div> <?php
        } else { ?>
            <div id="access-denied">
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