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

    if (isset($_POST["search"])) {
        $parameters = "?";
        if (!empty($_POST["search_admin"])) {
            $parameters = $parameters."search=".$_POST["search_admin"];
        }

        if (!empty($_GET["department"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."department=".$_GET["department"];
        }

        if (isset($_GET["status"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."status=".$_GET["status"];
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (strlen($parameters) > 1) {
            redirect("admins.php".$parameters);
        } else {
            redirect("admins.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("admins.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Admins");
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
                <h3>Admins</h3>
            </div>
        </div>
        
        <div>
            <div class="row">
                <!--SEARCH BUTTON/TEXT-->
                <form method="post">
                    <div class="col-lg-8 col-md-10 col-sm-12">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Search Admin" name="search_admin" value="<?php
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

                                    if (isset($_GET["status"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=".$_GET["status"];
                                    }
                                    
                                    if (!empty($_GET["sort"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=".$_GET["sort"];
                                    }

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "admins.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "admins.php" ?>" <?php
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>> <?= $row["name"] ?>
                                        </a></li> <?php
                                    } ?>
                                </ul>
                            </div>
                            <!--STATUS DROPDOWN-->
                            <div class="dropdown me-2">
                                <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false"> <?php
                                    if (isset($_GET["status"])) {
                                        switch ($_GET["status"]) {
                                            case "0":
                                                echo "Inactive";
                                                break;
                                            case "1":
                                                echo "Active";
                                                break;
                                            case "2":
                                                echo "Offboarded";
                                                break;
                                            case "3":
                                                echo "Withdrawn";
                                                break;
                                            case "4":
                                                echo "Extended";
                                                break;
                                            case "5":
                                                echo "Suspended";
                                                break;
                                            case "6":
                                                echo "Terminated";
                                                break;
                                        }
                                    } else {
                                        echo "All Status";
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
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>All Status</a></li>
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
                                        $parameters = $parameters."status=0";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Inactive</a></li>
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
                                        $parameters = $parameters."status=1";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Active</a></li>
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
                                        $parameters = $parameters."status=2";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Offboarded</a></li>
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
                                        $parameters = $parameters."status=3";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Withdrawn</a></li>
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
                                        $parameters = $parameters."status=4";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Extended</a></li>
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
                                        $parameters = $parameters."status=5";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Suspended</a></li>
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
                                        $parameters = $parameters."status=6";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Terminated</a></li>
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=1";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=2";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=3";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
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

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=4";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "admins.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "admins.php" ?>" <?php
                                        } ?>>Newest Intern</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="interns"> <?php
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
                                $sort = " ORDER BY intern_wsap_information.onboard_date";
                                break;
                            case "4":
                                $sort = " ORDER BY intern_wsap_information.onboard_date DESC";
                                break;
                        }
                    }

                    $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                    intern_personal_information.id = intern_roles.intern_id AND
                    intern_wsap_information.department_id = departments.id AND
                    (SELECT COUNT(*) FROM roles WHERE admin = 1 AND roles.id = intern_roles.role_id) > 0";
    
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
                    if (isset($_GET["status"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." intern_wsap_information.status=:status";
                    }
    
                    $query = "SELECT DISTINCT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                    FROM intern_personal_information, intern_wsap_information, departments, intern_roles";
    
                    if (strlen($conditions) > 6) {
                        $db->query($query.$conditions.$sort);
    
                        if (!empty($_GET["search"])) {
                            $db->selectInternName($_GET["search"]);
                        }
                        if (!empty($_GET["department"])) {
                            $db->selectDepartment($_GET["department"]);
                        }
                        if (isset($_GET["status"])) {
                            $db->selectStatus($_GET["status"]);
                        }
                    }
                    $db->execute();

                    $roles_db = new Database();

                    $roles_db->query("SELECT intern_roles.*, roles.*
                    FROM intern_roles, roles
                    WHERE intern_roles.role_id = roles.id AND roles.admin = 1");

                    while ($row = $db->fetch()) { ?>
                        <a class="clickable-card" href="profile.php?intern_id=<?= $row["intern_id"] ?>"
                            draggable="false">
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
                                <div class="summary-total w-fit mx-auto mt-2 mb-4">
                                    <h5 class="mb-0 text-dark fs-regular">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6 class="fs-f"> <?php
                                        $roles_db->execute();
                                        while ($role = $roles_db->fetch()) {
                                            if ($row["intern_id"] == $role["intern_id"]) {
                                                echo $role["name"]; ?> <br> <?php
                                            }
                                        } ?>
                                     </h6>
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
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1 fs-d">
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