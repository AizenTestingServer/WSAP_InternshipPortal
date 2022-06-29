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

    $current_level = 0;
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

    if (isset($_POST["search"])) {
        $parameters = "?";
        if (!empty($_POST["search_intern"])) {
            $parameters = $parameters."search=".$_POST["search_intern"];
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
            redirect("roles.php".$parameters);
        } else {
            redirect("roles.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("roles.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Roles");
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
                <h3>Roles</h3>
            </div>
        </div>  <?php
        if ($admin_roles_count != 0) { ?>
            <div>
                <form method="post">
                    <div class="row">
                        <!--SEARCH BUTTON/TEXT-->
                        <div class="col-lg-8 col-md-10 col-sm-12">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Search Role" name="search_intern" value="<?php
                                if (!empty($_GET["search"])) {
                                    echo $_GET["search"];
                                } ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-indigo" type="submit" name="search">Search</button>
                                    <button class="btn btn-danger" name="reset">Reset</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="w-100 d-lg-flex d-md-inline-block">
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
                                                
                                            if (!empty($_GET["brand"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."brand=".$_GET["brand"];
                                            }
                                            
                                            if (!empty($_GET["sort"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=".$_GET["sort"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "roles.php" ?>" <?php
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
                                                
                                                if (!empty($_GET["brand"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."brand=".$_GET["brand"];
                                                }
                                                
                                                if (!empty($_GET["sort"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=".$_GET["sort"];
                                                }

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
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
                                                href="<?= "roles.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "roles.php" ?>" <?php
                                            } ?>> All Brands </a></li> <?php
                                            
                                            $db->query("SELECT * FROM brands ORDER BY name");
                                            $db->execute();
                                            
                                            while ($row = $db->fetch()) { ?>
                                                <li><a class="dropdown-item btn-smoke" <?php
                                                $parameters = "?";
                                                if (!empty($_GET["search"])) {
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
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
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
                                                $parameters = "?";
                                                if (!empty($_GET["search"])) {
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
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
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
                                                
                                                if (!empty($_GET["brand"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."brand=".$_GET["brand"];
                                                }

                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=1";

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
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
                                                
                                                if (!empty($_GET["brand"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."brand=".$_GET["brand"];
                                                }
                                                
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=2";

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
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
                                                
                                                if (!empty($_GET["brand"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."brand=".$_GET["brand"];
                                                }
                                                
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=3";

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
                                                } ?>>Highest Level</a></li>
                                            <li><a class="dropdown-item btn-smoke" <?php
                                                $parameters = "?";
                                                if (!empty($_GET["search"])) {
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
                                                    href="<?= "roles.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "roles.php" ?>" <?php
                                                } ?>>Lowest Level</a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="w-fit my-2 ms-auto">
                                    <a type="button" class="btn btn-primary" href="role.php">
                                        <i class="fa-solid fa-add me-2"></i>Add Role
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div> <?php
            if (isset($_SESSION["role_success"])) { ?>
                <div class="alert alert-success text-success">
                    <?php
                        echo $_SESSION["role_success"];
                        unset($_SESSION["role_success"]);
                    ?>
                </div> <?php
            }?>
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

                    $conditions = " WHERE";
                    $roles = array();
                    
                    if (!empty($_GET["search"])) {
                        array_push($roles, $_GET["search"]);
                        $conditions = $conditions." roles.name LIKE CONCAT('%', :role_name, '%')";
                    }
                    if (!empty($_GET["department"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        array_push($roles, $_GET["department"]);
                        $conditions = $conditions." departments.name=:dept_name";
                    }
                    if (!empty($_GET["brand"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        array_push($roles, $_GET["brand"]);
                        $conditions = $conditions." brands.name=:brand_name";
                    }

                    $query = "SELECT roles.*, roles.name AS role_name, roles.id AS role_id,
                    brands.*, brands.name AS brand_name, departments.*, departments.name AS dept_name
                    FROM roles LEFT JOIN brands ON roles.brand_id = brands.id LEFT JOIN departments
                    ON roles.department_id = departments.id";
                    
                    if (strlen($conditions) > 6) {
                        $db->query($query.$conditions.$sort);
                        
                        if (!empty($_GET["search"]) && !empty($_GET["department"]) && !empty($_GET["brand"])) {
                            $db->selectRoles7($roles);
                        } else if (!empty($_GET["department"]) && !empty($_GET["brand"])) {
                            $db->selectRoles6($roles);
                        } else if (!empty($_GET["search"]) && !empty($_GET["brand"])) {
                            $db->selectRoles5($roles);
                        } else if (!empty($_GET["search"]) && !empty($_GET["department"])) {
                            $db->selectRoles4($roles);
                        } else if (!empty($_GET["brand"])) {
                            $db->selectRoles3($roles);
                        } else if (!empty($_GET["department"])) {
                            $db->selectRoles2($roles);
                        } else if (!empty($_GET["search"])) {
                            $db->selectRoles($roles);
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
                                    <a class="btn btn-secondary btn-sm" href="role.php?role_id=<?= $row["role_id"] ?>">
                                        <i class="fa-solid fa-pen fs-a"></i>
                                    </a> <?php
                                } else { ?>
                                    <a class="btn btn-secondary btn-sm disabled">
                                        <i class="fa-solid fa-pen fs-a"></i>
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
        } else {
            include_once "access_denied.php";
        } ?>        
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>