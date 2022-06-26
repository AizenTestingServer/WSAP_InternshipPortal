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

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Roles");
?>

<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("roles");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>

        <h3>Roles</h3>
        <div class="col-md-12">
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
                                                    echo "Highest Level";
                                                    break;
                                                case "4":
                                                    echo "Lower Level";
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
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=4";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "interns.php" ?>" <?php
                                            } ?>>Lowest Level</a></li>
                                    </ul>
                                </div>
                            </div> <?php
                            if ($admin_roles_count != 0) { ?>
                                <div class="w-fit my-2 ms-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#addRoleModal">Add Role</button>
                                </div> <?php
                            } ?>
                        </div>
                    </div>
                </div>
            </form>
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
            <tbody>
                <?php
                if (isset($_SESSION["intern_id"])) {
                    $db->query("SELECT roles.*, roles.name as role_name,
                    brands.*, brands.name as brand_name,
                    departments.*, departments.name as dept_name
                    FROM roles, brands, departments
                    WHERE roles.brand_id = brands.id AND
                    roles.department_id = departments.id
                    ORDER BY roles.name;");
                    $db->setInternId($_SESSION["intern_id"]);
                    $db->execute();

                    $count = 0;
                    while ($row = $db->fetch()) {
                        $count++;  ?>
                        <tr>
                            <th scope="row"><?= $count ?></th>
                            <td><?= $row["role_name"] ?></td>
                            <td><?= $row["brand_name"] ?></td>
                            <td><?= $row["dept_name"] ?></td>
                            <td><?= $row["admin"] ?></td>
                            <td><?= $row["admin_level"] ?></td>
                        </tr> <?php
                    }
                } ?>
            </tbody>
        </table> <?php
        if ($db->rowCount() == 0) { ?>
            <div class="w-100 text-center my-5">
                <h3>No Record</h3>
            </div> <?php
        } ?>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>