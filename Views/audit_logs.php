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
        if (!empty($_POST["search_log"])) {
            $parameters = $parameters."search=".$_POST["search_log"];
        }

        if (!empty($_GET["department"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."department=".$_GET["department"];
        }
                                                
        if (!empty($_GET["day"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."day=".$_GET["day"];
        }
                                                
        if (!empty($_GET["month"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."month=".$_GET["month"];
        }
        
        if (!empty($_GET["year"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."year=".$_GET["year"];
        }

        if (strlen($parameters) > 1) {
            redirect("audit_logs.php".$parameters);
        } else {
            redirect("audit_logs.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("audit_logs.php?day=".$date->getDay()."&month=".$date->getMonthName()."&year=".$date->getYear());
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Audit Logs");
?>

<div class="my-container">
    <?php
        include_once "nav_side_bar.php";
        navSideBar("auditLogs");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>

        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Audit Logs</h3>
            </div>
        </div>  <?php
        if ($admin_roles_count != 0) { ?>
            <div>
                <div class="row">
                    <!--SEARCH BUTTON/TEXT-->
                    <form method="post">
                        <div class="col-lg-8 col-md-10 col-sm-12">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Search Log" name="search_log" value="<?php
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
                        <div class="d-md-flex">
                            <div class="d-flex">
                                <div class="dropdown align-center mb-2 me-2">
                                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                        data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                                        if (!empty($_GET["day"]) && !empty($_GET["month"]) && !empty($_GET["year"])) {
                                            echo "Custom";
                                        } else {
                                            echo "All Records";
                                        } ?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li>
                                            <a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?";
                                            if (!empty($_GET["search"])) {
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }
                                                
                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "audit_logs.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "audit_logs.php" ?>" <?php
                                            } ?>>
                                                All Records
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?";
                                            if (!empty($_GET["search"])) {
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }
                                                
                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }

                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."day=".$date->getDay()."&month=".$date->getMonthName()."&year=".$date->getYear();

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "audit_logs.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "audit_logs.php" ?>" <?php
                                            } ?>>
                                                Custom
                                            </a>
                                        </li>
                                    </ul>
                                </div> <?php
                                if (!empty($_GET["day"]) && !empty($_GET["month"]) && !empty($_GET["year"])) { ?>
                                    <div class="dropdown align-center me-2">
                                        <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                            data-bs-toggle="dropdown" aria-expanded="false" name="department">
                                            <?= $_GET["day"] ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                                            $selected_month = date("m", strtotime($_GET["month"]));
                                            $selected_year = date("Y", strtotime($_GET["year"]));
                                            $number_of_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

                                            for ($i = 1; $i <= $number_of_days; $i++) { ?>
                                                <li>
                                                    <a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }
                                                        
                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."day=".$i."&month=".$_GET["month"]."&year=".$_GET["year"];

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "audit_logs.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "audit_logs.php" ?>" <?php
                                                    } ?>>
                                                        <?= $i ?>
                                                    </a>
                                                </li> <?php
                                            } ?>
                                        </ul>
                                    </div>
                                    <div class="dropdown align-center me-2">
                                        <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                            data-bs-toggle="dropdown" aria-expanded="false" name="department">
                                            <?= $_GET["month"] ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                                            foreach (getMonths() as $value) { ?>
                                                <li>
                                                    <a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }
                                                        
                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."day=".$_GET["day"]."&month=".$value."&year=".$_GET["year"];

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "audit_logs.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "audit_logs.php" ?>" <?php
                                                    } ?>>
                                                        <?= $value ?>
                                                    </a>
                                                </li> <?php
                                            } ?>
                                        </ul>
                                    </div>
                                    <div class="dropdown align-center me-2">
                                        <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                            data-bs-toggle="dropdown" aria-expanded="false" name="department">
                                            <?= $_GET["year"] ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                                            for ($i = 2018; $i <= $date->getYear(); $i++) { ?>
                                                <li>
                                                    <a class="dropdown-item btn-smoke" <?php
                                                    $parameters = "?";
                                                    if (!empty($_GET["search"])) {
                                                        $parameters = $parameters."search=".$_GET["search"];
                                                    }
                                                        
                                                    if (!empty($_GET["department"])) {
                                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                        $parameters = $parameters."department=".$_GET["department"];
                                                    }
                                                    
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."day=".$_GET["day"]."&month=".$_GET["month"]."&year=".$i;

                                                    if (strlen($parameters) > 1) { ?>
                                                        href="<?= "audit_logs.php".$parameters ?>" <?php
                                                    } else { ?>
                                                        href="<?= "audit_logs.php" ?>" <?php
                                                    } ?>>
                                                        <?= $i ?>
                                                    </a>
                                                </li> <?php
                                            } ?>
                                        </ul>
                                    </div> <?php
                                } ?>
                            </div>
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
                                        
                                    if (!empty($_GET["day"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."day=".$_GET["day"];
                                    }
                                        
                                    if (!empty($_GET["month"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."month=".$_GET["month"];
                                    }
                                    
                                    if (!empty($_GET["year"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."year=".$_GET["year"];
                                    }

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "audit_logs.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "audit_logs.php" ?>" <?php
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
                                        
                                        if (!empty($_GET["day"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."day=".$_GET["day"];
                                        }
                                        
                                        if (!empty($_GET["month"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."month=".$_GET["month"];
                                        }
                                        
                                        if (!empty($_GET["year"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."year=".$_GET["year"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "audit_logs.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "audit_logs.php" ?>" <?php
                                        } ?>> <?= $row["name"] ?>
                                        </a></li> <?php
                                    } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <th scope="col">Timestamp</th>
                        <th scope="col" class="text-start">Log</th>
                    </tr>
                </thead>
                <tbody> <?php
                    $sort = " ORDER BY audit_logs.id DESC";

                    $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                    intern_wsap_information.department_id = departments.id AND
                    intern_personal_information.id = audit_logs.intern_id";

                    if (!empty($_GET["search"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." log LIKE CONCAT('%', :log, '%')";
                    }
                    if (!empty($_GET["department"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." departments.name=:dept_name";
                    }
                    if (!empty($_GET["day"]) && !empty($_GET["month"]) && !empty($_GET["year"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." timestamp LIKE CONCAT(:month, '%', :day, '%', :year, '%')";
                    }

                    $query = "SELECT intern_personal_information.*, intern_wsap_information.*, departments.*, audit_logs.*
                    FROM intern_personal_information, intern_wsap_information, departments, audit_logs";

                    if (strlen($conditions) > 6) {
                        $db->query($query.$conditions.$sort);

                        if (!empty($_GET["search"])) {
                            $db->selectLog($_GET["search"]);
                        }
                        if (!empty($_GET["department"])) {
                            $db->selectDepartment($_GET["department"]);
                        }
                        if (!empty($_GET["day"]) && !empty($_GET["month"]) && !empty($_GET["year"])) {
                            $date_value = array($_GET["day"], $_GET["month"], $_GET["year"]);
                            $db->setDate($date_value);
                        }
                    }
                    $db->execute();

                    $count = 0;
                    while ($row = $db->fetch()) {
                        $count++;  ?>
                        <tr>
                            <th scope="row"><?= $count ?></th>
                            <td><?= $row["timestamp"] ?></td>
                            <td class="text-start"><?= $row["log"] ?></td>
                        <tr> <?php
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