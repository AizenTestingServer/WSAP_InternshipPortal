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

    if (!empty($_GET["intern_id"]) && $admin_roles_count == 0) {
        redirect("profile.php");
        exit();
    }

    $db->query("SELECT * FROM attendance WHERE intern_id=:intern_id ORDER BY id DESC LIMIT 1;");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    $lts_att = $db->fetch();

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
    $intern_count = $db->rowCount();

    if ($intern_count == 0) {
        redirect("profile.php");
        exit();
    }
    
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
                    $image_name = str_replace("#", "", $image_name);
                    $image_name = $_SESSION["intern_id"]."_".$image_name;
                    $image_path = "../Assets/img/profile_imgs/".$image_name;
                    move_uploaded_file($tmp_name, $image_path);
      
                    $profile_image = array(
                        $image_path,
                        strtoupper($_SESSION["intern_id"]),
                    );
    
                    $db->query("UPDATE intern_wsap_information
                    SET image=:image WHERE id=:intern_id");
                    $db->setProfileImage($profile_image);
                    $db->execute();
                    $db->closeStmt();

                    $image = array(
                        strtoupper($_SESSION["intern_id"]),
                        $image_name
                    );

                    $db->query("SELECT * FROM images
                    WHERE intern_id=:intern_id AND image_name=:image_name");
                    $db->selectImage($image);
                    $db->execute();
                    $image_count = $db->rowCount();

                    if ($image_count == 0) {
                        $upload_image = array(
                            strtoupper($_SESSION["intern_id"]),
                            $image_path,
                            $image_name
                        );
        
                        $db->query("INSERT INTO images VALUES
                        (null, :intern_id, :image_path, :image_name)");
                        $db->uploadImage($upload_image);
                        $db->execute();
                        $db->closeStmt();
                    }
                    
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
        $last_name = toProper(fullTrim($_POST["lastName"]));
        $first_name = toProper(fullTrim($_POST["firstName"]));
        $middle_name = toProper(fullTrim($_POST["middleName"]));
        $gender = $_POST["gender"];
        $birthdate = $_POST["birthdate"];

        $_SESSION["last_name"] = $last_name;
        $_SESSION["first_name"] = $first_name;
        $_SESSION["middle_name"] = $middle_name;
        $_SESSION["gender"] = $gender;
        $_SESSION["birthdate"] = $birthdate;

        if (!empty($last_name) && !empty($first_name) && !empty($birthdate)) {
            $personal_info = array($last_name,
            $first_name,
            $middle_name,
            $gender,
            $birthdate,
            $_SESSION["intern_id"]);
    
            $db->query("UPDATE intern_personal_information
            SET last_name=:last_name, first_name=:first_name, middle_name=:middle_name,
            gender=:gender, birthdate=:birthdate WHERE id=:intern_id");
            $db->setPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["personal_success"] = "Successfully saved the changes.";
            unset($_SESSION["last_name"]);
            unset($_SESSION["first_name"]);
            unset($_SESSION["middle_name"]);
            unset($_SESSION["birthdate"]);
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
        unset($_SESSION["birthdate"]);

        redirect("profile.php#personal-info");
        exit();
    }

    if (isset($_POST["saveWSAP"])) {
        $email_address = fullTrim($_POST["emailAddress"]);
        $mobile_number = $_POST["mobileNumber"];
        $mobile_number_2 = $_POST["mobileNumber2"];

        $_SESSION["email_address"] = $email_address;
        $_SESSION["mobile_number"] = $mobile_number;
        $_SESSION["mobile_number_2"] = $mobile_number_2;
        
        if (!empty($email_address) && !empty($mobile_number)) {
            if (isValidEmail($email_address)) {
                if (isValidMobileNumber($mobile_number) &&
                    (empty($mobile_number_2) || isValidMobileNumber($mobile_number_2))) {
                    $wsap_info = array($email_address,
                    $mobile_number,
                    $mobile_number_2,
                    $_SESSION["intern_id"]);
            
                    $db->query("UPDATE intern_wsap_information
                    SET email_address=:email_address, mobile_number=:mobile_number, mobile_number_2=:mobile_number_2
                    WHERE id=:intern_id");
                    $db->setWSAPInfo3($wsap_info);
                    $db->execute();
                    $db->closeStmt();
                    
                    $_SESSION["wsap_success"] = "Successfully saved the changes.";
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
        unset($_SESSION["email_address"]);
        unset($_SESSION["mobile_number"]);
        unset($_SESSION["mobile_number_2"]);
        
        redirect("profile.php#wsap-info");
        exit();
    }

    if (isset($_POST["saveEducational"])) {
        $university = fullTrim($_POST["university"]);
        $university_abbreviation = fullTrim($_POST["university_abbreviation"]);
        $course = fullTrim($_POST["course"]);
        $course_abbreviation = fullTrim($_POST["course_abbreviation"]);
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

    if (empty($_GET["intern_id"])) {
        setTitle("My Profile");
    } else {
        setTitle("Intern's Profile");
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
                <h3> <?php
                if (empty($_GET["intern_id"])) {
                    echo "My Profile";
                } else {
                    echo "Intern's Profile";
                } ?>
                </h3>
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
                        } ?>" onerror="this.src='../Assets/img/no_image_found.jpeg';"> <?php

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
                                <a class="btn btn-indigo" href="edit_interns_profile.php?intern_id=<?= $_GET["intern_id"] ?>">
                                    <i class="fa-solid fa-pen me-2"></i></i>Edit
                                </a>
                            </div> <?php
                        }
                    } else { ?>
                        <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                            onchange="loadFile(event)" name="image" style="max-width: 350px;">
        
                        <button class="btn btn-sm btn-smoke mt-2 w-100" style="max-width: 150px;"
                            type="submit" name="uploadImage">Upload</button> <?php
                    } ?>
                </form>

                <div class="modal fade" id="myPhotosModal" tabindex="-1" aria-labelledby="myPhotosModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="myPhotosModalLabel">My Photos</h5>
                                <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                    <i class="fa-solid fa-close"></i>
                                </button>
                            </div>

                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                <div class="modal-body">
                                    <div class="images-grid p-2"> <?php
                                        $images_db = new Database();

                                        $images_db->query("SELECT intern_personal_information.*, images.*
                                        FROM intern_personal_information, images
                                        WHERE intern_personal_information.id = images.intern_id AND
                                        intern_personal_information.id=:intern_id");
                                        $images_db->setInternId($_SESSION["intern_id"]);
                                        $images_db->execute();

                                        while ($images = $images_db->fetch()) { ?>
                                            <div class="p-2">
                                                <img src="<?= $images["image_path"] ?>" class="image"
                                                    onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                                <div class="w-100 d-flex justify-content-center">
                                                    <p class="my-2" style="max-width: 200px; height: 20px; overflow: hidden;"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="<?= $images["image_name"] ?>">
                                                        <?= $images["image_name"] ?>
                                                    </p>
                                                </div> <?php
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
                </div> <?php

                if (empty($_GET["intern_id"])) { ?>
                    <button class="btn btn-sm btn-primary mt-2 w-100" style="max-width: 150px;"
                        data-bs-toggle="modal" data-bs-target="#myPhotosModal">My Photos</button> <?php
                } ?>
                
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
                                    <input type="text" name="lastName" class="form-control <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            fw-bold <?php
                                        } ?>"
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
                                    <label class="mb-2" for="firstName">First Name <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input type="text" name="firstName" class="form-control <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            fw-bold <?php
                                        } ?>"
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
                                    <label class="mb-2" for="middleName">Middle Name</label>
                                    <input type="text" name="middleName" class="form-control <?php
                                        if (!empty($_GET["intern_id"])) { ?>
                                            fw-bold <?php
                                        } ?>"
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
                                    <label class="mb-2" for="birthdate">Birthdate <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label> <?php
                                    if (!empty($_GET["intern_id"])) { ?>
                                         <input type="text" name="birthdate" class="form-control fw-bold"
                                         value="<?= date("F j, Y", strtotime($value["birthdate"])) ?>" disabled> <?php
                                    } else { ?>
                                    <input type="date" name="birthdate" class="form-control" 
                                        value="<?php
                                        if (isset($_SESSION["birthdate"])) {
                                            echo $_SESSION["birthdate"];
                                        } else {
                                            echo date("Y-m-d", strtotime($value["birthdate"]));
                                        } ?>"> <?php
                                    } ?>
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="gender">Gender</label>
                                    <select name="gender" class="form-select <?php
                                            if (!empty($_GET["intern_id"])) { ?>
                                                fw-bold <?php
                                            } ?>" <?php
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
                unset($_SESSION["birthdate"]); ?>
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
                                            <label class="mb-2" for="intern_id">Intern ID</label>
                                            <input type="text" name="intern_id" class="form-control text-uppercase fw-bold"
                                                value="<?= $value["id"]; ?>" disabled>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="department">Department</label>
                                            <select name="department" class="form-select fw-bold" disabled> <?php
                                                $db->query("SELECT * FROM departments WHERE id=:id ORDER BY name");
                                                $db->setId($value["department_id"]);
                                                $db->execute();

                                                while ($row = $db->fetch()) { ?>
                                                    <option value="<?= $row["id"] ?>" selected><?= $row["name"] ?> </option> <?php
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="status">Status</label>
                                            <select name="status" class="form-select fw-bold" disabled> <?php
                                            if ($value["status"] == 0) { ?>
                                                <option value="0" selected>Inactive</option> <?php
                                            }
                                            if ($value["status"] == 1) { ?>
                                                <option value="0" selected>Active</option> <?php
                                            }
                                            if ($value["status"] == 2) { ?>
                                                <option value="0" selected>Offboarded</option> <?php
                                            }
                                            if ($value["status"] == 3) { ?>
                                                <option value="0" selected>Withdrawn</option> <?php
                                            }
                                            if ($value["status"] == 4) { ?>
                                                <option value="0" selected>Extended</option> <?php
                                            }
                                            if ($value["status"] == 5) { ?>
                                                <option value="0" selected>Suspended</option> <?php
                                            }
                                            if ($value["status"] == 6) { ?>
                                                <option value="0" selected>Terminated</option> <?php
                                            } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="onboardDate">Onboard Date</label>
                                            <input type="text" name="onboardDate" class="form-control fw-bold"
                                            value="<?= date("F j, Y", strtotime($value["onboard_date"])) ?>" disabled>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1"> <?php
                                            if (empty($value["offboard_date"])) { ?>
                                                <label class="mb-2" for="offboardDate">Estimated Offboard Date</label>
                                                <input type="text" name="offboardDate" class="form-control fw-bold"
                                                value="<?php
                                                $rendering_days = floor(($value["target_rendering_hours"]-$value["rendered_hours"])/9);

                                                $estimated_weekend_days = floor(($rendering_days/5) * 2);
                                                $rendering_days += $estimated_weekend_days;

                                                if (!empty($lts_att) && $lts_att["att_date"] == $date->getDate() && !empty($lts_att["time_out"])) {
                                                    $rendering_days += 1;
                                                }

                                                echo date("F j, Y", strtotime($date->getDate()." + ".$rendering_days." days")); ?>"
                                                disabled> <?php
                                            } else { ?>
                                                <label class="mb-2" for="offboardDate">Offboard Date</label>
                                                <input type="text" name="offboardDate" class="form-control fw-bold"
                                                value="<?= date("F j, Y", strtotime($value["offboard_date"])); ?>"
                                                disabled> <?php
                                            } ?>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="renderedHours">Rendered Hours</label>
                                            <input type="number" name="renderedHours" class="form-control fw-bold"
                                                value="<?= $value["rendered_hours"]; ?>" step="any" disabled>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="targetRenderingHours">Target Rendering Hours</label>
                                            <input type="number" name="targetRenderingHours" class="form-control fw-bold"
                                                value="<?= $value["target_rendering_hours"]; ?>" step="1" disabled>
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
                                    <label class="mb-2" for="emailAddress">Email Address <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <input name="emailAddress" class="form-control <?php
                                            if (!empty($_GET["intern_id"])) { ?>
                                                fw-bold <?php
                                            } ?>"
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
                                    <label class="mb-2" for="mobileNumber">Mobile Number <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <span class="text-danger">*</span> <?php
                                        } ?>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber" class="form-control <?php
                                            if (!empty($_GET["intern_id"])) { ?>
                                                fw-bold <?php
                                            } ?>"
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
                                        <input type="phone" name="mobileNumber2" class="form-control <?php
                                            if (!empty($_GET["intern_id"])) { ?>
                                                fw-bold <?php
                                            } ?>"
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
                                            <label class="mb-2" for="university">University <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="university" class="form-control <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    fw-bold <?php
                                                } ?>"
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
                                            <label class="mb-2" for="course">Course <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="course" class="form-control <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    fw-bold <?php
                                                } ?>"
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
                                            <label class="mb-2" for="university_abbreviation">University Abbreviation <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="university_abbreviation" class="form-control <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    fw-bold <?php
                                                } ?>"
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
                                            <label class="mb-2" for="course_abbreviation">Course Abbreviation <?php
                                                if (empty($_GET["intern_id"])) { ?>
                                                    <span class="text-danger">*</span> <?php
                                                } ?>
                                            </label>
                                            <input type="text" name="course_abbreviation" class="form-control <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    fw-bold <?php
                                                } ?>"
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
                                            <select name="year" class="form-select <?php
                                                if (!empty($_GET["intern_id"])) { ?>
                                                    fw-bold <?php
                                                } ?>" <?php
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
                                            <label class="mb-2" for="date_activated">Date Activated</label>
                                            <input type="text" name="date_activated" class="form-control fw-bold"
                                                value="<?= date("F j, Y", strtotime($value["date_activated"])) ?>" disabled>
                                        </div> <?php
                                        if (empty($_GET["intern_id"])) { ?>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="new_password">New Password
                                                    <span class="text-danger">*</span></label>
                                                <input type="password" name="new_password" class="form-control" maxLength="16">
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="confirm_password">Confirm Password
                                                    <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control" maxLength="16">
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                                <label class="mb-2" for="current_password">Current Password
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
            
            <table class="table fs-d text-center mt-2">
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
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

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