<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id_2"])) {
        redirect("../index.php");
        exit();
    }
    
    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    $db = new Database();
    $date = new Date();

    $db->query("SELECT * FROM intern_personal_information WHERE id=:intern_id");
    $db->setInternId($_SESSION["intern_id_2"]);
    $db->execute();
    
    $value = $db->fetch();

    if (isset($_POST["setPassword"])) {
        if (!empty($_POST["password"]) && !empty($_POST["confirm_password"])) {
            if (strlen($_POST["password"]) > 5) {
                if (isValidPassword($_POST["password"])) {
                    if ($_POST["password"] == $_POST["confirm_password"]) {
                        $password = array(md5($_POST["password"]), $_SESSION["intern_id_2"]);
            
                        $db->query("UPDATE intern_accounts SET password=:password WHERE id=:intern_id");
                        $db->updatePassword($password);
                        $db->execute();
                        $db->closeStmt(); 
                    
                        $_SESSION["setup_success"] = "Successfully setup the password.";
                        $_SESSION["intern_id"] = $_SESSION["intern_id_2"];
                        $_SESSION["password"] = $_POST["password"];
                        unset($_SESSION["intern_id_2"]);
                        redirect("dashboard.php");
                        exit();
                    } else {
                        $_SESSION["setup_failed"] = "The new and confirm password does not match!";
                    }
                } else {
                    $_SESSION["setup_failed"] = "The password must only contain letters or numbers!";
                }
            } else {
                $_SESSION["setup_failed"] = "The new password must be between 6 and 16 characters!";
            }
        } else {
            $_SESSION["setup_failed"] = "Please fill-out the required fields!";
        }
        redirect("profile.php#account-info");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Reset Password");
?> 
<div class="my-container">
    <div class="main-section p-4 ms-0">
        <div class="aside">
            <?php include_once "profile_nav_setup.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Reset Password</h3>
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
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
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
                        <button class="btn btn-indigo" type="submit" name="setPassword">Submit</button>
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