<?php
    session_start();

    require_once "./Controllers/Functions.php";

    if (isset($_SESSION["intern_id"]) && isset($_SESSION["password"])) {
        redirect("./Views/dashboard.php");
        exit();
    } else if (isset($_SESSION["intern_id"])) {
        redirect("./Views/profile_setup.php");
        exit();
    } else if (isset($_SESSION["intern_id_2"])) {
        redirect("./Views/reset_password.php");
        exit();
    }

    require_once "./Controllers/Database.php";  
    require_once "./Controllers/Date.php";

    $db = new Database();
    $date = new Date();

    if (isset($_POST["signIn"])) {
        if (!empty($_POST["intern_id"]) && !empty($_POST["password"])) {
            $_SESSION["intern_id_temp"] = $_POST["intern_id"];
            
            $db->query("SELECT intern_accounts.*, intern_personal_information.*, intern_wsap_information.*
            FROM intern_accounts, intern_personal_information, intern_wsap_information
            WHERE intern_accounts.id=:intern_id AND intern_accounts.id=intern_personal_information.id AND
            intern_accounts.id=intern_wsap_information.id");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();

            $signedIn = false;
            while ($row = $db->fetch()) {
                if ($row["id"] == strtoupper($_POST["intern_id"]) && $row["password"] == md5($_POST["password"])) {
                    $signedIn = true;
                    $value = $row;
                    break;
                }
            }

            if ($signedIn) {
                $log_value = $value["last_name"].", ".$value["first_name"]." (".$value["id"].") has signed in.";

                $log = array($date->getDateTime(),
                $value["id"],
                $log_value);

                $db->query("INSERT INTO audit_logs
                VALUES (null, :timestamp, :intern_id, :log)");
                $db->log($log);
                $db->execute();
                $db->closeStmt();

                $_SESSION["intern_id"] = $_POST["intern_id"];
                $_SESSION["password"] = $_POST["password"];
                unset($_SESSION["intern_id_temp"]);
                redirect("./Views/dashboard.php");
                exit();
            } else {
                $_SESSION["sign_in_failed"] = "Unregistered account!";
            }
        } else if (empty($_POST["intern_id"]) && empty($_POST["password"])) {
            $_SESSION["sign_in_failed"] = "Please enter your credentials.";
        } else {
            $_SESSION["intern_id_temp"] = $_POST["intern_id"];

            $db->query("SELECT * FROM intern_personal_information WHERE id=:intern_id AND
            (SELECT COUNT(*) FROM intern_accounts WHERE id=:intern_id) = 0");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();

            if ($db->rowCount() != 0) {
                $_SESSION["intern_id"] = $_POST["intern_id"];
                unset($_SESSION["intern_id_temp"]);
                redirect("./Views/profile_setup.php");
                exit();
            } else {
                $db->query("SELECT intern_personal_information.*, intern_accounts.*
                FROM intern_personal_information, intern_accounts
                WHERE intern_personal_information.id=intern_accounts.id AND
                intern_personal_information.id=:intern_id");
                $db->setInternId($_POST["intern_id"]);
                $db->execute();
                $value = $db->fetch();
                
                if (empty($value["password"])) {
                    $_SESSION["intern_id_2"] = $_POST["intern_id"];
                    unset($_SESSION["intern_id_temp"]);
                    redirect("./Views/reset_password.php");
                    exit();
                } else {                
                    $_SESSION["sign_in_failed"] = "Please enter your credentials.";
                }
            }
        }
        redirect("index.php");
        exit();
    }

    require_once "./Templates/header.php";
    setTitle("Login");
?>
<div class="login-container">
    <div class="login-outside">
        <div class="login-form rounded shadow-lg p-4">
            <div class="text-center">
                <img height="120" width="120" src="./Assets/img/Brand_Logo/WSAP.png" alt="">
                <div class="p-1 pb-0">
                    <h4 class="title">INTERNSHIP PORTAL</h4>
                </div>
                <?php
                    if (isset($_SESSION["sign_in_failed"])) { ?>
                        <div class="alert alert-danger text-danger">
                            <?php
                                echo $_SESSION["sign_in_failed"];
                                unset($_SESSION["sign_in_failed"]);
                            ?>
                        </div> <?php
                    }
                ?>
            </div>
            <!-- form-login -->
            <form class="d-flex flex-column pt-2 px-1" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                <div>
                    <input name="intern_id" type="text" class="form-control text-uppercase" placeholder="Intern ID"
                    value="<?php if (isset($_SESSION["intern_id_temp"])) {
                        echo $_SESSION["intern_id_temp"];
                    } ?>" maxLength="10">
                </div>
                <div class="mt-2">
                    <input name="password" type="password" class="form-control" placeholder="Password">
                </div>
                <div class="text-center mt-4">
                    <button name="signIn" class="btn btn-warning w-100">Sign in</button>
                </div>
            </form>
            <div class="text-center mt-4"style="line-height: 14px;">
                <h6 class="fw-bold fs-f">Developed by: WSAP Interns</h6>
                <p class="m-0 fs-e">IT Department</p>
                <p class="m-0 fs-e">Web Development Team</p>
            </div>
        </div>
    </div>
</div>
<?php
    require_once "./Templates/footer.php";
?>

