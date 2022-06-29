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

    if (!empty($_GET["role_id"])) {
        $db->query("SELECT roles.*, roles.name AS role_name, roles.id AS role_id, brands.*, brands.name AS brand_name, departments.*, departments.name AS dept_name
        FROM roles LEFT JOIN brands ON roles.brand_id = brands.id LEFT JOIN departments ON roles.department_id = departments.id
        WHERE roles.id=:role_id");
        $db->setRoleId($_GET["role_id"]);
        $db->execute();
        $value = $db->fetch();
    }

    if (isset($_POST["submit"])) {
        $_SESSION["name"] = $_POST["name"];
        $_SESSION["brand"] = $_POST["brand"];
        $_SESSION["department"] = $_POST["department"];
        $_SESSION["admin"] = $_POST["admin"];
        $_SESSION["level"] = $_POST["level"];

        if (!empty($_POST["name"]) && !empty($_POST["level"])) {            
            if ($_POST["level"] < $current_level) {
                if (empty($_GET["role_id"])) {
                    $roles = array($_POST["name"],
                    $_POST["brand"],
                    $_POST["department"],
                    $_POST["admin"],
                    $_POST["level"]);
    
                    $db->query("INSERT INTO roles VALUES (null, :role_name, :brand_id, :dept_id, :admin, :level)");
                    $db->insertRole($roles);
                    $db->execute();
                    $db->closeStmt();
    
                    $_SESSION["role_success"] = "Successfully added a role.";
                } else {
                    $roles = array($_POST["name"],
                    $_POST["brand"],
                    $_POST["department"],
                    $_POST["admin"],
                    $_POST["level"],
                    $_GET["role_id"]);
    
                    $db->query("UPDATE roles SET name=:role_name, brand_id=:brand_id, department_id=:dept_id,
                    admin=:admin, admin_level=:level WHERE id=:role_id");
                    $db->updateRole($roles);
                    $db->execute();
                    $db->closeStmt();
    
                    $_SESSION["role_success"] = "Successfully saved the changes.";
                }
                unset($_SESSION["name"]);
                unset($_SESSION["brand"]);
                unset($_SESSION["department"]);
                unset($_SESSION["admin"]);
                unset($_SESSION["level"]);

                redirect("roles.php");
                exit();
            } else {
                $_SESSION["role_failed"] = "The level must not be greater than your currrent level (".$current_level.").";
            }
        } else {
            $_SESSION["role_failed"] = "Please fill-out the required fields!";
        }
        redirect("role.php");
        exit();
    }

    if (isset($_POST["reset"])) {
        
        redirect("role.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    
    if (empty($_GET["role_id"])) {
        setTitle("WSAP IP Add Role");
    } else {
        setTitle("WSAP IP Edit Role");
    }
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
                <h3><?php
                 if (empty($_GET["role_id"])) {
                    echo "Add Role";
                 } else {
                    echo "Edit Role";
                 }
                ?></h3>
            </div>
        </div> <?php

        if ($admin_roles_count != 0) { ?>
        <div id="role" class="row rounded shadow pb-4 position-relative">
            <div class="col-12 p-4 mb-4"> <?php
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
                <form method="post">
                    <div class="row">
                        <div class="col-3 user_input my-1">
                            <label class="mb-2" for="name">Name
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control"
                                value="<?php
                                if (!empty($_SESSION["name"])) {
                                    echo $_SESSION["name"];
                                } else {
                                    if (!empty($_GET["role_id"])) {
                                        echo $value["role_name"];
                                    }
                                } ?>" maxLength="64">
                        </div>
                        <div class="col-3 user_input my-1">
                            <label class="text-indigo mb-2" for="brand">Brand</label>
                            <select name="brand" class="form-select">
                                <option value="0" selected>No Brand</option> <?php
                                $db->query("SELECT * FROM brands ORDER BY name");
                                $db->execute();
                                
                                while ($row = $db->fetch()) { ?>
                                    <option value="<?= $row["id"] ?>" <?php
                                        if (!empty($_SESSION["brand"])) {
                                            if ($row["id"] == $_SESSION["brand"]) { ?>
                                                selected <?php
                                            }
                                        } else {
                                            if (!empty($_GET["role_id"])) {
                                                if ($row["id"] == $value["brand_id"]) { ?>
                                                    selected <?php
                                                }
                                            }
                                        } ?>><?= $row["name"] ?> </option> <?php
                                } ?>
                            </select>
                        </div>
                        <div class="col-3 user_input my-1">
                            <label class="text-indigo mb-2" for="department">Department</label>
                            <select name="department" class="form-select">
                                <option value="0" selected>No Department</option> <?php
                                $db->query("SELECT * FROM departments ORDER BY name");
                                $db->execute();

                                while ($row = $db->fetch()) { ?>
                                    <option value="<?= $row["id"] ?>" <?php
                                        if (!empty($_SESSION["department"])) {
                                            if ($row["id"] == $_SESSION["department"]) { ?>
                                                selected <?php
                                            }
                                        } else {
                                            if (!empty($_GET["role_id"])) {
                                                if ($row["id"] == $value["department_id"]) { ?>
                                                    selected <?php
                                                }
                                            }
                                        } ?>><?= $row["name"] ?></option> <?php
                                } ?>
                            </select>
                        </div>
                        <div class="col-2 user_input my-1">
                            <label class="text-indigo mb-2" for="admin">Admin</label>
                            <select id="admin" name="admin" class="form-select">
                                <option value="0" <?php
                                    if (!empty($_SESSION["admin"])) {
                                        if ($_SESSION["admin"] == 0) { ?>
                                            selected <?php
                                        }
                                    } else {
                                        if (!empty($_GET["role_id"])) {
                                            if ($value["admin"] == 0) { ?>
                                                selected <?php
                                            }
                                        }
                                    } ?>>No
                                </option>
                                <option value="1" <?php
                                    if (!empty($_SESSION["admin"])) {
                                        if ($_SESSION["admin"] == 1) { ?>
                                            selected <?php
                                        }
                                    } else {
                                        if (!empty($_GET["role_id"])) {
                                            if ($value["admin"] == 1) { ?>
                                                selected <?php
                                            }
                                        }
                                    }?>>Yes
                                </option>
                            </select>
                        </div>
                        <div class="col-1 user_input my-1">
                            <label class="text-indigo mb-2" for="level">Level
                                <span class="text-danger">*</span>
                            </label>
                            <input id="level" type="number" name="level" class="form-control"
                                value="<?php
                                if (!empty($_SESSION["level"])) {
                                    echo $_SESSION["level"];
                                } else {
                                    if (!empty($_GET["role_id"])) {
                                        echo $value["admin_level"];
                                    } else {
                                        echo 1;
                                    }
                                } ?>" <?php
                                if (!empty($_SESSION["admin"])) {
                                    if ($_SESSION["admin"] == 0) { ?>
                                        readonly <?php
                                    }
                                } else {
                                    if (!empty($_GET["role_id"])) {
                                        if ($value["admin"] == 0) { ?>
                                            readonly <?php
                                        }
                                    }
                                } ?>>
                        </div>
                    </div> 
                    <div class="bottom-right">
                        <button class="btn btn-danger" name="reset">Reset</button>
                        <button class="btn btn-indigo" type="submit" name="submit"> <?php
                         if (empty($_GET["role_id"])) {
                            echo "Submit";
                         } else {
                            echo "Save Changes";
                         } ?></button>
                    </div>
                </form>
            </div>
        </div> <?php
        } else {
            include_once "access_denied.php";
        } ?>
    </div>
</div>

<script>
    const adminLevel = document.getElementById("admin");
    const levelElement = document.getElementById("level");

    adminLevel.addEventListener("change", function(event) {
        if (adminLevel.value == 1) {
            levelElement.removeAttribute("readonly");
        } else if (adminLevel.value == 0) {
            levelElement.value = 1;
            levelElement.setAttribute("readonly", "");
        }
    });
</script>