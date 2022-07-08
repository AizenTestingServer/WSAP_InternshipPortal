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

    $db_interns = new Database();
    $db_interns->query("SELECT intern_personal_information.*, intern_wsap_information.*, intern_accounts.*
    FROM intern_personal_information, intern_wsap_information, intern_accounts
    WHERE intern_personal_information.id = intern_wsap_information.id AND
    intern_wsap_information.id = intern_accounts.id
    ORDER BY last_name");

    require_once "../Templates/header_view.php";
    setTitle("Calendar");
?>

<div class="my-container">
    <?php
          include_once "nav_side_bar.php";
          navSideBar("attendance");
      ?>
    <div class="main-section p-4">
        <div class="aside">
          <?php include_once "profile_nav.php"; ?>
        </div>

        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Calendar</h3>
            </div>
        </div> <?php
        if ($admin_roles_count != 0) { ?>
            <div class="d-flex align-items-center mb-3">
                <div class="dropdown align-center me-2">
                    <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                        if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                            echo "Custom";
                        } else {
                            echo "All Records";
                        } ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li>
                            <a class="dropdown-item btn-smoke" href="calendar.php">
                                All Records
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-smoke"
                                href="calendar.php?month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>">
                                Custom
                            </a>
                        </li>
                    </ul>
                </div> <?php
                if (!empty($_GET["month"]) && !empty($_GET["year"])) { ?>
                    <div class="dropdown align-center me-2">
                        <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false" name="department">
                            <?= $_GET["month"] ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1"> <?php
                            foreach (getMonths() as $value) { ?>
                                <li>
                                    <a class="dropdown-item btn-smoke"
                                        href="calendar.php?month=<?= $value ?>&year=<?= $_GET["year"] ?>">
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
                                    <a class="dropdown-item btn-smoke"
                                        href="calendar.php?month=<?= $_GET["month"] ?>&year=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li> <?php
                            } ?>
                        </ul>
                    </div> <?php
                } ?>
            </div>

            <div class="calendar"> <?php
                $records_db = new Database();
                
                if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                    $month_year = array($_GET["month"], $_GET["year"]);
                    
                    $records_db->query("SELECT DISTINCT att_date
                    FROM attendance
                    WHERE att_date LIKE CONCAT(:month, '%', :year)
                    ORDER BY id DESC");
                    $records_db->setMonthYear($month_year);
                } else {
                    $records_db->query("SELECT DISTINCT att_date
                    FROM attendance ORDER BY id DESC");
                }
                $records_db->execute();

                while ($row = $records_db->fetch()) { ?>
                    <a class="clickable-card" href="interns_attendance.php?date=<?= $row["att_date"] ?>"
                    draggable="false">
                        <div class="calendar-item text-center">
                            <div class="calendar-date mt-2">
                                <h6 class="text-dark fw-bold"><?= date("Y", strtotime($row["att_date"])) ?></h6>
                                <h1 class="text-dark"><?= date("d", strtotime($row["att_date"])) ?></h1>
                                <h6 class="fw-bold mb-0"><?= date("F", strtotime($row["att_date"])) ?></h6>
                                <h6><?= date("D", strtotime($row["att_date"])); ?></h6>
                            </div>
                            <div class="bottom d-flex justify-content-evenly border py-1"> <?php
                                $db_interns->execute();

                                $active_interns = 0;
                                while ($row_interns = $db_interns->fetch()) {
                                    if (isActiveIntern($row_interns["onboard_date"], $row_interns["offboard_date"], $row["att_date"])) {
                                        $active_interns++;
                                    }
                                }

                                $db->query("SELECT COUNT(*) AS present FROM attendance WHERE att_date=:att_date");
                                $db->setAttDate($row["att_date"]);
                                $db->execute();
                                $value = $db->fetch();

                                $db->query("SELECT * FROM attendance WHERE att_date=:att_date");
                                $db->setAttDate($row["att_date"]);
                                $db->execute();

                                $morning_shift_count = 0;
                                $afternoon_shift_count = 0;
                                while ($row = $db->fetch()) {
                                    $time_in = $row["time_in"];
                                    $time_out = $row["time_out"];

                                    if (strlen($time_in) > 8) {
                                        $time_in = substr($time_in, 0, 8);
                                    }
                                    
                                    if (strlen($time_out) > 8) {
                                        $time_out = substr($time_out, 0, 8);
                                    }

                                    if (isMorningShift($time_in, $time_out)) {
                                        $morning_shift_count++;
                                    } else if (isAfternoonShift($time_in, $time_out)) {
                                        $afternoon_shift_count++;
                                    }
                                } ?>
                                <div>
                                    <p class="fs-e mb-1">MS</p>
                                    <p class="bg-morning text-light rounded w-fit m-auto px-2 py-1">
                                        <?= $morning_shift_count ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="fs-e mb-1">FT</p>
                                    <p class="bg-info text-dark rounded w-fit m-auto px-2 py-1">
                                        <?= $value["present"] - $morning_shift_count - $afternoon_shift_count ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="fs-e mb-1">AS</p>
                                    <p class="bg-afternoon text-light rounded w-fit m-auto px-2 py-1">
                                        <?= $afternoon_shift_count ?>
                                    </p>
                                </div>
                            </div>
                            <div class="bottom d-flex justify-content-evenly border border-top-0 py-1">
                                <div>
                                    <p class="fs-e mb-1">Present</p>
                                    <p class="bg-success text-light rounded w-fit m-auto px-2 py-1">
                                        <?= $value["present"] ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="fs-e mb-1">Absent</p>
                                    <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                        <?= $active_interns - $value["present"] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a> <?php
                } ?>
            </div> <?php
            if ($records_db->rowCount() == 0) { ?>
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