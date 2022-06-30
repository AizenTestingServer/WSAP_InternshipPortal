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

    $db->query("SELECT intern_wsap_information.*, intern_personal_information.*, intern_educational_information.*, intern_accounts.*
    FROM intern_wsap_information, intern_personal_information, intern_educational_information, intern_accounts
    WHERE intern_wsap_information.id=:intern_id AND
    intern_personal_information.id=:intern_id AND
    intern_educational_information.id=:intern_id AND
    intern_accounts.id=:intern_id");
    if (!empty($_GET["intern_id"])) {
        $db->setInternId($_GET["intern_id"]);
    } else {
        $db->setInternId($_SESSION["intern_id"]);
    }
    $db->execute();
    
    $value = $db->fetch();

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
      
                    // $upload_image = array(
                    //     strtoupper($_SESSION["intern_id"]),
                    //     $image_path,
                    // );
    
                    // $db->query("INSERT INTO images VALUES (null, :intern_id, :image_path)");
                    // $db->uploadImage($upload_image);
                    // $db->execute();
                    // $db->closeStmt();
      
                    $profile_image = array(
                        $image_path,
                        strtoupper($_SESSION["intern_id"]),
                    );
    
                    $db->query("UPDATE intern_wsap_information
                    SET image=:image WHERE id=:intern_id");
                    $db->setProfileImage($profile_image);
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
        redirect("profile.php");
        exit();
    }

    if (isset($_POST["savePersonal"])) {
        $last_name = ucwords(trim($_POST["lastName"]));
        $first_name = ucwords(trim($_POST["firstName"]));
        $middle_name = ucwords(trim($_POST["middleName"]));
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
            $_SESSION["intern_id"]);
    
            $db->query("UPDATE intern_personal_information
            SET last_name=:last_name, first_name=:first_name, middle_name=:middle_name,
            gender=:gender, birthday=:birthday WHERE id=:intern_id");
            $db->setPersonalInfo($personal_info);
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
        redirect("profile.php#personal-info");
        exit();
    }

    if (isset($_POST["resetPersonal"])) {
        unset($_SESSION["last_name"]);
        unset($_SESSION["first_name"]);
        unset($_SESSION["middle_name"]);
        unset($_SESSION["gender"]);
        unset($_SESSION["birthday"]);

        redirect("profile.php#personal-info");
        exit();
    }

    if (isset($_POST["saveWSAP"])) {
        $dept_id = $_POST["department"];
        $status = $_POST["status"];
        $onboard_date = $_POST["onboardDate"];
        $email_address = trim($_POST["emailAddress"]);
        $mobile_number = $_POST["mobileNumber"];
        $mobile_number_2 = $_POST["mobileNumber2"];

        $_SESSION["dept_id"] = $dept_id;
        $_SESSION["status"] = $status;
        $_SESSION["onboard_date"] = $onboard_date;
        $_SESSION["email_address"] = $email_address;
        $_SESSION["mobile_number"] = $mobile_number;
        $_SESSION["mobile_number_2"] = $mobile_number_2;
        
        if (!empty($onboard_date) && !empty($email_address) && !empty($mobile_number)) {
            if (isValidEmail($email_address)) {
                if (isValidMobileNumber($mobile_number) &&
                    (empty($mobile_number_2) || isValidMobileNumber($mobile_number_2))) {
                    $wsap_info = array($dept_id,
                    $status,
                    $onboard_date,
                    $email_address,
                    $mobile_number,
                    $mobile_number_2,
                    $_SESSION["intern_id"]);
            
                    $db->query("UPDATE intern_wsap_information
                    SET department_id=:dept_id, status=:status, onboard_date=:onboard_date, email_address=:email_address,
                    mobile_number=:mobile_number, mobile_number_2=:mobile_number_2 WHERE id=:intern_id");
                    $db->setWSAPInfo($wsap_info);
                    $db->execute();
                    $db->closeStmt();
                    
                    $_SESSION["wsap_success"] = "Successfully saved the changes.";
                    unset($_SESSION["dept_id"]);
                    unset($_SESSION["status"]);
                    unset($_SESSION["onboard_date"]);
                    unset($_SESSION["email_address"]);
                    unset($_SESSION["mobile_number"]);
                    unset($_SESSION["mobile_number_2"]);
                } else {
                    $_SESSION["wsap_failed"] = "The mobile number is not a valid number!";
                }
            } else {
                $_SESSION["wsap_failed"] = "The email address is not a valid email!";
            }
        } else {
            $_SESSION["wsap_failed"] = "Please fill-out the required fields!";
        }
        redirect("profile.php#wsap-info");
        exit();
    }

    if (isset($_POST["resetWSAP"])) {
        unset($_SESSION["dept_id"]);
        unset($_SESSION["status"]);
        unset($_SESSION["onboard_date"]);
        unset($_SESSION["email_address"]);
        unset($_SESSION["mobile_number"]);
        unset($_SESSION["mobile_number_2"]);
        
        redirect("profile.php#wsap-info");
        exit();
    }

    if (isset($_POST["saveEducational"])) {
        $university = trim($_POST["university"]);
        $university_abbreviation = trim($_POST["university_abbreviation"]);
        $course = trim($_POST["course"]);
        $course_abbreviation = trim($_POST["course_abbreviation"]);
        $year = $_POST["year"];

        $_SESSION["university"] = $university;
        $_SESSION["university_abbreviation"] = $university_abbreviation;
        $_SESSION["course"] = $course;
        $_SESSION["course_abbreviation"] = $course_abbreviation;
        $_SESSION["year"] = $year;

        if (!empty($university) && !empty($course) && !empty($year) &&
        !empty($university_abbreviation) && !empty($course_abbreviation)) {
            $educational_info = array($university,
            $course,
            $university_abbreviation,
            $course_abbreviation,
            $year,
            $_SESSION["intern_id"]);
    
            $db->query("UPDATE intern_educational_information
            SET university=:university, course=:course, university_abbreviation=:university_abbreviation,
            course_abbreviation=:course_abbreviation, year=:year WHERE id=:intern_id");
            $db->setEducationalInfo($educational_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["educational_success"] = "Successfully saved the changes.";
            unset($_SESSION["university"]);
            unset($_SESSION["university_abbreviation"]);
            unset($_SESSION["course"]);
            unset($_SESSION["course_abbreviation"]);
            unset($_SESSION["year"]);
        } else {
            $_SESSION["educational_failed"] = "Please fill-out the required fields!";
        }
        redirect("profile.php#educational-info");
        exit();
    }

    if (isset($_POST["resetEducational"])) {
        unset($_SESSION["university"]);
        unset($_SESSION["university_abbreviation"]);
        unset($_SESSION["course"]);
        unset($_SESSION["course_abbreviation"]);
        unset($_SESSION["year"]);
        
        redirect("profile.php#educational-info");
        exit();
    }

    if (isset($_POST["saveAccount"])) {
        if (!empty($_POST["new_password"]) && !empty($_POST["confirm_password"]) && !empty($_POST["current_password"])) {
            if (strlen($_POST["new_password"]) > 5) {
                if (isValidPassword($_POST["new_password"])) {
                    if ($_POST["new_password"] == $_POST["confirm_password"]) {
                        if (md5($_POST["current_password"]) == $value["password"]) {
                            $new_password = array(md5($_POST["new_password"]), $_SESSION["intern_id"]);
            
                            $db->query("UPDATE intern_accounts SET password=:password WHERE id=:intern_id");
                            $db->updatePassword($new_password);
                            $db->execute();
                            $db->closeStmt();
                            
                            $_SESSION["account_success"] = "Successfully saved the changes.";
                        } else {
                            $_SESSION["account_failed"] = "Incorrect password!";
                        }
                    } else {
                        $_SESSION["account_failed"] = "The new and confirm password does not match!";
                    }
                } else {
                    $_SESSION["account_failed"] = "The password must only contain letters or numbers!";
                }
            } else {
                $_SESSION["account_failed"] = "The new password must be between 6 and 16 characters!";
            }
        } else {
            $_SESSION["account_failed"] = "Please fill-out the required fields!";
        }
        redirect("profile.php#account-info");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Profile");
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
                <h3>My Profile</h3>
            </div>
        </div>

        <div id="personal-info" class="row rounded shadow pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                    Personal Information
                </h6>
            </div>

            <div class="col-lg-4 col-md-5 p-4 pb-0 text-center">
                <form method="post" enctype="multipart/form-data">
                    <label for="image" class="form-label text-indigo fw-bold w-100">Photo</label>
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
                        } ?>" /> <?php

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
                    }

                    if (!empty($_GET["intern_id"])) {
                        if ($admin_roles_count != 0) { ?>
                            <div class="w-fit my-2 mx-auto">
                                <a class="btn btn-indigo" href="edit_profile.php?intern_id=<?= $_GET["intern_id"] ?>">
                                    <i class="fa-solid fa-pen me-2"></i></i>Edit
                                </a>
                            </div> <?php
                        }
                    } else { ?>
                        <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                            onchange="loadFile(event)" name="image" style="max-width: 350px;">
        
                        <button class="btn btn-sm btn-smoke border-dark mt-2 w-100" style="max-width: 150px;"
                            type="submit" name="uploadImage">Upload</button> <?php
                    } ?>
                </form>
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
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-6">
                            <div class="row">
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="lastName">Last Name <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input type="text" name="lastName" class="form-control"
                                    value="<?php
                                        if (isset($_SESSION["last_name"])) {
                                            echo $_SESSION["last_name"];
                                        } else {
                                            echo $value["last_name"];
                                        } ?>" maxLength="32" <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            disabled <?php
                                        } ?>>
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="firstName">First Name <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input type="text" name="firstName" class="form-control"
                                    value="<?php
                                        if (isset($_SESSION["first_name"])) {
                                            echo $_SESSION["first_name"];
                                        } else {
                                            echo $value["first_name"];
                                        } ?>" maxLength="32" <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            disabled <?php
                                        } ?>>
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="middleName">Middle Name</label>
                                    <input type="text" name="middleName" class="form-control"
                                    value="<?php
                                        if (isset($_SESSION["middle_name"])) {
                                            echo $_SESSION["middle_name"];
                                        } else {
                                            echo $value["middle_name"];
                                        } ?>" maxLength="32" <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            disabled <?php
                                        } ?>>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 col-md-12 col-sm-6">
                            <div class="row <?php
                                if (empty($_GET["intern_id"])) { ?>
                                    mb-4 <?php
                                } ?>">
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="birthday">Birthday <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input type="date" name="birthday" class="form-control"
                                    value="<?php
                                        if (isset($_SESSION["birthday"])) {
                                            echo $_SESSION["birthday"];
                                        } else {
                                            echo date("Y-m-d", strtotime($value["birthday"]));
                                        } ?>" <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            disabled <?php
                                        } ?>>
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="gender">Gender</label>
                                    <select name="gender" class="form-select" <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            disabled <?php
                                        } ?>>
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
                    </div> <?php
                    if (empty($_GET["intern_id"])) { ?>
                        <div class="bottom-right">
                            <button class="btn btn-indigo" type="submit" name="savePersonal">Save Changes</button>
                            <button class="btn btn-danger" name="resetPersonal">Reset</button>
                        </div> <?php
                    } ?>
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
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="intern_id">Intern ID</label>
                                            <input type="text" name="intern_id" class="form-control text-uppercase"
                                                value="<?= $value["id"]; ?>" disabled>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="department">Department</label>
                                            <select name="department" class="form-select" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>> <?php
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
                                            <select name="status" class="form-select" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>>
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
                                                } ?>>Withdrew</option>
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
                                            <label class="mb-2" for="onboardDate">Onboard Date <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="date" name="onboardDate" class="form-control"
                                            value="<?php
                                                if (isset($_SESSION["onboard_date"])) {
                                                    echo $_SESSION["onboard_date"];
                                                } else {
                                                    echo date("Y-m-d", strtotime($value["onboard_date"]));
                                                } ?>" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="offboardDate">Estimated Offboard Date
                                            </label>
                                            <input type="text" name="offboardDate" class="form-control"
                                            value="<?php
                                            $rendering_days = round(($value["target_rendering_hours"]-$value["rendered_hours"])/8);
                                            $estimated_weekends = ceil(($rendering_days/5) * 2);
                                            $rendering_days += $estimated_weekends + 1;

                                            echo date("m/d/Y", strtotime($date->getDate()." + ".$rendering_days." days")); ?>"
                                            disabled>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="renderedHours">Rendered Hours</label>
                                            <input type="number" name="renderedHours" class="form-control"
                                                value="<?= $value["rendered_hours"]; ?>" disabled>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="targetRenderingHours">Target Rendering Hours</label>
                                            <input type="number" name="targetRenderingHours" class="form-control"
                                                value="<?= $value["target_rendering_hours"]; ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="row mt-2 <?php
                                if (empty($_GET["intern_id"])) { ?>
                                    mb-4 <?php
                                } ?>">
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="emailAddress">Email Address <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input name="emailAddress" class="form-control"
                                        value="<?php if(isset($_SESSION["email_address"])) {
                                                echo $_SESSION["email_address"];
                                            } else {
                                                echo $value["email_address"];
                                            } ?>" maxLength="64" <?php
                                            if (!empty($_GET["intern_id"])) { ?>
                                                disabled <?php
                                            } ?>>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="mobileNumber">Mobile Number <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber" class="form-control"
                                        value="<?php if(isset($_SESSION["mobile_number"])) {
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
                                    <label class="text-indigo mb-2" for="mobileNumber2">Mobile Number 2</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber2" class="form-control"
                                        value="<?php if(isset($_SESSION["mobile_number_2"])) {
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
                    </div> <?php

                    if (empty($_GET["intern_id"])) { ?>
                        <div class="bottom-right">
                            <button class="btn btn-indigo" type="submit" name="saveWSAP">Save Changes</button>
                            <button class="btn btn-danger" name="resetWSAP">Reset</button>
                        </div> <?php
                    } ?>
                </form> <?php
                unset($_SESSION["dept_id"]);
                unset($_SESSION["status"]);
                unset($_SESSION["onboard_date"]);
                unset($_SESSION["email_address"]);
                unset($_SESSION["mobile_number"]);
                unset($_SESSION["mobile_number_2"]); ?>
            </div>
        </div>

        <div id="educational-info" class="row rounded shadow mt-4 pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                    Educational Information
                </h6>
            </div>

            <div class="col-12 p-4"> <?php
                if (isset($_SESSION["educational_success"])) { ?>
                    <div class="alert alert-success text-success">
                        <?php
                            echo $_SESSION["educational_success"];
                            unset($_SESSION["educational_success"]);
                        ?>
                    </div> <?php
                }
                
                if (isset($_SESSION["educational_failed"])) { ?>
                    <div class="alert alert-danger text-danger">
                        <?php
                            echo $_SESSION["educational_failed"];
                            unset($_SESSION["educational_failed"]);
                        ?>
                    </div> <?php
                } ?>
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row <?php
                                if (empty($_GET["intern_id"])) { ?>
                                    mb-4 <?php
                                } ?>">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-12 user_input my-1">
                                            <label class="text-indigo mb-2" for="university">University <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="university" class="form-control"
                                                value="<?php if(isset($_SESSION["university"])) {
                                                echo $_SESSION["university"];
                                                } else {
                                                    echo $value["university"];
                                                } ?>" maxLength="64" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>>
                                        </div>
                                        <div class="col-lg-6 col-md-12 user_input my-1">
                                            <label class="text-indigo mb-2" for="course">Course <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="course" class="form-control"
                                                value="<?php if(isset($_SESSION["course"])) {
                                                echo $_SESSION["course"];
                                                } else {
                                                    echo $value["course"];
                                                } ?>" maxLength="64" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="university_abbreviation">University Abbreviation <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="university_abbreviation" class="form-control"
                                                value="<?php if(isset($_SESSION["university_abbreviation"])) {
                                                echo $_SESSION["university_abbreviation"];
                                                } else {
                                                    echo $value["university_abbreviation"];
                                                } ?>" maxLength="16" <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    disabled <?php
                                                } ?>>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="course_abbreviation">Course Abbreviation <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="course_abbreviation" class="form-control"
                                                value="<?php if(isset($_SESSION["course_abbreviation"])) {
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
                                            <select name="year" class="form-select" <?php
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
                    </div> <?php
                    if (empty($_GET["intern_id"])) { ?>
                        <div class="bottom-right">
                            <button class="btn btn-indigo" type="submit" name="saveEducational">Save Changes</button>
                            <button class="btn btn-danger" name="resetEducational">Reset</button>
                        </div> <?php
                    } ?>
                </form> <?php
                unset($_SESSION["university"]);
                unset($_SESSION["university_abbreviation"]);
                unset($_SESSION["course"]);
                unset($_SESSION["course_abbreviation"]);
                unset($_SESSION["year"]); ?>
            </div>
        </div>

        <div id="account-info" class="row rounded shadow mt-4 pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                    Account Information
                </h6>
            </div>

            <div class="col-12 p-4"> <?php
                if (isset($_SESSION["account_success"])) { ?>
                    <div class="alert alert-success text-success">
                        <?php
                            echo $_SESSION["account_success"];
                            unset($_SESSION["account_success"]);
                        ?>
                    </div> <?php
                }
                
                if (isset($_SESSION["account_failed"])) { ?>
                    <div class="alert alert-danger text-danger">
                        <?php
                            echo $_SESSION["account_failed"];
                            unset($_SESSION["account_failed"]);
                        ?>
                    </div> <?php
                } ?>
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row <?php
                                if (empty($_GET["intern_id"])) { ?>
                                    mb-4 <?php
                                } ?>">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="text-indigo mb-2" for="date_activated">Date Activated</label>
                                            <input type="date" name="date_activated" class="form-control"
                                                value="<?= $value["date_activated"]; ?>" disabled>
                                        </div> <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="text-indigo mb-2" for="new_password">New Password
                                                    <span class="text-danger">*</span></label>
                                                <input type="password" name="new_password" class="form-control" maxLength="16">
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="text-indigo mb-2" for="confirm_password">Confirm Password
                                                    <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control" maxLength="16">
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="text-indigo mb-2" for="current_password">Current Password
                                                    <span class="text-danger">*</span></label>
                                                <input type="password" name="current_password" class="form-control" maxLength="16">
                                            </div> <?php
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <?php
                    if (empty($_GET["intern_id"])) { ?>
                        <div class="bottom-right">
                            <button class="btn btn-indigo" type="submit" name="saveAccount">Submit</button>
                            <button class="btn btn-danger" type="reset">Clear</button>
                        </div> <?php
                    } ?>
                </form>
            </div>
        </div>

        <div id="roles" class="row rounded shadow mt-4 pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                    Roles
                </h6>
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
                    $db->query("SELECT intern_roles.*, roles.*, roles.name AS role_name,
                    brands.*, brands.name AS brand_name, departments.*, departments.name AS dept_name
                    FROM intern_roles, roles
                    LEFT JOIN brands ON roles.brand_id = brands.id 
                    LEFT JOIN departments ON roles.department_id = departments.id
                    WHERE intern_roles.role_id=roles.id AND intern_roles.intern_id=:intern_id");
                    $db->setInternId($value["id"]);
                    $db->execute();

                    $count = 0;
                    while ($row = $db->fetch()) {
                        $count++;  ?>
                        <tr>
                            <div class="modal fade" id="removeRole<?= $row["id"] ?>" tabindex="-1"
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
                                        
                                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                            <div class="modal-body">
                                                <div class="text-center">
                                                    <div class="summary-total mt-2 w-fit mx-auto">
                                                        <h5 class="text-dark fs-regularmb-0"><?= $row["role_name"] ?></h5>
                                                        <h6><?php
                                                            if (!empty($row["dept_name"])) {
                                                                echo $row["dept_name"];
                                                            } else {
                                                                echo "No Department";
                                                            } ?></h6>
                                                        <h6 class="fs-f mb-0"><?php
                                                            if (!empty($row["brand_name"])) {
                                                                echo $row["brand_name"];
                                                            } else {
                                                                echo "No Brand";
                                                            } ?></h6>
                                                        <input type="text" name="intern_role_id" class="form-control text-center d-none mt-2"
                                                            value="<?= $row["id"] ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="btnRemoveRole" class="btn btn-danger">Remove</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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