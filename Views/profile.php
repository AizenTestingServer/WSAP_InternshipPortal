<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"])) {
        redirect("../index");
        exit();
    }
    
    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    $db = new Database();
    $date = new Date();

    $db->query("SELECT intern_wsap_information.*, intern_personal_information.*
    FROM intern_wsap_information, intern_personal_information
    WHERE intern_wsap_information.id=:intern_id AND intern_personal_information.id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    
    $value = $db->fetch();

    if (isset($_POST["uploadImg"])) {
        if (!empty($_POST["img"]) && $check !== false && $is_image) {
            $upload_img = array($_SESSION["intern_id"], $_POST["img"]);
    
            $db->query("UPDATE intern_wsap_information SET img=:img WHERE id=:intern_id");
            $db->setImg($upload_img);
            $db->execute();
            $db->closeStmt();
            
            redirect('profile');
            exit();
        } else {
            $_SESSION['upload_failed'] = "Please select an image file!";
            redirect('profile');
            exit();
        }
    }

    if (isset($_POST["savePersonal"])) {
        if (!empty($_POST["lastName"]) && !empty($_POST["firstName"]) && !empty($_POST["birthday"])) {
            $personal_info = array($_POST["lastName"],
            $_POST["firstName"],
            $_POST["middleName"],
            $_POST["gender"],
            $_POST["birthday"],
            $_SESSION["intern_id"]);
    
            $db->query("UPDATE intern_personal_information
            SET last_name=:last_name, first_name=:first_name, middle_name=:middle_name,
            gender=:gender, birthday=:birthday WHERE id=:intern_id");
            $db->setPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION['personal_success'] = "Successfully saved the changes.";
            redirect('profile');
            exit();
        } else {
            $_SESSION['personal_failed'] = "Please fill-out the required fields!";
            redirect('profile');
            exit();
        }
    }

    if (isset($_POST["saveWSAP"])) {
        if (!empty($_POST["onboardDate"]) && !empty($_POST["emailAddress"]) && !empty($_POST["mobileNumber"])) {
            $wsap_info = array($_POST["department"],
            $_POST["status"],
            $_POST["onboardDate"],
            $_POST["emailAddress"],
            $_POST["mobileNumber"],
            $_POST["mobileNumber2"],
            $_SESSION["intern_id"]);
    
            $db->query("UPDATE intern_wsap_information
            SET department_id=:dept_id, status=:status, onboard_date=:onboard_date, email_address=:email_address,
            mobile_number=:mobile_number, mobile_number_2=:mobile_number_2 WHERE id=:intern_id");
            $db->setWSAPInfo($wsap_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION['wsap_success'] = "Successfully saved the changes.";
            redirect('profile');
            exit();
        } else {
            $_SESSION['wsap_failed'] = "Please fill-out the required fields!";
            redirect('profile');
            exit();
        }
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Profile");
?> 
<div class="my-container position-relative"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("interns");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_settings.php"; ?>
        </div>
        
        <div class="row align-items-center mb-2">
            <div class="col-md-12">
                <h3>My Profile</h3>
            </div>
        </div>
        <div class="row rounded shadow pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                    Personal Information
                </h6>
            </div>

            <div class="col-lg-4 col-md-5 p-4 pb-0 text-center">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <label for="profile_photo" class="form-label text-indigo fw-bold w-100">Photo</label>
                    <img class="mb-2" id="output" src="<?php {
                            if ($value["img"] == null || strlen($value["img"]) == 0) {
                                if ($value["gender"] == 0) {
                                    echo "../Assets/img/profile_imgs/default_male.png";
                                } else {
                                    echo "../Assets/img/profile_imgs/default_female.png";
                                }
                            } else {
                                echo $value["img"];
                            }
                        } ?>" /> <?php

                    if (isset($_SESSION['upload_failed'])) { ?>
                        <div class="alert alert-danger text-danger">
                            <?php
                                echo $_SESSION['upload_failed'];
                                unset($_SESSION['upload_failed']);
                            ?>
                        </div> <?php
                    } ?>

                    <input class="form-control form-control-sm mx-auto" id="formFileSm" type="file" accept="image/*"
                        onchange="loadFile(event)" style="max-width: 350px;">

                    <button class="btn btn-sm btn-smoke border-dark mt-2 w-100" style="max-width: 150px;"
                    type="submit" name="uploadImg">Upload</button>
                </form>
            </div>

            <div class="col-lg-8 col-md-7 p-4"> <?php
                if (isset($_SESSION['personal_success'])) { ?>
                    <div class="alert alert-success text-success">
                        <?php
                            echo $_SESSION['personal_success'];
                            unset($_SESSION['personal_success']);
                        ?>
                    </div> <?php
                }

                if (isset($_SESSION['personal_failed'])) { ?>
                    <div class="alert alert-danger text-danger">
                        <?php
                            echo $_SESSION['personal_failed'];
                            unset($_SESSION['personal_failed']);
                        ?>
                    </div> <?php
                } ?>
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-6">
                            <div class="row">
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="lastName">Last Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="lastName" class="form-control"
                                    value="<?= $value["last_name"]; ?>">
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="firstName">First Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="firstName" class="form-control"
                                    value="<?= $value["first_name"]; ?>">
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="middleName">Middle Name</label>
                                    <input type="text" name="middleName" class="form-control"
                                    value="<?= $value["middle_name"]; ?>">
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
                                    value="<?= date("Y-m-d", strtotime($value["birthday"])); ?>">
                                </div>
                                <div class="col-lg-4 col-md-12 user_input my-1">
                                    <label class="mb-2" for="gender">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option <?php
                                            if ($value["gender"] == 0) { ?>
                                                selected <?php
                                            } ?> value="0">Male</option>
                                        <option  <?php
                                            if ($value["gender"] == 1) { ?>
                                                selected <?php
                                            } ?> value="1">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-indigo btn-bottom-right mt-4" type="submit" name="savePersonal">Save Changes</button>
                </form>
            </div>
        </div>
        
        <div class="row rounded shadow mt-4 pb-4 position-relative">
            <div class="rounded shadow px-0">
                <h6 class="d-block text-light px-3 pt-2 pb-2 bg-indigo rounded mb-0">
                    WSAP Information
                </h6>
            </div>

            <div class="col-12 p-4"> <?php
                if (isset($_SESSION['wsap_success'])) { ?>
                    <div class="alert alert-success text-success">
                        <?php
                            echo $_SESSION['wsap_success'];
                            unset($_SESSION['wsap_success']);
                        ?>
                    </div> <?php
                }
                
                if (isset($_SESSION['wsap_failed'])) { ?>
                    <div class="alert alert-danger text-danger">
                        <?php
                            echo $_SESSION['wsap_failed'];
                            unset($_SESSION['wsap_failed']);
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
                                            <select name="department" class="form-select"> <?php
                                                    $db->query("SELECT * FROM departments ORDER BY name");
                                                    $db->execute();

                                                    while ($row = $db->fetch()) { ?>
                                                    <option <?php
                                                        if ($value["department_id"] == $row["id"]) { ?>
                                                            selected <?php
                                                        } ?> value="<?= $row["id"] ?>"><?= $row["name"] ?> </option> <?php
                                                    } ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="status">Status</label>
                                            <select name="status" class="form-select">
                                                <option <?php
                                                    if ($value["status"] == 0) { ?>
                                                        selected <?php
                                                    } ?> value="0">Inactive</option>
                                                <option  <?php
                                                    if ($value["status"] == 1) { ?>
                                                        selected <?php
                                                    } ?> value="1">Active</option>
                                                <option  <?php
                                                    if ($value["status"] == 2) { ?>
                                                        selected <?php
                                                    } ?> value="2">Offboarded</option>
                                                <option  <?php
                                                    if ($value["status"] == 3) { ?>
                                                        selected <?php
                                                    } ?> value="3">Withdrew</option>
                                                <option  <?php
                                                    if ($value["status"] == 4) { ?>
                                                        selected <?php
                                                    } ?> value="4">Extended</option>
                                                <option  <?php
                                                    if ($value["status"] == 5) { ?>
                                                        selected <?php
                                                    } ?> value="5">Suspended</option>
                                                <option  <?php
                                                    if ($value["status"] == 6) { ?>
                                                        selected <?php
                                                    } ?> value="6">Terminated</option>
                                            </select>
                                        </div>
                                    </div>
                                <div>

                                <div class="col-lg-12">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="onboardDate">Onboard Date
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="onboardDate" class="form-control"
                                            value="<?= date("Y-m-d", strtotime($value["onboard_date"])); ?>">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 user_input my-1">
                                            <label class="mb-2" for="offboardDate">Estimated Offboard Date
                                            </label>
                                            <input type="text" name="offboardDate" class="form-control"
                                            value="<?php
                                            $rendering_days = round(($value["target_rendering_hours"]-$value["rendered_hours"])/8);
                                            $estimated_weekends = ceil(($rendering_days/5) * 2);
                                            $rendering_days += $estimated_weekends;
                                            
                                            echo date('Y-m-d', strtotime($date->getDate().' + '.$rendering_days.' days')); ?>"
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
                                <div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="row mt-2 mb-4">
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="emailAddress">Email Address
                                        <span class="text-danger">*</span></label>
                                    <input type="email" name="emailAddress" class="form-control"
                                        value="<?= $value["email_address"]; ?>">
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="mobileNumber">Mobile Number
                                        <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber" class="form-control"
                                        value="<?= $value["mobile_number"]; ?>" maxLength="10">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="mobileNumber2">Mobile Number 2</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+63</span>
                                        </div>
                                        <input type="phone" name="mobileNumber2" class="form-control"
                                        value="<?= $value["mobile_number_2"]; ?>" maxLength="10">
                                    </div>
                                </div>
                            </div>
                        <div>
                    </div>

                    <button class="btn btn-indigo btn-bottom-right mt-4" type="submit" name="saveWSAP">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    var loadFile = function (event) {
        var output = document.getElementById('output');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function () {
            URL.revokeObjectURL(output.src)
        }
    };
</script>
<?php
    require_once "../Templates/footer.php";
?>