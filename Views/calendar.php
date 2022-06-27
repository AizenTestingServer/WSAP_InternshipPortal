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

    $db->query("SELECT COUNT(*) as active_interns FROM intern_wsap_information WHERE status = 1");
    $db->execute();

    $active_interns = 0;
    if ($value = $db->fetch()) {
        $active_interns = $value["active_interns"];
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Interns");
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

        <div class="row align-items-center mb-2">
          <div class="col-md-12">
            <h3>Calendar</h3>
          </div>
        </div>

        <div>
          <div class="row">

          </div>
        </div>

        <div class="calendar"> <?php
            $records_db = new Database();
            $records_db->query("SELECT DISTINCT att_date FROM attendance ORDER BY id DESC");
            $records_db->execute();

            while ($row = $records_db->fetch()) { ?>
                <a class="clickable-card" href="interns_attendance.php?date=<?= $row["att_date"] ?>"
                draggable="false">
                    <div class="calendar-item text-center">
                        <div class="calendar-date mt-2">
                            <h6 class="text-dark fw-bold"><?= date('Y', strtotime($row["att_date"])) ?></h6>
                            <h1 class="text-dark"><?= date('d', strtotime($row["att_date"])) ?></h1>
                            <h6 class="fw-bold mb-0"><?= date('F', strtotime($row["att_date"])) ?></h6>
                            <h6><?= date("D", strtotime($row["att_date"])); ?></h6>
                        </div>
                        <div class="bottom d-flex justify-content-evenly border py-1"> <?php
                            $db->query("SELECT COUNT(*) as present FROM attendance WHERE att_date=:att_date");
                            $db->setAttDate($row["att_date"]);
                            $db->execute();
                            $value = $db->fetch(); 

                            $db->query("SELECT * FROM attendance WHERE att_date=:att_date");
                            $db->setAttDate($row["att_date"]);
                            $db->execute();

                            $morning_shift_count = 0;
                            $afternoon_shift_count = 0;
                            while($row = $db->fetch()) {
                                $time_in = $row["time_in"];
                                $time_out = $row["time_out"];

                                if (strlen($time_in) > 8) {
                                    $time_in = substr($time_in, 0, 8);
                                }
                                
                                if (strlen($time_out) > 8) {
                                    $time_out = substr($time_out, 0, 8);
                                }

                                if (isMorningShift(strtotime($time_in), strtotime($time_out))) {
                                    $morning_shift_count++;
                                } else if (isAfternoonShift(strtotime($time_in), strtotime($time_out))) {
                                    $afternoon_shift_count++;
                                }
                            } ?>
                            <div>
                                <p class="fs-e mb-1">MS</p>
                                <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                    <?= $morning_shift_count ?>
                                </p>
                            </div>
                            <div>
                                <p class="fs-e mb-1">FT</p>
                                <p class="bg-info text-dark rounded w-fit m-auto px-2 pt-1 pb-1">
                                    <?= $value["present"] - $morning_shift_count - $afternoon_shift_count ?>
                                </p>
                            </div>
                            <div>
                                <p class="fs-e mb-1">AS</p>
                                <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                    <?= $afternoon_shift_count ?>
                                </p>
                            </div>
                        </div>
                        <div class="bottom d-flex justify-content-evenly border border-top-0 py-1">
                            <div>
                                <p class="fs-e mb-1">Present</p>
                                <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                    <?= $value["present"] ?>
                                </p>
                            </div>
                            <div>
                                <p class="fs-e mb-1">Absent</p>
                                <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1">
                                    <?= $active_interns - $value["present"] ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </a> <?php
            } ?>
        </div>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>