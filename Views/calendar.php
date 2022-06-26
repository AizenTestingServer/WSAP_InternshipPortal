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
            $db->query("SELECT DISTINCT att_date FROM attendance");
            $db->execute();

            while ($row = $db->fetch()) { ?>
                <a class="clickable-card"
                href="interns_attendance.php?date=<?= $row["att_date"] ?>"
                draggable="false">
                    <div class="calendar-item text-center ">
                        <div class="calendar-date mt-2 p-2">
                            <h6><?= date('Y', strtotime($row["att_date"])) ?></h6>
                            <h1><?= date('d', strtotime($row["att_date"])) ?></h1>
                            <h6><?= date('F', strtotime($row["att_date"])) ?></h6>
                        </div>
                        <div class="bottom d-flex justify-content-evenly border py-2"> <?php
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
                                if (isMorningShift(strtotime($row["time_in"]), strtotime($row["time_out"]))) {
                                    $morning_shift_count++;
                                } else if (isAfternoonShift(strtotime($row["time_in"]), strtotime($row["time_out"]))) {
                                    $afternoon_shift_count++;
                                }
                            } ?>
                            <div>
                                <h8>MS</h8>
                                <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1 mt-1">
                                    <?= $morning_shift_count ?>
                                </p>
                            </div>
                            <div>
                                <h8>FT</h8>
                                <p class="bg-info text-dark rounded w-fit m-auto px-2 pt-1 pb-1 mt-1">
                                    <?= $value["present"] - $morning_shift_count - $afternoon_shift_count ?>
                                </p>
                            </div>
                            <div>
                                <h8>AS</h8>
                                <p class="bg-secondary text-light rounded w-fit m-auto px-2 pt-1 pb-1 mt-1">
                                    <?= $afternoon_shift_count ?>
                                </p>
                            </div>
                        </div>
                        <div class="bottom d-flex justify-content-evenly border py-2">
                            <div>
                                <h8>Present</h8>
                                <p class="bg-success text-light rounded w-fit m-auto px-2 pt-1 pb-1 mt-1">
                                    <?= $value["present"] ?>
                                </p>
                            </div>
                            <div>
                                <h8>Absent</h8>
                                <p class="bg-danger text-light rounded w-fit m-auto px-2 pt-1 pb-1 mt-1">
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