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

    $current_level = 0;
    if (!empty($_GET["intern_id"])) {
        $db->query("SELECT MAX(roles.admin_level) AS max_level
        FROM intern_personal_information, intern_roles, roles
        WHERE intern_personal_information.id=intern_roles.intern_id AND
        intern_roles.role_id=roles.id AND roles.admin=1 AND
        intern_personal_information.id=:intern_id");
        $db->setInternId($_SESSION["intern_id"]);
        $db->execute();
        if ($value = $db->fetch()) {
            $current_level = $value["max_level"];
        }

        $db->query("SELECT intern_personal_information.id AS intern_id, intern_personal_information.*,
        intern_wsap_information.*, intern_accounts.*, departments.*
        FROM intern_personal_information, intern_wsap_information, intern_accounts, departments
        WHERE intern_personal_information.id = intern_wsap_information.id AND
        intern_personal_information.id = intern_accounts.id AND
        intern_wsap_information.department_id = departments.id AND
        intern_personal_information.id=:intern_id");
        $db->setInternId($_GET["intern_id"]);
        $db->execute();
        $value = $db->fetch();
        $intern_count = $db->rowCount();

        if ($intern_count == 0) {
            redirect("assign_roles.php");
            exit();
        }
    }

    if (isset($_POST["searchRole"])) {
        $parameters = "?intern_id=".$_GET["intern_id"];
        if (!empty($_POST["search_role"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."search=".$_POST["search_role"];
        }

        if (!empty($_GET["department"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."department=".$_GET["department"];
        }
                                                
        if (!empty($_GET["brand"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."brand=".$_GET["brand"];
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (strlen($parameters) > 1) {
            redirect("assign_roles.php".$parameters);
        } else {
            redirect("assign_roles.php");
        }

        exit();
    }

    if (isset($_POST["resetRole"])) {
        redirect("assign_roles.php?intern_id=".$_GET["intern_id"]);
        exit();
    }
    
    if (isset($_POST["removeRole"])) {
        if (!empty($_POST["intern_role_id"])) {
            $db->query("DELETE FROM intern_roles WHERE id=:id");
            $db->setId($_POST["intern_role_id"]);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") removed the ".$_POST["role_name"]." role of ".$value["last_name"].", ".$value["first_name"].".";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (null, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["role_success"] = "Successfully removed a role from intern.";
        } else {
            $_SESSION["role_failed"] = "Please fill-out the required fields!";
        }

        redirect("assign_roles.php?intern_id=".$_GET["intern_id"]);
        exit();
    }

    if (isset($_POST["searchIntern"])) {
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
            redirect("assign_roles.php".$parameters);
        } else {
            redirect("assign_roles.php");
        }

        exit();
    }

    if (isset($_POST["resetIntern"])) {
        redirect("assign_roles.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Assign Roles");
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
                <h3>Assign Roles</h3>
            </div>
        </div>  <?php
        if ($admin_roles_count != 0) {
            if (!empty($_GET["intern_id"])) { ?>
                <div class="w-100 d-md-flex p-3 w-fit">
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
                        } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
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
                                <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                    if ($value["status"] == 0) {
                                        echo "Inactive";
                                    } else {
                                        echo "Suspended";
                                    } ?>
                                </p> <?php
                            }  else if ($value["status"] == 1 || $value["status"] == 4) { ?>
                                <p class="bg-success text-light rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                    if ($value["status"] == 1) {
                                        echo "Active";
                                    } else {
                                        echo "Extended";
                                    } ?>
                                </p> <?php
                            }   else if ($value["status"] == 2) { ?>
                                <p class="bg-secondary text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                    Offboarded
                                </p> <?php
                            }   else if ($value["status"] == 4) { ?>
                                <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                    Withdrawn
                                </p> <?php
                            }   else if ($value["status"] == 6) { ?>
                                <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                    Terminated
                                </p> <?php
                            } ?>
                        </div>
                    </div>
                </div>

                <div class="w-fit my-2">
                    <a class="btn btn-secondary btn-sm"
                        href="edit_interns_profile.php?intern_id=<?= $_GET["intern_id"] ?>">
                        <i class="fa-solid fa-pen me-2"></i>Edit Profile
                    </a>
                </div>

                <div id="roles" class="row rounded shadow mb-4 pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                            Current Roles
                        </h6>
                    </div>
                    
                    <div class="p-4"> <?php
                        if (isset($_SESSION["role_success"])) { ?>
                            <div class="alert alert-success text-success">
                                <?php
                                    echo $_SESSION["role_success"];
                                    unset($_SESSION["role_success"]);
                                ?>
                            </div> <?php
                        }
                        
                        if (isset($_SESSION["role_failed"])) { ?>
                            <div class="alert alert-danger text-danger">
                                <?php
                                    echo $_SESSION["role_failed"];
                                    unset($_SESSION["role_failed"]);
                                ?>
                            </div> <?php
                        } ?>
                    </div>

                    <table class="table caption-top fs-d text-center mt-2">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Brand</th>
                                <th scope="col">Department</th>
                                <th scope="col">Admin</th>
                                <th scope="col">Level</th>
                            </tr>
                        </thead>
                        <tbody> <?php
                            $db->query("SELECT intern_roles.*, intern_roles.id AS intern_role_id,
                            roles.*, roles.name AS role_name, brands.*, brands.name AS brand_name,
                            departments.*, departments.name AS dept_name
                            FROM intern_roles, roles
                            LEFT JOIN brands ON roles.brand_id = brands.id 
                            LEFT JOIN departments ON roles.department_id = departments.id
                            WHERE intern_roles.role_id=roles.id AND intern_roles.intern_id=:intern_id");
                            $db->setInternId($_GET["intern_id"]);
                            $db->execute();

                            $count = 0;
                            while ($row = $db->fetch()) {
                                $count++;  ?>
                                <tr> <?php
                                    if ($row["admin_level"] < $current_level) { ?>
                                        <div class="modal fade" id="removeRoleModal<?= $row["intern_role_id"] ?>" tabindex="-1"
                                            aria-labelledby="removeRoleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h5 class="modal-title" id="removeRoleModalLabel">
                                                                Remove Role from Intern
                                                            </h5>
                                                            <h6 class="modal-title fs-f ms-2" id="removeRoleModalLabel">
                                                                <?= $value["last_name"].", ".$value["first_name"] ?>
                                                            </h6>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="text-center">
                                                                <div class="summary-total mt-2 w-fit mx-auto">
                                                                    <h5 class="text-dark"><?= $row["role_name"] ?></h5>
                                                                    <h6 class="fs-f mb-0"><?php
                                                                        if (!empty($row["dept_name"])) {
                                                                            echo $row["dept_name"];
                                                                        } else {
                                                                            echo "No Department";
                                                                        } ?></h6>
                                                                    <h6 class="fs-f"><?php
                                                                        if (!empty($row["brand_name"])) {
                                                                            echo $row["brand_name"];
                                                                        } else {
                                                                            echo "No Brand";
                                                                        } ?></h6>
                                                                    <input type="text" name="intern_role_id" class="form-control text-center d-none mt-2"
                                                                        value="<?= $row["intern_role_id"] ?>" readonly>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" name="removeRole" class="btn btn-danger">Remove</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div> <?php
                                    } ?>
                                    <th scope="row"><?= $count ?></th>
                                    <td><?= $row["role_name"] ?></td>
                                    <td><?php
                                    if (!empty($row["brand_name"])) {
                                        echo $row["brand_name"];
                                    } else {
                                        echo "No Brand";
                                    } ?></td>
                                    <td><?php
                                    if (!empty($row["dept_name"])) {
                                        echo $row["dept_name"];
                                    } else {
                                        echo "No Department";
                                    } ?></td>
                                    <td><?php
                                        if ($row["admin"] == 1) {
                                            echo "Yes";;
                                        } else {
                                            echo "No";
                                        } ?></td>
                                    <td><?= $row["admin_level"] ?></td>
                                    <td> <?php
                                        if ($row["admin_level"] < $current_level) { ?>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#removeRoleModal<?= $row["intern_role_id"] ?>">
                                                <i class="fa-solid fa-xmark fs-a"></i>
                                            </button> <?php
                                        } else { ?>
                                            <button class="btn btn-secondary btn-sm disabled">
                                                <i class="fa-solid fa-xmark fs-a"></i>
                                            </button> <?php
                                        } ?>
                                    </td>
                                </tr> <?php
                            } ?>
                        </tbody>
                    </table> <?php
                    if ($db->rowCount() == 0) { ?>
                        <div class="w-100 text-center my-5">
                            <h3>No Record</h3>
                        </div> <?php
                    } ?>
                </div>

                <div>
                    <div class="row">
                        <!--SEARCH BUTTON/TEXT-->
                        <form method="post">
                            <div class="col-lg-8 col-md-10 col-sm-12">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" placeholder="Search Role" name="search_role" value="<?php
                                    if (!empty($_GET["search"])) {
                                        echo $_GET["search"];
                                    } ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-indigo" type="submit" name="searchRole">Search</button>
                                        <button class="btn btn-danger" name="resetRole">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                            
                        <div class="col-12">
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
                                        $parameters = "?intern_id=".$_GET["intern_id"];
                                        if (!empty($_GET["search"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }
                                            
                                        if (!empty($_GET["brand"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."brand=".$_GET["brand"];
                                        }
                                        
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "assign_roles.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "assign_roles.php" ?>" <?php
                                        } ?>> All Departments </a></li> <?php
                                        
                                        $db->query("SELECT * FROM departments ORDER BY name");
                                        $db->execute();
                                        
                                        while ($row = $db->fetch()) { ?>
                                            <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($row["name"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$row["name"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }
                                            
                                            if (!empty($_GET["sort"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=".$_GET["sort"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>> <?= $row["name"] ?>
                                            </a></li> <?php
                                        } ?>
                                    </ul>
                                </div>
                                <!--BRAND DROPDOWN-->
                                <div class="dropdown align-center me-2">
                                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                    data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                                        if (empty($_GET["brand"])) {
                                            echo "All Brands";
                                        } else {
                                            echo $_GET["brand"];
                                        }?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?intern_id=".$_GET["intern_id"];
                                        if (!empty($_GET["search"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
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
                                            href="<?= "assign_roles.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "assign_roles.php" ?>" <?php
                                        } ?>> All Brands </a></li> <?php
                                        
                                        $db->query("SELECT * FROM brands ORDER BY name");
                                        $db->execute();
                                        
                                        while ($row = $db->fetch()) { ?>
                                            <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }
                                        
                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }

                                            if (!empty($row["name"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$row["name"];
                                            }
                                            
                                            if (!empty($_GET["sort"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=".$_GET["sort"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                    echo "Highest Level";
                                                    break;
                                                case "4":
                                                    echo "Lowest Level";
                                                    break;
                                            }
                                        }?>
                                    </button>
                                    <ul class="dropdown-menu me-2z" aria-labelledby="dropdownMenuButton1" name="sort">
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>Default</a></li>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }

                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=1";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>A-Z</a></li>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=2";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>Z-A</a></li>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=3";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>Highest Level</a></li>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?intern_id=".$_GET["intern_id"];
                                            if (!empty($_GET["search"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }
                                            
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=4";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>Lowest Level</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <table class="table caption-top fs-d text-center mt-2">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Brand</th>
                            <th scope="col">Department</th>
                            <th scope="col">Admin</th>
                            <th scope="col">Level</th>
                        </tr>
                    </thead>
                    <tbody> <?php
                        $sort = " ORDER BY roles.name";
                        if (!empty($_GET["sort"])) {
                            switch ($_GET["sort"]) {
                                case "1":
                                    $sort = " ORDER BY roles.name";
                                    break;
                                case "2":
                                    $sort = " ORDER BY roles.name DESC";
                                    break;
                                case "3":
                                    $sort = " ORDER BY roles.admin_level DESC";
                                    break;
                                case "4":
                                    $sort = " ORDER BY roles.admin_level";
                                    break;
                            }
                        }

                        $conditions = " WHERE NOT EXISTS
                        (SELECT intern_roles.* FROM intern_roles
                        WHERE intern_roles.role_id=roles.id AND
                        intern_roles.intern_id=:intern_id)";
                    
                        if (!empty($_GET["search"])) {
                            if (strlen($conditions) > 6) {
                                $conditions = $conditions." AND";
                            }
                            $conditions = $conditions." roles.name LIKE CONCAT('%', :role_name, '%')";
                        }
                        if (!empty($_GET["department"])) {
                            if (strlen($conditions) > 6) {
                                $conditions = $conditions." AND";
                            }
                            $conditions = $conditions." departments.name=:dept_name";
                        }
                        if (!empty($_GET["brand"])) {
                            if (strlen($conditions) > 6) {
                                $conditions = $conditions." AND";
                            }
                            $conditions = $conditions." brands.name=:brand_name";
                        }

                        $query = "SELECT roles.*, roles.name AS role_name, roles.id AS role_id,
                        brands.*, brands.name AS brand_name, departments.*, departments.name AS dept_name
                        FROM roles LEFT JOIN brands ON roles.brand_id = brands.id LEFT JOIN departments
                        ON roles.department_id = departments.id";
                        
                        if (strlen($conditions) > 6) {
                            $db->query($query.$conditions.$sort);
                            $db->setInternId($_GET["intern_id"]);
                        
                            if (!empty($_GET["brand"])) {
                                $db->selectBrand($_GET["brand"]);
                            }
                            if (!empty($_GET["department"])) {
                                $db->selectDepartment($_GET["department"]);
                            }
                             if (!empty($_GET["search"])) {
                                $db->selectRoleName($_GET["search"]);
                            }
                        } else {
                            $db->query($query.$sort);
                        }
                        $db->execute();

                        $count = 0;
                        while ($row = $db->fetch()) {
                            $count++;  ?>
                            <tr>
                                <th scope="row"><?= $count ?></th>
                                <td><?= $row["role_name"] ?></td>
                                <td><?php
                                if (!empty($row["brand_name"])) {
                                    echo $row["brand_name"];
                                } else {
                                    echo "No Brand";
                                } ?></td>
                                <td><?php
                                if (!empty($row["dept_name"])) {
                                    echo $row["dept_name"];
                                } else {
                                    echo "No Department";
                                } ?></td>
                                <td><?php
                                    if ($row["admin"] == 1) {
                                        echo "Yes";;
                                    } else {
                                        echo "No";
                                    } ?></td>
                                <td><?= $row["admin_level"] ?></td>
                                <td> <?php
                                    if ($row["admin_level"] < $current_level) { ?>
                                        <a class="btn btn-primary btn-sm"
                                            href="role_assigned.php?intern_id=<?= $_GET["intern_id"]?>&role_id=<?= $row["role_id"] ?>">
                                            <i class="fa-solid fa-add fs-a"></i>
                                        </a> <?php
                                    } else { ?>
                                        <a class="btn btn-secondary btn-sm disabled">
                                            <i class="fa-solid fa-add fs-a"></i>
                                        </a> <?php
                                    } ?>
                                </td>
                            </tr> <?php
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
                                        <button class="btn btn-indigo" type="submit" name="searchIntern">Search</button>
                                        <button class="btn btn-danger" name="resetIntern">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                            
                        <div class="col-12">
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
                                            href="<?= "assign_roles.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
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
                                                href="<?= "assign_roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "assign_roles.php" ?>" <?php
                                            } ?>>Newest Intern</a></li>
                                    </ul>
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
                        intern_personal_information.id = intern_accounts.id AND
                        intern_wsap_information.department_id = departments.id";
    
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
                        $db->execute();

                        while ($row = $db->fetch()) { ?>
                            <a class="clickable-card" href="assign_roles.php?intern_id=<?= $row["intern_id"] ?>"
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
                                        } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                                    </div>
                                    <div class="summary-total mt-2 w-fit mx-auto">
                                        <h5 class="mb-0 text-dark fs-regular">
                                            <?= $row["last_name"].", ".$row["first_name"] ?>
                                        </h5>
                                        <h6 class="fs-f"><?= $row["name"] ?></h6>
                                    </div>
                                    <div class="bottom w-100 mt-3"> <?php
                                        if ($row["status"] == 0 || $row["status"] == 5) { ?>
                                            <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                                if ($row["status"] == 0) {
                                                    echo "Inactive";
                                                } else {
                                                    echo "Suspended";
                                                } ?>
                                            </p> <?php
                                        }  else if ($row["status"] == 1 || $row["status"] == 4) { ?>
                                            <p class="bg-success text-light rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                                if ($row["status"] == 1) {
                                                    echo "Active";
                                                } else {
                                                    echo "Extended";
                                                } ?>
                                            </p> <?php
                                        }   else if ($row["status"] == 2) { ?>
                                            <p class="bg-secondary text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                            Offboarded
                                            </p> <?php
                                        }   else if ($row["status"] == 4) { ?>
                                            <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                            Withdrawn
                                            </p> <?php
                                        }   else if ($row["status"] == 6) { ?>
                                            <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
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