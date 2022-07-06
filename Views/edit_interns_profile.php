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

        $db->query("SELECT intern_wsap_information.*, intern_personal_information.*, intern_educational_information.*,intern_accounts.*
        FROM intern_wsap_information, intern_personal_information, intern_educational_information, intern_accounts
        WHERE intern_wsap_information.id=:intern_id AND
        intern_personal_information.id=:intern_id AND
        intern_educational_information.id=:intern_id AND
        intern_accounts.id=:intern_id");
        $db->setInternId($_GET["intern_id"]);
        $db->execute();
        $value = $db->fetch();
        $intern_count = $db->rowCount();

        if ($intern_count == 0) {
            redirect("edit_interns_profile.php");
            exit();
        }
    }

    if (isset($_POST["uploadImage"]) && isset($_FILES["image"])) {
        $image_name = $_FILES["image"]["name"];
        $image_size = $_FILES["image"]["size"];
        $tmp_name = $_FILES["image"]["tmp_name"];
        $error = $_FILES["image"]["error"];

        if (!empty($_FILES["image"]["name"])) {
            if ($error == 0) {
                $img_ex = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $allowed_exs = array("jpg", "jpeg", "png");
    
                if (in_array($img_ex, $allowed_exs)) {
                    $image_path = "../Assets/img/profile_imgs/".$image_name;
                    move_uploaded_file($tmp_name, $image_path);
      
                    $profile_image = array(
                        $image_path,
                        strtoupper($_GET["intern_id"]),
                    );
    
                    $db->query("UPDATE intern_wsap_information
                    SET image=:image WHERE id=:intern_id");
                    $db->setProfileImage($profile_image);
                    $db->execute();
                    $db->closeStmt();

                    $image = array(
                        strtoupper($_GET["intern_id"]),
                        $image_name
                    );

                    $db->query("SELECT * FROM images
                    WHERE intern_id=:intern_id AND image_name=:image_name");
                    $db->selectImage($image);
                    $db->execute();
                    $image_count = $db->rowCount();

                    if ($image_count == 0) {
                        $upload_image = array(
                            strtoupper($_GET["intern_id"]),
                            $image_path,
                            $image_name
                        );
        
                        $db->query("INSERT INTO images VALUES
                        (null, :intern_id, :image_path, :image_name)");
                        $db->uploadImage($upload_image);
                        $db->execute();
                        $db->closeStmt();
                    }
                    
                    $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                        " (".$admin_info["name"].") uploaded an image for ".$value["last_name"].", ".$value["first_name"].".";

                    $log = array($date->getDateTime(),
                    strtoupper($_SESSION["intern_id"]),
                    $log_value);
        
                    $db->query("INSERT INTO audit_logs
                    VALUES (null, :timestamp, :intern_id, :log)");
                    $db->log($log);
                    $db->execute();
                    $db->closeStmt();
                    
                    $_SESSION["upload_success"] = "The file has been uploaded successfully.";
                } else {
                    $_SESSION["upload_failed"] = "The file must be an image!";
                }
            } else {
                $_SESSION["upload_failed"] = "There is an error occurred!";
            }
        } else {
            $_SESSION["upload_failed"] = "You must select an image file first!";
        }
        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]);
        exit();
    }

    if (isset($_POST["savePersonal"])) {
        $last_name = toProper(fullTrim($_POST["lastName"]));
        $first_name = toProper(fullTrim($_POST["firstName"]));
        $middle_name = toProper(fullTrim($_POST["middleName"]));
        $gender = $_POST["gender"];
        $birthday = $_POST["birthday"];

        $_SESSION["last_name"] = $last_name;
        $_SESSION["first_name"] = $first_name;
        $_SESSION["middle_name"] = $middle_name;
        $_SESSION["gender"] = $gender;
        $_SESSION["birthday"] = $birthday;

        if (!empty($last_name) && !empty($first_name) && !empty($birthday)) {
            $personal_info = array($last_name,
            $first_name,
            $middle_name,
            $gender,
            $birthday,
            $_GET["intern_id"]);
    
            $db->query("UPDATE intern_personal_information
            SET last_name=:last_name, first_name=:first_name, middle_name=:middle_name,
            gender=:gender, birthday=:birthday WHERE id=:intern_id");
            $db->setPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") updated the personal information of ".$value["last_name"].", ".$value["first_name"].".";

            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);

            $db->query("INSERT INTO audit_logs
            VALUES (null, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["personal_success"] = "Successfully saved the changes.";
            unset($_SESSION["last_name"]);
            unset($_SESSION["first_name"]);
            unset($_SESSION["middle_name"]);
            unset($_SESSION["birthday"]);
            unset($_SESSION["gender"]);
        } else {
            $_SESSION["personal_failed"] = "Please fill-out the required fields!";
        }
        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#personal-info");
        exit();
    }

    if (isset($_POST["resetPersonal"])) {
        unset($_SESSION["last_name"]);
        unset($_SESSION["first_name"]);
        unset($_SESSION["middle_name"]);
        unset($_SESSION["gender"]);
        unset($_SESSION["birthday"]);

        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#personal-info");
        exit();
    }

    if (isset($_POST["saveWSAP"])) {
        $dept_id = $_POST["department"];
        $status = $_POST["status"];
        $onboard_date = $_POST["onboardDate"];
        $rendered_hours = $_POST["renderedHours"];
        $target_rendering_hours = $_POST["targetRenderingHours"];

        $_SESSION["dept_id"] = $dept_id;
        $_SESSION["status"] = $status;
        $_SESSION["onboard_date"] = $onboard_date;
        $_SESSION["rendered_hours"] = $rendered_hours;
        $_SESSION["target_rendering_hours"] = $target_rendering_hours;
        
        if (!empty($onboard_date)) {
            if ($target_rendering_hours >= 200) {
                $wsap_info = array($dept_id,
                $status,
                $onboard_date,
                $rendered_hours,
                $target_rendering_hours,
                $_GET["intern_id"]);
        
                $db->query("UPDATE intern_wsap_information
                SET department_id=:dept_id, status=:status, onboard_date=:onboard_date,
                target_rendering_hours=:target_rendering_hours, rendered_hours=:rendered_hours
                WHERE id=:intern_id");
                $db->setWSAPInfo2($wsap_info);
                $db->execute();
                $db->closeStmt();
                        
                $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                    " (".$admin_info["name"].") updated the WSAP information of ".$value["last_name"].", ".$value["first_name"].".";
    
                $log = array($date->getDateTime(),
                strtoupper($_SESSION["intern_id"]),
                $log_value);
    
                $db->query("INSERT INTO audit_logs
                VALUES (null, :timestamp, :intern_id, :log)");
                $db->log($log);
                $db->execute();
                $db->closeStmt();
                
                $_SESSION["wsap_success"] = "Successfully saved the changes.";
                unset($_SESSION["dept_id"]);
                unset($_SESSION["status"]);
                unset($_SESSION["onboard_date"]);
                unset($_SESSION["rendered_hours"]);
                unset($_SESSION["target_rendering_hours"]);
            } else {
                $_SESSION["wsap_failed"] = "The target rendering hours must be at least 200!";
            }
        } else {
            $_SESSION["wsap_failed"] = "Please fill-out the required fields!";
        }
        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#wsap-info");
        exit();
    }

    if (isset($_POST["resetWSAP"])) {
        unset($_SESSION["dept_id"]);
        unset($_SESSION["status"]);
        unset($_SESSION["onboard_date"]);
        unset($_SESSION["rendered_hours"]);
        unset($_SESSION["target_rendering_hours"]);
        
        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#wsap-info");
        exit();
    }
    
    if (isset($_POST["resetPassword"])) {
        $reset_password = array("", $_GET["intern_id"]);

        $db->query("UPDATE intern_accounts SET password=:password WHERE id=:intern_id");
        $db->updatePassword($reset_password);
        $db->execute();
        $db->closeStmt();
                    
        $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
            " (".$admin_info["name"].") reset the password of ".$value["last_name"].", ".$value["first_name"].".";

        $log = array($date->getDateTime(),
        strtoupper($_SESSION["intern_id"]),
        $log_value);

        $db->query("INSERT INTO audit_logs
        VALUES (null, :timestamp, :intern_id, :log)");
        $db->log($log);
        $db->execute();
        $db->closeStmt();
        
        $_SESSION["reset_success"] = "Successfully reset the password of an intern.";

        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#account-info");
        exit();
    }
    
    if (isset($_POST["removeRole"])) {
        if (!empty($_POST["intern_role_id"]) && !empty($_POST["role_name"])) {
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

        redirect("edit_interns_profile.php?intern_id=".$_GET["intern_id"]."#roles");
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
            redirect("edit_interns_profile.php".$parameters);
        } else {
            redirect("edit_interns_profile.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("edit_interns_profile.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Edit Intern's Profile");
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
                <h3>Edit Intern's Profile</h3>
            </div>
        </div> <?php
        if ($admin_roles_count != 0) {
            if (!empty($_GET["intern_id"])) { ?>
                <div id="personal-info" class="row rounded shadow pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                            Personal Information
                        </h6>
                    </div>

                    <div class="col-lg-4 col-md-5 p-4 pb-0 text-center">
                        <form method="post" enctype="multipart/form-data">
                            <label for="image" class="form-label fw-bold w-100">Photo</label>
                            <img class="mb-2" id="output" src="<?php {
                                    if ($value["image"] == null || strlen($value["image"]) == 0) {
                                        if ($value["gender"] == 0) {
                                            echo "../Assets/img/profile_imgs/default_male.png";
                                        } else {
                                            echo "../Assets/img/profile_imgs/default_female.png";
                                        }
                                    } else {
                                        echo $value["image"];
                                    }
                                } ?>" onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';"> <?php

                            if (isset($_SESSION["upload_success"])) { ?>
                                <div class="alert alert-success text-success">
                                    <?php
                                        echo $_SESSION["upload_success"];
                                        unset($_SESSION["upload_success"]);
                                    ?>
                                </div> <?php
                            }

                            if (isset($_SESSION["upload_failed"])) { ?>
                                <div class="alert alert-danger text-danger">
                                    <?php
                                        echo $_SESSION["upload_failed"];
                                        unset($_SESSION["upload_failed"]);
                                    ?>
                                </div> <?php
                            } ?>
                            
                            <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                                onchange="loadFile(event)" name="image" style="max-width: 350px;">
            
                                <button class="btn btn-sm btn-smoke border-dark mt-2 w-100" style="max-width: 150px;"
                                type="submit" name="uploadImage">Upload</button>
                        </form>

                        <div class="modal fade" id="photosModal" tabindex="-1" aria-labelledby="photosModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="photosModalLabelLabel">Photos</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                        <div class="modal-body">
                                            <div class="images-grid p-2"> <?php
                                                $images_db = new Database();

                                                $images_db->query("SELECT intern_personal_information.*, images.*
                                                FROM intern_personal_information, images
                                                WHERE intern_personal_information.id = images.intern_id AND
                                                intern_personal_information.id=:intern_id");
                                                $images_db->setInternId($_GET["intern_id"]);
                                                $images_db->execute();

                                                while ($images = $images_db->fetch()) { ?>
                                                    <div class="p-2">
                                                        <img src="<?= $images["image_path"] ?>" class="image"
                                                            onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                                                        <p class="my-2" style="height: 20px; overflow: hidden;"><?= $images["image_name"] ?></p> <?php
                                                        if ($value["image"] == $images["image_path"]) { ?>
                                                            <a class="btn btn-sm btn-secondary w-75 disabled">Current Profile Photo</a> <?php
                                                        } else { ?>
                                                            <a href="set_profile_photo.php?intern_id=<?= strtoupper($_GET["intern_id"]) ?>&image_id=<?= $images["id"] ?>"
                                                            class="btn btn-sm btn-indigo w-75">Set as Profile Photo</a> <?php
                                                        } ?>
                                                    </div> <?php
                                                } ?>                                        
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-sm btn-primary mt-2 w-100" style="max-width: 150px;"
                            data-bs-toggle="modal" data-bs-target="#photosModal">Photos</button>
                    </div>

                    <div class="col-lg-8 col-md-7 p-4"> <?php
                        if (isset($_SESSION["personal_success"])) { ?>
                            <div class="alert alert-success text-success">
                                <?php
                                    echo $_SESSION["personal_success"];
                                    unset($_SESSION["personal_success"]);
                                ?>
                            </div> <?php
                        }

                        if (isset($_SESSION["personal_failed"])) { ?>
                            <div class="alert alert-danger text-danger">
                                <?php
                                    echo $_SESSION["personal_failed"];
                                    unset($_SESSION["personal_failed"]);
                                ?>
                            </div> <?php
                        } ?>
                        <form method="post">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-6">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-12 user_input my-1">
                                            <label class="mb-2" for="lastName">Last Name
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="lastName" class="form-control"
                                            value="<?php
                                                if (isset($_SESSION["last_name"])) {
                                                    echo $_SESSION["last_name"];
                                                } else {
                                                    echo $value["last_name"];
                                                } ?>" maxLength="32">
                                        </div>
                                        <div class="col-lg-4 col-md-12 user_input my-1">
                                            <label class="mb-2" for="firstName">First Name
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="firstName" class="form-control"
                                            value="<?php
                                                if (isset($_SESSION["first_name"])) {
                                                    echo $_SESSION["first_name"];
                                                } else {
                                                    echo $value["first_name"];
                                                } ?>" maxLength="32">
                                        </div>
                                        <div class="col-lg-4 col-md-12 user_input my-1">
                                            <label class="mb-2" for="middleName">Middle Name</label>
                                            <input type="text" name="middleName" class="form-control"
                                            value="<?php
                                                if (isset($_SESSION["middle_name"])) {
                                                    echo $_SESSION["middle_name"];
                                                } else {
                                                    echo $value["middle_name"];
                                                } ?>" maxLength="32">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 col-md-12 col-sm-6">
                                    <div class="row mb-4">
                                        <div class="col-lg-4 col-md-12 user_input my-1">
                                            <label class="mb-2" for="birthday">Birthday
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="birthday" class="form-control"
                                            value="<?php
                                                if (isset($_SESSION["birthday"])) {
                                                    echo $_SESSION["birthday"];
                                                } else {
                                                    echo date("Y-m-d", strtotime($value["birthday"]));
                                                } ?>">
                                        </div>
                                        <div class="col-lg-4 col-md-12 user_input my-1">
                                            <label class="mb-2" for="gender">Gender</label>
                                            <select name="gender" class="form-select">
                                                <option value="0" <?php
                                                    if (isset($_SESSION["gender"])) {
                                                        if ($_SESSION["gender"] == 0) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["gender"] == 0) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>Male</option>
                                                <option value="1" <?php
                                                    if (isset($_SESSION["gender"])) {
                                                        if ($_SESSION["gender"] == 1) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["gender"] == 1) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>Female</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bottom-right">
                                <button class="btn btn-indigo" type="submit" name="savePersonal">Save Changes</button>
                                <button class="btn btn-danger" name="resetPersonal">Reset</button>
                            </div>
                        </form> <?php
                        unset($_SESSION["last_name"]);
                        unset($_SESSION["first_name"]);
                        unset($_SESSION["middle_name"]);
                        unset($_SESSION["gender"]);
                        unset($_SESSION["birthday"]); ?>
                    </div>
                </div>

                <div id="wsap-info" class="row rounded shadow mt-4 pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                            WSAP Information
                        </h6>
                    </div>

                    <div class="col-12 p-4"> <?php
                        if (isset($_SESSION["wsap_success"])) { ?>
                            <div class="alert alert-success text-success">
                                <?php
                                    echo $_SESSION["wsap_success"];
                                    unset($_SESSION["wsap_success"]);
                                ?>
                            </div> <?php
                        }

                        if (isset($_SESSION["wsap_failed"])) { ?>
                            <div class="alert alert-danger text-danger">
                                <?php
                                    echo $_SESSION["wsap_failed"];
                                    unset($_SESSION["wsap_failed"]);
                                ?>
                            </div> <?php
                        } ?>
                        <form method="post">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="row">
                                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                    <label class="mb-2" for="intern_id">Intern ID</label>
                                                    <input type="text" name="intern_id" class="form-control text-uppercase fw-bold"
                                                        value="<?= $value["id"]; ?>" disabled>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                    <label class="mb-2" for="department">Department</label>
                                                    <select name="department" class="form-select"> <?php
                                                        $db->query("SELECT * FROM departments ORDER BY name");
                                                        $db->execute();

                                                        while ($row = $db->fetch()) { ?>
                                                        <option <?php
                                                            if (isset($_SESSION["dept_id"])) {
                                                                if ($_SESSION["dept_id"] == $row["id"]) { ?> selected <?php }
                                                            } else {
                                                                if ($value["department_id"] == $row["id"]) { ?>
                                                                    selected <?php
                                                                }
                                                            } ?> value="<?= $row["id"] ?>"><?= $row["name"] ?> </option> <?php
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                    <label class="mb-2" for="status">Status</label>
                                                    <select name="status" class="form-select">
                                                    <option value="0" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 0) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 0) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Inactive</option>
                                                        <option value="1" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 1) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 1) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Active</option>
                                                        <option value="2" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 2) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 2) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Offboarded</option>
                                                        <option value="3" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 3) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 3) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Withdrawn</option>
                                                        <option value="4" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 4) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 4) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Extended</option>
                                                        <option value="5" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 5) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 5) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Suspended</option>
                                                        <option value="6" <?php
                                                        if (isset($_SESSION["status"])) {
                                                            if ($_SESSION["status"] == 6) { ?>
                                                                selected <?php
                                                            }
                                                        } else {
                                                            if ($value["status"] == 6) { ?>
                                                                selected <?php
                                                            }
                                                        } ?>>Terminated</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-12">
                                            <div class="row">
                                                <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                    <label class="mb-2" for="onboardDate">Onboard Date
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="date" name="onboardDate" class="form-control"
                                                    value="<?php
                                                        if (isset($_SESSION["onboard_date"])) {
                                                            echo $_SESSION["onboard_date"];
                                                        } else {
                                                            echo date("Y-m-d", strtotime($value["onboard_date"]));
                                                        } ?>">
                                                </div>
                                                <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1"> <?php
                                                    if (empty($value["offboard_date"])) { ?>
                                                        <label class="mb-2" for="offboardDate">Estimated Offboard Date</label>
                                                        <input type="text" name="offboardDate" class="form-control fw-bold"
                                                        value="<?php
                                                        $rendering_days = round(($value["target_rendering_hours"]-$value["rendered_hours"])/8);
                                                        $estimated_weekends = ceil(($rendering_days/5) * 2);
                                                        $rendering_days += $estimated_weekends + 1;

                                                        echo date("F j, Y", strtotime($date->getDate()." + ".$rendering_days." days")); ?>"
                                                        disabled> <?php
                                                    } else { ?>
                                                        <label class="mb-2" for="offboardDate">Offboard Date</label>
                                                        <input type="text" name="offboardDate" class="form-control fw-bold"
                                                        value="<?= date("F j, Y", strtotime($value["offboard_date"])); ?>"
                                                        disabled> <?php
                                                    } ?>
                                                </div>
                                                <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1"> <?php
                                                    if (empty($value["offboard_date"]) && $value["status"] == 1) { ?>
                                                        <label class="mb-2" for="renderedHours">Rendered Hours
                                                            <span class="text-danger">*</span></label>
                                                        <input type="number" name="renderedHours" class="form-control"
                                                            value="<?php
                                                            if (isset($_SESSION["rendered_hours"])) {
                                                                echo $_SESSION["rendered_hours"];
                                                            } else {
                                                                echo $value["rendered_hours"];;
                                                            } ?>"> <?php
                                                    } else { ?>
                                                        <label class="mb-2" for="renderedHours">Rendered Hours</label>
                                                        <input type="number" name="renderedHours" class="form-control fw-bold"
                                                            value="<?= $value["rendered_hours"]; ?>" readonly> <?php
                                                    } ?>
                                                </div>
                                                <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1"> <?php
                                                    if (empty($value["offboard_date"]) && $value["status"] == 1) { ?>
                                                        <label class="mb-2" for="targetRenderingHours">Target Rendering Hours
                                                            <span class="text-danger">*</span></label>
                                                        <input type="number" name="targetRenderingHours" class="form-control"
                                                            value="<?php
                                                            if (isset($_SESSION["target_rendering_hours"])) {
                                                                echo $_SESSION["target_rendering_hours"];
                                                            } else {
                                                                echo $value["target_rendering_hours"];
                                                            } ?>"> <?php
                                                    } else { ?>
                                                        <label class="mb-2" for="targetRenderingHours">Target Rendering Hours</label>
                                                        <input type="number" name="targetRenderingHours" class="form-control fw-bold"
                                                            value="<?= $value["target_rendering_hours"]; ?>" readonly> <?php
                                                    } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="row mt-2 mb-4">
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="emailAddress">Email Address</label>
                                            <input name="emailAddress" class="form-control fw-bold"
                                                value="<?php if (isset($_SESSION["email_address"])) {
                                                        echo $_SESSION["email_address"];
                                                    } else {
                                                        echo $value["email_address"];
                                                    } ?>" maxLength="64" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="mobileNumber">Mobile Number</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">+63</span>
                                                </div>
                                                <input type="phone" name="mobileNumber" class="form-control fw-bold"
                                                value="<?php if (isset($_SESSION["mobile_number"])) {
                                                        echo $_SESSION["mobile_number"];
                                                    } else {
                                                        echo $value["mobile_number"];
                                                    } ?>" maxLength="10" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="mobileNumber2">Mobile Number 2</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">+63</span>
                                                </div>
                                                <input type="phone" name="mobileNumber2" class="form-control fw-bold"
                                                value="<?php if (isset($_SESSION["mobile_number_2"])) {
                                                        echo $_SESSION["mobile_number_2"];
                                                    } else {
                                                        echo $value["mobile_number_2"];
                                                    } ?>" maxLength="10" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bottom-right">
                                <button class="btn btn-indigo" type="submit" name="saveWSAP">Save Changes</button>
                                <button class="btn btn-danger" name="resetWSAP">Reset</button>
                            </div>
                        </form> <?php
                        unset($_SESSION["dept_id"]);
                        unset($_SESSION["status"]);
                        unset($_SESSION["onboard_date"]);
                        unset($_SESSION["rendered_hours"]);
                        unset($_SESSION["target_rendering_hours"]); ?>
                    </div>
                </div>

                <div id="educational-info" class="row rounded shadow mt-4 pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                            Educational Information
                        </h6>
                    </div>

                    <div class="col-12 p-4">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12 user_input my-1">
                                                <label class="mb-2" for="university">University</label>
                                                <input type="text" name="university" class="form-control fw-bold"
                                                    value="<?php if (isset($_SESSION["university"])) {
                                                    echo $_SESSION["university"];
                                                    } else {
                                                        echo $value["university"];
                                                    } ?>" maxLength="64" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                            <div class="col-lg-6 col-md-12 user_input my-1">
                                                <label class="mb-2" for="course">Course</label>
                                                <input type="text" name="course" class="form-control fw-bold"
                                                    value="<?php if (isset($_SESSION["course"])) {
                                                    echo $_SESSION["course"];
                                                    } else {
                                                        echo $value["course"];
                                                    } ?>" maxLength="64" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="university_abbreviation">University Abbreviation</label>
                                                <input type="text" name="university_abbreviation" class="form-control fw-bold"
                                                    value="<?php if (isset($_SESSION["university_abbreviation"])) {
                                                    echo $_SESSION["university_abbreviation"];
                                                    } else {
                                                        echo $value["university_abbreviation"];
                                                    } ?>" maxLength="16" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="course_abbreviation">Course Abbreviation</label>
                                                <input type="text" name="course_abbreviation" class="form-control fw-bold"
                                                    value="<?php if (isset($_SESSION["course_abbreviation"])) {
                                                    echo $_SESSION["course_abbreviation"];
                                                    } else {
                                                        echo $value["course_abbreviation"];
                                                    } ?>" maxLength="12" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="year">Year</label>
                                                <select name="year" class="form-select fw-bold" <?php
                                                    if (!empty($_GET["intern_id"])) { ?>
                                                        disabled <?php
                                                    } ?>>
                                                    <option value="1" <?php
                                                    if (isset($_SESSION["year"])) {
                                                        if ($_SESSION["year"] == 1) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["year"] == 1) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>1</option>
                                                    <option value="2" <?php
                                                    if (isset($_SESSION["year"])) {
                                                        if ($_SESSION["year"] == 2) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["year"] == 2) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>2</option>
                                                    <option value="3" <?php
                                                    if (isset($_SESSION["year"])) {
                                                        if ($_SESSION["year"] == 3) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["year"] == 3) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>3</option>
                                                    <option value="4" <?php
                                                    if (isset($_SESSION["year"])) {
                                                        if ($_SESSION["year"] == 4) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["year"] == 4) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>4</option>
                                                    <option value="5" <?php
                                                    if (isset($_SESSION["year"])) {
                                                        if ($_SESSION["year"] == 5) { ?>
                                                            selected <?php
                                                        }
                                                    } else {
                                                        if ($value["year"] == 5) { ?>
                                                            selected <?php
                                                        }
                                                    } ?>>5</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="account-info" class="row rounded shadow mt-4 pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                            Account Information
                        </h6>
                    </div>
                    
                    <div class="col-12 p-4"> <?php
                        if (isset($_SESSION["reset_success"])) { ?>
                            <div class="alert alert-success text-success">
                                <?php
                                    echo $_SESSION["reset_success"];
                                    unset($_SESSION["reset_success"]);
                                ?>
                            </div> <?php
                        }
                        
                        if (isset($_SESSION["reset_failed"])) { ?>
                            <div class="alert alert-danger text-danger">
                                <?php
                                    echo $_SESSION["reset_failed"];
                                    unset($_SESSION["reset_failed"]);
                                ?>
                            </div> <?php
                        } ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row <?php
                                    if ($_GET["intern_id"] != strtoupper($_SESSION["intern_id"])) {  ?>
                                        mb-4 <?php
                                    } ?>">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="date_activated">Date Activated</label>
                                                <input type="text" name="date_activated" class="form-control fw-bold"
                                                    value="<?= date("F j, Y", strtotime($value["date_activated"])) ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <?php
                        if ($_GET["intern_id"] != strtoupper($_SESSION["intern_id"])) { ?>
                            <div class="modal fade" id="resetPasswordModal" tabindex="-1"
                                aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="modal-title" id="resetPasswordModalLabel">
                                                <h5>Reset Password</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <form method="post">
                                            <div class="modal-body">
                                                <div class="text-center px-5">
                                                    <h6 class="text-dark mb-0">
                                                        Do you want to reset the password of<br><?= $value["last_name"].", ".$value["first_name"]."?"; ?>
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="resetPassword" class="btn btn-danger">Reset Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                                
                            <div class="bottom-right">
                                <button class="btn btn-danger" data-bs-toggle="modal" 
                                    data-bs-target="#resetPasswordModal">Reset Password</button>
                            </div> <?php
                        } ?>
                    </div>
                </div>
                
                <div id="roles" class="row rounded shadow mt-4 pb-4 position-relative">
                    <div class="rounded shadow px-0">
                        <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                            Roles
                        </h6>
                    </div>

                    <div class="my-3 ms-auto w-fit">
                        <a class="btn btn-primary" href="assign_roles.php?intern_id=<?= $_GET["intern_id"] ?>">
                            <i class="fa-solid fa-plus me-2"></i>Assign Roles
                        </a>
                    </div> <?php
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
                            $db->setInternId($value["id"]);
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
                                                                    <input type="text" name="role_name" class="form-control text-center d-none mt-2"
                                                                        value="<?= $row["role_name"] ?>" readonly>
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
                </div> <?php
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
                                        <button class="btn btn-indigo" type="submit" name="search">Search</button>
                                        <button class="btn btn-danger" name="reset">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div class="col-12">
                            <div class="d-md-flex d-sm-inline-block">
                                <div class="my-2">
                                    <a class="btn btn-secondary me-2" href="interns.php">
                                        <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns
                                    </a>
                                </div>
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
                                                href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                                                    href="<?= "edit_interns_profile.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "edit_interns_profile.php" ?>" <?php
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
                            <a class="clickable-card" href="edit_interns_profile.php?intern_id=<?= $row["intern_id"] ?>"
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

<script>
    var loadFile = function (event) {
        var output = document.getElementById("output");
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function () {
            URL.revokeObjectURL(output.src)
        }
    };
</script>
<?php
    require_once "../Templates/footer.php";
?>