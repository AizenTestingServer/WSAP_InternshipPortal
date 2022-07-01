<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }
    
    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    $db = new Database();
    $date = new Date();

    $db->query("SELECT * FROM intern_personal_information WHERE id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    
    $value = $db->fetch();

    if (isset($_POST["setProfile"]) &&  isset($_FILES["image"])) {
        $last_name = ucwords(trim($_POST["lastName"]));
        $first_name = ucwords(trim($_POST["firstName"]));
        $middle_name = ucwords(trim($_POST["middleName"]));
        $gender = $_POST["gender"];
        $birthday = $_POST["birthday"];
        
        $dept_id = $_POST["department"];
        $status = $_POST["status"];
        $onboard_date = $_POST["onboardDate"];
        $target_rendering_hours = $_POST["targetRenderingHours"];
        $email_address = trim($_POST["emailAddress"]);
        $mobile_number = $_POST["mobileNumber"];
        $mobile_number_2 = $_POST["mobileNumber2"];
        
        $university = trim($_POST["university"]);
        $university_abbreviation = trim($_POST["university_abbreviation"]);
        $course = trim($_POST["course"]);
        $course_abbreviation = trim($_POST["course_abbreviation"]);
        $year = $_POST["year"];

        $_SESSION["last_name"] = $last_name;
        $_SESSION["first_name"] = $first_name;
        $_SESSION["middle_name"] = $middle_name;
        $_SESSION["gender"] = $gender;
        $_SESSION["birthday"] = $birthday;

        $_SESSION["dept_id"] = $dept_id;
        $_SESSION["status"] = $status;
        $_SESSION["onboard_date"] = $onboard_date;
        $_SESSION["target_rendering_hours"] = $target_rendering_hours;
        $_SESSION["email_address"] = $email_address;
        $_SESSION["mobile_number"] = $mobile_number;
        $_SESSION["mobile_number_2"] = $mobile_number_2;

        $_SESSION["university"] = $university;
        $_SESSION["university_abbreviation"] = $university_abbreviation;
        $_SESSION["course"] = $course;
        $_SESSION["course_abbreviation"] = $course_abbreviation;
        $_SESSION["year"] = $year;

        $personal_completed = !empty($last_name) && 
        !empty($first_name) && 
        !empty($birthday);

        $wsap_completed = !empty($onboard_date) &&
        !empty($target_rendering_hours) &&
        !empty($email_address) &&
        !empty($mobile_number);

        $educational_completed = !empty($university) &&
        !empty($course) && !empty($year) &&
        !empty($university_abbreviation) &&
        !empty($course_abbreviation);

        $account_completed = !empty($_POST["password"]) &&
        !empty($_POST["confirm_password"]);

        if ($personal_completed && $wsap_completed && $educational_completed && $account_completed) {
            if (isValidEmail($email_address)) {
                if (isValidMobileNumber($mobile_number) &&
                    (empty($mobile_number_2) || isValidMobileNumber($mobile_number_2))) {
                    if (strlen($_POST["password"]) > 5) {
                        if (isValidPassword($_POST["password"])) {
                            if ($_POST["password"] == $_POST["confirm_password"]) {
                                $image_name = $_FILES["image"]["name"];
                                $image_size = $_FILES["image"]["size"];
                                $tmp_name = $_FILES["image"]["tmp_name"];
                                $error = $_FILES["image"]["error"];
            
                                if (!empty($image_name)) {
                                    if ($error == 0) {
                                        $img_ex = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                                        $allowed_exs = array("jpg", "jpeg", "png");
                            
                                        if (in_array($img_ex, $allowed_exs)) {
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
                                            
                                            $image_path = "../Assets/img/profile_imgs/".$image_name;
                                            
                                            $wsap_info = array($_SESSION["intern_id"],
                                            $dept_id,
                                            $status,
                                            $onboard_date,
                                            $target_rendering_hours,
                                            0,
                                            $email_address,
                                            $mobile_number,
                                            $mobile_number_2,
                                            $image_path);
                                            
                                            $db->query("INSERT INTO intern_wsap_information
                                            VALUES(:intern_id, :dept_id, :status, :onboard_date,
                                            :target_rendering_hours, :rendered_hours,
                                            :email_address, :mobile_number, :mobile_number_2, :image)");
                                            $db->insertWSAPInfo($wsap_info);
                                            $db->execute();
                                            $db->closeStmt();
            
                                            $educational_info = array($_SESSION["intern_id"],
                                            $university,
                                            $university_abbreviation,
                                            $course,
                                            $course_abbreviation,
                                            $year);
                                    
                                            $db->query("INSERT INTO intern_educational_information
                                            VALUES(:intern_id, :university, :university_abbreviation,
                                            :course, :course_abbreviation, :year)");
                                            $db->insertEducationalInfo($educational_info);
                                            $db->execute();
                                            $db->closeStmt();
            
                                            $account_info = array($_SESSION["intern_id"],
                                            md5($_POST["password"]),
                                            date("Y-m-d", $date->getDateValue()));
                            
                                            $db->query("INSERT INTO intern_accounts
                                            VALUES(:intern_id, :password, :date_activated)");
                                            $db->insertAccount($account_info);
                                            $db->execute();
                                            $db->closeStmt();
            
                                            move_uploaded_file($tmp_name, $image_path);
                    
                                            $log_value = $last_name.", ".$first_name.
                                                "'s (".$_SESSION["intern_id"].") account has been activated.";
                                    
                                            $log = array($date->getDateTime(),
                                            strtoupper($_SESSION["intern_id"]),
                                            $log_value);
                                    
                                            $db->query("INSERT INTO audit_logs
                                            VALUES (null, :timestamp, :intern_id, :log)");
                                            $db->log($log);
                                            $db->execute();
                                            $db->closeStmt();
                                            
                                            $_SESSION["setup_success"] = "Successfully setup the profile.";
                                            $_SESSION["password"] = $_POST["password"];
                                            redirect("dashboard.php");
                                            exit();
                                        } else {
                                            $_SESSION["setup_failed"] = "The file must be an image!";
                                        }
                                    } else {
                                        $_SESSION["setup_failed"] = "There is an error occurred!";
                                    }
                                } else {
                                    $_SESSION["setup_failed"] = "You must select an image file first!";
                                }
                            } else {
                                $_SESSION["setup_failed"] = "The password does not match!";
                            }
                        } else {
                            $_SESSION["setup_failed"] = "The password must only contain letters or numbers!";
                        }
                    } else {
                        $_SESSION["setup_failed"] = "The new password must be between 6 and 16 characters!";
                    }
                } else {
                    $_SESSION["setup_failed"] = "The mobile number is not a valid number!";
                }
            } else {
                $_SESSION["setup_failed"] = "The email address is not a valid email!";
            }
        } else {
            $_SESSION["setup_failed"] = "Please fill-out the required fields!";
        }
        redirect("profile_setup.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Profile Setup");
?> 
<div class="my-container">
    <div class="main-section p-4 ms-0">
        <div class="aside">
            <?php include_once "profile_nav_setup.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Profile Setup</h3>
            </div>
        </div> <?php

        if (isset($_SESSION["setup_success"])) { ?>
            <div class="alert alert-success text-success">
                <?php
                    echo $_SESSION["setup_success"];
                    unset($_SESSION["setup_success"]);
                ?>
            </div> <?php
        }

        if (isset($_SESSION["setup_failed"])) { ?>
            <div class="alert alert-danger text-danger">
                <?php
                    echo $_SESSION["setup_failed"];
                    unset($_SESSION["setup_failed"]);
                ?>
            </div> <?php
        } ?>
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
            <div id="personal-info" class="row rounded shadow pb-4 position-relative">
                <div class="rounded shadow px-0">
                    <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                        Personal Information
                    </h6>
                </div>

                <div class="col-lg-4 col-md-5 p-4 pb-0 text-center"><label for="image" class="form-label fw-bold w-100">Photo</label>
                    <img class="mb-2" id="output"  src="<?php
                        if (isset($_SESSION["gender"])) {
                            if ($_SESSION["gender"] == 0) {
                                echo "../Assets/img/profile_imgs/default_male.png";
                            } else if ($_SESSION["gender"] == 1) {
                                echo "../Assets/img/profile_imgs/default_female.png";
                            }
                        } else {
                            echo "../Assets/img/profile_imgs/default_male.png";
                        } ?>" />
                    <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                        onchange="loadFile(event)" name="image" style="max-width: 350px;">
                </div>

                <div class="col-lg-8 col-md-7 p-4">
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
                            <div class="row">
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="birthday">Birthday
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="birthday" class="form-control"
                                        value="<?php
                                        if (isset($_SESSION["birthday"])) {
                                            echo $_SESSION["birthday"];
                                        } else {
                                            echo date("Y-m-d", $date->getDateValue());
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
                                            } else { ?>
                                                selected <?php
                                            } ?>>Male</option>
                                        <option value="1" <?php
                                            if (isset($_SESSION["gender"])) {
                                                if ($_SESSION["gender"] == 1) { ?>
                                                    selected <?php
                                                }
                                            } ?>>Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div id="wsap-info" class="row rounded shadow pb-4 position-relative">
                <div class="rounded shadow px-0">
                    <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                        WSAP Information
                    </h6>
                </div>

                <div class="col-12 p-4">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="intern_id">Intern ID</label>
                                            <input type="text" name="intern_id" class="form-control text-uppercase"
                                                value="<?= $value["id"]; ?>" disabled>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="department">Department</label>
                                            <select name="department" class="form-select"> <?php
                                                $db->query("SELECT * FROM departments ORDER BY name");
                                                $db->execute();

                                                $index = 0;
                                                while ($row = $db->fetch()) { ?>
                                                <option <?php
                                                    if (isset($_SESSION["dept_id"])) {
                                                        if ($_SESSION["dept_id"] == $row["id"]) { ?> selected <?php }
                                                    } else {
                                                        if ($index == 0) { ?> selected <?php }
                                                    } $index++; ?> value="<?= $row["id"] ?>"><?= $row["name"] ?> </option> <?php
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
                                                } ?>>Inactive</option>
                                                <option value="1" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 1) { ?>
                                                        selected <?php
                                                    }
                                                } else { ?>
                                                    selected <?php
                                                } ?>>Active</option>
                                                <option value="2" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 2) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>Offboarded</option>
                                                <option value="3" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 3) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>Withdrawn</option>
                                                <option value="4" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 4) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>Extended</option>
                                                <option value="5" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 5) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>Suspended</option>
                                                <option value="6" <?php
                                                if (isset($_SESSION["status"])) {
                                                    if ($_SESSION["status"] == 6) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>Terminated</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-36 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="onboardDate">Onboard Date
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="onboardDate" class="form-control"
                                                value="<?php
                                                if (isset($_SESSION["onboard_date"])) {
                                                    echo $_SESSION["onboard_date"];
                                                } else {
                                                    echo date("Y-m-d", $date->getDateValue());
                                                } ?>">
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="targetRenderingHours">
                                                Target Rendering Hours
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" name="targetRenderingHours" class="form-control"
                                                value="<?php if(isset($_SESSION["target_rendering_hours"])) {
                                                    echo $_SESSION["target_rendering_hours"]; } ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="row mt-2">
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="mb-2" for="emailAddress">Email Address
                                        <span class="text-danger">*</span></label>
                                    <input name="emailAddress" class="form-control"
                                        value="<?php if(isset($_SESSION["email_address"])) {
                                            echo $_SESSION["email_address"]; } ?>" maxLength="64">
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="mb-2" for="mobileNumber">Mobile Number
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber" class="form-control"
                                            value="<?php if(isset($_SESSION["mobile_number"])) {
                                                echo $_SESSION["mobile_number"]; } ?>" maxLength="10">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="mb-2" for="mobileNumber2">Mobile Number 2</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber2" class="form-control"
                                            value="<?php if(isset($_SESSION["mobile_number_2"])) {
                                                echo $_SESSION["mobile_number_2"]; } ?>" maxLength="10">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <label class="mb-2" for="university">University
                                                <span class="text-danger">*</span></label>
                                            <input type="text" name="university" class="form-control"
                                                value="<?php if(isset($_SESSION["university"])) {
                                                    echo $_SESSION["university"]; } ?>" maxLength="64">
                                        </div>
                                        <div class="col-lg-6 col-md-12 user_input my-1">
                                            <label class="mb-2" for="course">Course
                                                <span class="text-danger">*</span></label>
                                            <input type="text" name="course" class="form-control"
                                                value="<?php if(isset($_SESSION["course"])) {
                                                    echo $_SESSION["course"]; } ?>" maxLength="64">
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="university_abbreviation">University Abbreviation
                                                <span class="text-danger">*</span></label>
                                            <input type="text" name="university_abbreviation" class="form-control"
                                                value="<?php if(isset($_SESSION["university_abbreviation"])) {
                                                    echo $_SESSION["university_abbreviation"]; } ?>" maxLength="16">
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="course_abbreviation">Course Abbreviation
                                                <span class="text-danger">*</span></label>
                                            <input type="text" name="course_abbreviation" class="form-control"
                                                value="<?php if(isset($_SESSION["course_abbreviation"])) {
                                                    echo $_SESSION["course_abbreviation"]; } ?>" maxLength="12">
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="year">Year</label>
                                            <select name="year" class="form-select">
                                                <option value="1" <?php
                                                if (isset($_SESSION["year"])) {
                                                    if ($_SESSION["year"] == 1) { ?>
                                                        selected <?php
                                                    }
                                                } else { ?>
                                                    selected <?php
                                                } ?>>1</option>
                                                <option value="2" <?php
                                                if (isset($_SESSION["year"])) {
                                                    if ($_SESSION["year"] == 2) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>2</option>
                                                <option value="3" <?php
                                                if (isset($_SESSION["year"])) {
                                                    if ($_SESSION["year"] == 3) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>3</option>
                                                <option value="4" <?php
                                                if (isset($_SESSION["year"])) {
                                                    if ($_SESSION["year"] == 4) { ?>
                                                        selected <?php
                                                    }
                                                } ?>>4</option>
                                                <option value="5" <?php
                                                if (isset($_SESSION["year"])) {
                                                    if ($_SESSION["year"] == 5) { ?>
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

                <div class="col-12 p-4">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="password">Password
                                                <span class="text-danger">*</span></label>
                                            <input type="password" name="password" class="form-control" maxLength="16">
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="confirm_password">Confirm Password
                                                <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" class="form-control" maxLength="16">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row rounded shadow mt-4 pb-4 position-relative">

                <div class="col-12 p-4">
                    <div class="bottom-right">
                        <button class="btn btn-indigo" type="submit" name="setProfile">Submit</button>
                    </div>
                </div>
            </div>
        </form>
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