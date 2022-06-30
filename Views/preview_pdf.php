<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }

    if (empty($_GET["intern_id"])) {
        redirect("interns_attendance.php");
        exit();
    }

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $url = "https://";
    } else {
        $url = "http://";
    }

    // Append the host(domain name, ip) to the URL.   
    $url.= $_SERVER['HTTP_HOST'];
    
    // Append the requested resource location to the URL   
    $url.= $_SERVER['REQUEST_URI'];

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
    $db->setInternId($_GET["intern_id"]);
    $db->execute();

    $intern_info = $db->fetch();

    if (isset($_POST["generatePDF"])) {
        $_SESSION["print"] = true;
        redirect($url);
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("WSAP IP Preview DTR as PDF");
?>

<div class="my-container"> <?php
    if ($admin_roles_count != 0 || $_GET["intern_id"] == $_SESSION["intern_id"]) { ?>
        <div class="p-3">
            <div class="d-flex align-items-center mb-3"> <?php
                if (false) {
                    if ($_GET["intern_id"] == strtoupper($_SESSION["intern_id"])) { ?>
                        <a class="btn btn-secondary me-2" href="attendance.php">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back to Attendance
                        </a> <?php
                    } else { ?>
                        <a class="btn btn-secondary me-2" href="interns_attendance.php">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns' Attendance
                        </a> <?php
                    } 
                } ?>           
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
                            <a class="dropdown-item btn-smoke" href="preview_pdf.php?intern_id=<?= $_GET["intern_id"] ?>">
                                All Records
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-smoke"
                                href="preview_pdf.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>">
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
                                        href="preview_pdf.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $value ?>&year=<?= $_GET["year"] ?>">
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
                                        href="preview_pdf.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $_GET["month"] ?>&year=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li> <?php
                            } ?>
                        </ul>
                    </div> <?php
                } ?>
                <div class="ms-auto">
                    <form method="post">
                        <button class="btn btn-primary" name="generatePDF">Generate PDF</button>
                    </form>
                </div>
            </div>

            <div id="dtr-document" class="dtr-container fs-d" style="padding: 1rem;">
                <!--DTR HEADER-->
                <div class="d-flex justify-content-center my-2"><h3 class="fw-bold">DAILY TIME RECORD</h3></div>

                <div class="indicator-head-container">
                    <ul class="list-group list-group-horizontal">
                        <li class="list-group-item text-center p-3 fw-bold fs-regular" style="width: 100%; border: 1px solid black;">
                            <?= $intern_info["last_name"].", ".$intern_info["first_name"] ?>
                        </li>
                    </ul>
                </div> <?php
                
                if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                    $month_year = array($_GET["month"], $_GET["year"]);

                    $db->query("SELECT DISTINCT SUBSTRING_INDEX(att_date, ' ', 1) AS month,
                    SUBSTRING_INDEX(att_date, ' ', -1) AS year
                    FROM attendance WHERE intern_id=:intern_id AND
                    att_date LIKE CONCAT(:month, '%', :year);");
                    $db->setMonthYear($month_year);
                } else {
                    $db->query("SELECT DISTINCT SUBSTRING_INDEX(att_date, ' ', 1) AS month,
                    SUBSTRING_INDEX(att_date, ' ', -1) AS year
                    FROM attendance WHERE intern_id=:intern_id;");
                }
                $db->setInternId($_GET["intern_id"]);
                $db->execute();

                $total_rendered_hours = 0;
                
                while ($month = $db->fetch()) { ?>
                    <div class="indicator-head-container my-3">
                        <ul class="list-group list-group-horizontal list-unstyled fw-bold">
                            <li class="text-center" style="width: 100%;">
                                <?= strtoupper($month["month"])." ".$month["year"] ?>
                            </li>
                        </ul>
                        <p class="list-group-item text-center mb-0" style="width: 100%;">Official hours for arrival and departure</p>
                    </div>

                    <!--DTR BODY-->
                    <table class="table table-bordered text-center" style="border: 1px solid black; margin-top: 21px; margin-bottom: 40px;">
                        <thead>
                            <tr>
                                <th scope="col-span row-span" style="width: 10%;"></th>
                                <th scope="col-span row-span" style="width: 20%;" colspan="2">AM</th>
                                <th scope="col-span row-span" style="width: 20%;" colspan="2">PM</th>
                                <th scope="col-span row-span" style="width: 10%;" class="align-middle" rowspan="2">Rendered Hours</th>
                                <th scope="col-span row-span" style="width: 10%;" class="align-middle" rowspan="2">Overtime Hours</th>
                            </tr>
                            <tr>
                                <th scope="col-span row-span">DAY</th>
                                <th scope="col-span row-span">Arrival</th>
                                <th scope="col-span row-span">Departure</th>
                                
                                <th scope="col-span row-span">Arrival</th>
                                <th scope="col-span row-span">Departure</th>
                            </tr>
                        </thead>

                        <tbody> <?php
                            $selected_month = date("m", strtotime($month["month"]));
                            $selected_year = date("Y", strtotime($month["year"]));
                            $number_of_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

                            $att_db = new Database();

                            $att_db->query("SELECT * FROM attendance WHERE intern_id=:intern_id");
                            $att_db->setInternId($_GET["intern_id"]);
                            $att_db->execute();

                            $rendered_hours_in_month = 0;
                            $ot_hours_in_month = 0;

                            for ($i = 1; $i <= $number_of_days + 1; $i++) { 
                                if ($i == $number_of_days + 1) { ?>
                                    <tr>
                                        <th scope="row" colspan="5"><?= "Total in ".$month["month"] ?></th>
                                        <td><?= $rendered_hours_in_month ?></td>
                                        <td><?= $ot_hours_in_month ?></td>
                                    </tr> <?php
                                } else { ?>
                                    <tr> <?php
                                        $rendered_hours = 0;
                                        $ot_hours = 0; ?>
                                        <th scope="row"><?= $i ?></th> <?php
                                        $no_record = true;

                                        $att_db->execute();
                                        while ($att = $att_db->fetch()) {
                                            $att_day = date("d", strtotime($att["att_date"]));
                                            $att_month = date("m", strtotime($att["att_date"]));
                                            $att_year = date("Y", strtotime($att["att_date"]));

                                            if ($att_day == $i && $att_month == $selected_month && $att_year == $selected_year) {
                                                $time_in_am = $att["time_in"];
                                                $time_out_pm = $att["time_out"];

                                                $time_in_pm = "tmp";
                                                $time_out_am = "tmp"; 
                                                
                                                if (!empty($time_in_am) && !empty($time_out_pm) && $time_out_pm != "NTO") {
                                                    if (strlen($time_in_am) > 8) {
                                                        $time_in_am = substr($time_in_am, 0, 8);
                                                    }                                    
                                                    if (strlen($time_out_pm) > 8) {
                                                        $time_out_pm = substr($time_out_pm, 0, 8);
                                                    }

                                                    if (isMorningShift($time_in_am, $time_out_pm) || isAfternoonShift($time_in_am, $time_out_pm)) {
                                                        $rendered_hours = 4;
                                                    } else {
                                                        $rendered_hours = 8;
                                                    }

                                                    if (isOvertime($time_out_pm)) {
                                                        $dt_time_out_start = new DateTime(date("G:i", $date->time_out_start()));
                                                        $dt_time_out = new DateTime(date("G:i", strtotime($time_out_pm)));
                                                        $ot_hours += $dt_time_out_start->diff($dt_time_out)->format("%h");
                                                        $rendered_minutes = $dt_time_out_start->diff($dt_time_out)->format("%i");
                                                        $ot_hours += round($rendered_minutes/60, 1);
                                                    }
                                                }
                                                
                                                if ($time_out_pm == "NTO") {
                                                    $time_out_pm = "";
                                                }
                                                
                                                if (strtotime($time_in_am) >= $date->morning_shift_end()) {
                                                    $time_in_pm = $time_in_am;
                                                    $time_in_am = "";
                                                }
                                                
                                                if (strtotime($time_out_pm) < $date->afternoon_shift_start()) {
                                                    $time_out_am = $time_out_pm;
                                                    $time_out_pm = "";
                                                }

                                                if ($time_in_pm == "tmp") {
                                                    $time_in_pm = "1:00 pm";
                                                }

                                                if ($time_out_am == "tmp") {
                                                    $time_out_am = "12:00 pm";
                                                }

                                                if (empty($time_in_am)) {
                                                    $time_out_am = "";
                                                }

                                                if (empty($time_out_pm)) {
                                                    $time_in_pm = "";
                                                }

                                                if (empty($time_in_am) && empty($time_in_pm) && empty($time_out_am) && empty($time_out_pm)) {
                                                    $rendered_hours = "";
                                                    $ot_hours = "";
                                                }
                                                ?>

                                                <td><?= $time_in_am ?></td>
                                                <td><?= $time_out_am ?></td>
                                                <td><?= $time_in_pm ?></td>
                                                <td><?= $time_out_pm ?></td>
                                                <td><?= $rendered_hours ?></td>
                                                <td><?= $ot_hours ?></td> <?php
                                                $no_record = false;
                                                break;
                                            }
                                        }
                                        
                                        if ($no_record) { ?>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td> <?php
                                        }

                                        if (empty($rendered_hours)) {
                                            $rendered_hours = 0;
                                        }

                                        if (empty($ot_hours)) {
                                            $ot_hours = 0;
                                        }
                                        
                                        $rendered_hours_in_month += $rendered_hours; 
                                        $ot_hours_in_month += $ot_hours; ?>                                        
                                    </tr> <?php
                                }
                            }
                            
                            $total_rendered_hours += $rendered_hours_in_month + $ot_hours_in_month; ?>
                        </tbody>
                    </table> <?php
                }
                if ($db->rowCount() == 0) { ?>
                    <div class="indicator-head-container my-3">
                        <ul class="list-group list-group-horizontal list-unstyled fw-bold">
                            <li class="text-center" style="width: 100%;">
                                <?php
                                    if (!empty($_GET["month"]) && !empty($_GET["year"])) {
                                        echo strtoupper($_GET["month"])." ".$_GET["year"];
                                    } else {
                                        echo strtoupper($date->getMonthName())." ".$date->getYear();
                                    }
                                ?>
                            </li>
                        </ul>
                        <p class="list-group-item text-center mb-0" style="width: 100%;">Official hours for arrival and departure</p>
                    </div>

                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } ?>

                <!--DTR FOOTER-->
                <div class="indicator-footer-container ">
                    <ul class="list-group list-group-horizontal">
                        <li class="list-group-item text-center" style="width: 80%; border: 1px solid black;">Total Rendered Hours</li>
                        <li class="list-group-item fw-bold" style="width: 20%; border: 1px solid black; border-left: 0">
                            <?= $total_rendered_hours ?>
                        </li>
                    </ul>
                </div>

                <div class="indicator-description-container">
                    <ul class="list-group list-group-horizontal">
                    <li class="list-group-item text-center py-3 px-5 my-3" style="width: 100%; border: 1px solid black;">I CERTIFY on my honor that the
                        above is a true and correct report of the hours of work performed, record of which was made daily at the
                        time of arrival and departure from office</li>
                    </ul>
                </div>

                <ul class="list-group list-group-horizontal">
                    <li class="list-group-item text-center" style="width: 100%; border: 1px solid black;">Verified as to the prescribed office hours</li>
                </ul>
            </div> 
        </div><?php
    } else { ?>
        <div class="d-flex justify-content-center w-100"> <?php
            include_once "access_denied.php"; ?>
        </div> <?php
    } ?>
</div>
<script> <?php 
    if (isset($_SESSION["print"])) { ?>
        const dtr = document.getElementById("dtr-document");
        html2pdf().from(dtr).save(); <?php
        unset($_SESSION["print"]);
    } ?>
</script>
<?php
    require_once "../Templates/footer.php";
?>