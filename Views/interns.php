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
    
    if (isset($_POST["btnAddIntern"])) {
        if (!empty($_POST["lastName"]) && !empty($_POST["firstName"])) {
            $intern_id = $date->getYear()."-".randomWord();

            $personal_info = array($intern_id,
            ucwords($_POST["lastName"]),
            ucwords($_POST["firstName"]),
            ucwords($_POST["middleName"]));
    
            $db->query("INSERT INTO intern_personal_information (id, last_name, first_name, middle_name)
            VALUES(:intern_id, :last_name, :first_name, :middle_name)");
            $db->insertPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION['personal_success'] = "Successfully added a record.";
        } else {
            $_SESSION['personal_failed'] = "Please fill-out the required fields!";
        }
        redirect('interns.php');
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Interns");
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
        
        <div class="row align-items-center mb-2">
            <div class="col-md-12">
                <h3>Interns</h3>
            </div>
        </div> <?php
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
        <button type="button" class="btn btn-indigo" data-bs-toggle="modal" 
            data-bs-target="#addInternModal">Add Intern</button>

        <div class="modal fade" id="addInternModal" tabindex="-1" aria-labelledby="addInternModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addInternModalLabel">Add Intern</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                        <div class="modal-body">
                            <div class="row">
                                <!-- <div class="col-6 user_input my-1">
                                    <label class="text-indigo mb-2" for="intern_id">Intern ID</label>
                                    <div class="input-group">
                                        <input type="text" name="intern_id" class="form-control" disabled>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-smoke border-dark">Regen</button>
                                        </div>
                                    </div>
                                </div> -->
                                <div class="col-12 user_input my-1">
                                    <label class="mb-2" for="lastName">Last Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="lastName" class="form-control" maxLength="32">
                                </div>
                                <div class="col-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="firstName">First Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="firstName" class="form-control" maxLength="32">
                                </div>
                                <div class="col-12 user_input my-1">
                                    <label class="text-indigo mb-2" for="middleName">Middle Name</label>
                                    <input type="text" name="middleName" class="form-control" maxLength="32">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="btnAddIntern" class="btn btn-success">Submit</button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>