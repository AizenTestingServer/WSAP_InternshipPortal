<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }

    if (empty($_GET["intern_id"])) {
        redirect("attendance.php");
        //redirect("preview_excel.php?intern_id=".strtoupper($_SESSION["intern_id"]));
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

    $nto_array = array($_GET["intern_id"], "NTO");
    $db->query("SELECT COUNT(*) as count FROM attendance
    WHERE intern_id=:intern_id AND time_out=:time_out");
    $db->selectInternIdAndTimeOut($nto_array);
    $db->execute();
    $nto_value = $db->fetch();

    if ($nto_value["count"] != 0) {
        redirect("attendance.php");
        exit();
    }
    
    $db->query("SELECT intern_personal_information.*, intern_roles.*, roles.*
    FROM intern_personal_information, intern_roles, roles
    WHERE intern_personal_information.id=intern_roles.intern_id AND
    intern_roles.role_id=roles.id AND roles.admin=1 AND
    intern_personal_information.id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    $admin_roles_count = $db->rowCount();

    $db->query("SELECT intern_wsap_information.*, intern_personal_information.*, intern_educational_information.*, intern_accounts.*, departments.*
    FROM intern_wsap_information, intern_personal_information, intern_educational_information, intern_accounts, departments
    WHERE intern_wsap_information.id=:intern_id AND
    intern_personal_information.id=:intern_id AND
    intern_educational_information.id=:intern_id AND
    intern_wsap_information.department_id=departments.id AND
    intern_accounts.id=:intern_id");
    $db->setInternId($_GET["intern_id"]);
    $db->execute();
    $intern_info = $db->fetch();
    $intern_count = $db->rowCount();

    if ($intern_count == 0) {
        redirect("attendance.php");
        exit();
    }

    if (isset($_POST["exportToExcel"])) {
        $_SESSION["excel"] = true;
        redirect($url);
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Preview DTR as Excel");
?>

<div class="my-container"> <?php
    if ($admin_roles_count != 0 || $_GET["intern_id"] == $_SESSION["intern_id"]) { ?>
        <div class="w-100 p-3 pb-0">
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
                            <a class="dropdown-item btn-smoke" href="preview_excel.php?intern_id=<?= $_GET["intern_id"] ?>">
                                All Records
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item btn-smoke"
                                href="preview_excel.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>">
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
                                        href="preview_excel.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $value ?>&year=<?= $_GET["year"] ?>">
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
                                        href="preview_excel.php?intern_id=<?= $_GET["intern_id"] ?>&month=<?= $_GET["month"] ?>&year=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li> <?php
                            } ?>
                        </ul>
                    </div> <?php
                } ?>
                <div class="ms-auto">
                    <form method="post">
                        <button name="exportToExcel" class="btn btn-excel">Export to Excel</button>
                    </form>
                </div>
            </div>

            <div id="dtr-document" class="dtr-container fs-d" style="padding: 1rem;"> <?php
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
                
                $att_db = new Database();

                $att_db->query("SELECT * FROM attendance WHERE intern_id=:intern_id");
                $att_db->setInternId($_GET["intern_id"]);

                $total_rendered_hours = 0; ?>
                    <!--DTR BODY-->
                    <div class="fs-b">
                        <table id="dtr" class="table table-bordered text-center me-1" style="border: 1px solid black; margin-top: 21px;"> <?php            
                            while ($row = $db->fetch()) { 
                                $selected_month = date("m", strtotime($row["month"]));
                                $selected_year = date("Y", strtotime($row["year"]));
                                $number_of_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);

                                $rendered_hours_in_month = 0;
                                $ot_hours_in_month = 0; ?>
                                <thead>
                                    <tr>
                                        <th scope="col-span row-span" class="fw-bold" colspan="14"><?= strtoupper($row["month"])." ".$row["year"] ?></th>
                                    </tr>
                                    <tr>
                                        <th scope="col-span row-span" class="fw-bold" colspan="2">Name:</th>
                                        <td scope="col-span row-span" colspan="5">
                                            <?= $intern_info["last_name"].", ".$intern_info["first_name"] ?>
                                        </td>
                                        <th scope="col-span row-span" class="fw-bold" colspan="2">Onboard Date:</th>
                                        <td scope="col-span row-span" colspan="5">
                                            <?= date("F j, Y", strtotime($intern_info["onboard_date"])) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="col-span row-span" class="fw-bold" colspan="2">Department:</th>
                                        <td scope="col-span row-span" colspan="5">
                                            <?= $intern_info["name"] ?>
                                        </td> <?php
                                        if (empty($value["offboard_date"])) { ?>
                                            <th scope="col-span row-span" class="fw-bold" colspan="2">Est. Offboard Date:</th> <?php
                                        } else { ?>
                                            <th scope="col-span row-span" class="fw-bold" colspan="2">Offboard Date:</th> <?php
                                        } ?>
                                        <td scope="col-span row-span" colspan="5">
                                            <?php
                                                if (empty($value["offboard_date"])) {
                                                    $rendering_days = floor(($intern_info["target_rendering_hours"]-$intern_info["rendered_hours"])/9);

                                                    $estimated_weekend_days = floor(($rendering_days/5) * 2);
                                                    $rendering_days += $estimated_weekend_days;

                                                    echo date("F j, Y", strtotime($date->getDate()." + ".$rendering_days." days"));
                                                } else {
                                                echo date("F j, Y", strtotime($intern_info["offboard_date"]));
                                                } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="col-span row-span" style="width: 5%;"></th>
                                        <th scope="col-span row-span" style="width: 10%;" colspan="2">AM</th>
                                        <th scope="col-span row-span" style="width: 10%;" colspan="2">PM</th>
                                        <th scope="col-span row-span" style="width: 5%;" class="align-middle" rowspan="2">Rendered Hours</th>
                                        <th scope="col-span row-span" style="width: 5%;" class="align-middle" rowspan="2">Overtime Hours</th>

                                        <th scope="col-span row-span" style="width: 5%;"></th>
                                        <th scope="col-span row-span" style="width: 10%;" colspan="2">AM</th>
                                        <th scope="col-span row-span" style="width: 10%;" colspan="2">PM</th>
                                        <th scope="col-span row-span" style="width: 5%;" class="align-middle" rowspan="2">Rendered Hours</th>
                                        <th scope="col-span row-span" style="width: 5%;" class="align-middle" rowspan="2">Overtime Hours</th>
                                    </tr>
                                    <tr>
                                        <th scope="col-span row-span">DAY</th>
                                        <th scope="col-span row-span">Arrival</th>
                                        <th scope="col-span row-span">Departure</th>
                                        
                                        <th scope="col-span row-span">Arrival</th>
                                        <th scope="col-span row-span">Departure</th>

                                        <th scope="col-span row-span">DAY</th>
                                        <th scope="col-span row-span">Arrival</th>
                                        <th scope="col-span row-span">Departure</th>
                                        
                                        <th scope="col-span row-span">Arrival</th>
                                        <th scope="col-span row-span">Departure</th>
                                    </tr>
                                </thead>

                                <tbody> <?php
                                    for ($i = 1; $i <= $number_of_days; $i++) { 
                                        if ($i == $number_of_days) { ?>
                                            <tr>
                                                <td colspan="7"></td> <?php
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
                                                        $rendered_hours = $att["regular_hours"];
                                                        $ot_hours = $att["rendered_hours"] - $att["regular_hours"];
                                                        
                                                        $time_in_am = $att["time_in"];
                                                        $time_out_pm = $att["time_out"];

                                                        $time_in_pm = "tmp";
                                                        $time_out_am = "tmp"; 
                                                        
                                                        if (!empty($time_in_am) && !empty($time_out_pm) && $time_out_pm != "NTO") {
                                                            if (strlen($time_in_am) > 8) {
                                                                $time_in_am = trim(substr($time_in_am, 0, 8));
                                                            }                                    
                                                            if (strlen($time_out_pm) > 8) {
                                                                $time_out_pm = trim(substr($time_out_pm, 0, 8));
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
                                        } else if ($i <= floor($number_of_days / 2)) { ?>
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
                                                        $rendered_hours = $att["regular_hours"];
                                                        $ot_hours = $att["rendered_hours"] - $att["regular_hours"];
                                                        
                                                        $time_in_am = $att["time_in"];
                                                        $time_out_pm = $att["time_out"];

                                                        $time_in_pm = "tmp";
                                                        $time_out_am = "tmp"; 
                                                        
                                                        if (!empty($time_in_am) && !empty($time_out_pm) && $time_out_pm != "NTO") {
                                                            if (strlen($time_in_am) > 8) {
                                                                $time_in_am = trim(substr($time_in_am, 0, 8));
                                                            }                                    
                                                            if (strlen($time_out_pm) > 8) {
                                                                $time_out_pm = trim(substr($time_out_pm, 0, 8));
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
                                                $ot_hours_in_month += $ot_hours;

                                                // SPLIT
                                                
                                                $rendered_hours = 0;
                                                $ot_hours = 0; 
                                                $temp_i = $i + floor($number_of_days / 2); ?>
                                                <th scope="row"><?= $temp_i ?></th> <?php
                                                $no_record = true;

                                                $att_db->execute();
                                                while ($att = $att_db->fetch()) {
                                                    $att_day = date("d", strtotime($att["att_date"]));
                                                    $att_month = date("m", strtotime($att["att_date"]));
                                                    $att_year = date("Y", strtotime($att["att_date"]));

                                                    if ($att_day == $temp_i && $att_month == $selected_month && $att_year == $selected_year) {
                                                        $rendered_hours = $att["regular_hours"];
                                                        $ot_hours = $att["rendered_hours"] - $att["regular_hours"];

                                                        $time_in_am = $att["time_in"];
                                                        $time_out_pm = $att["time_out"];

                                                        $time_in_pm = "tmp";
                                                        $time_out_am = "tmp"; 
                                                        
                                                        if (!empty($time_in_am) && !empty($time_out_pm) && $time_out_pm != "NTO") {
                                                            if (strlen($time_in_am) > 8) {
                                                                $time_in_am = trim(substr($time_in_am, 0, 8));
                                                            }                                    
                                                            if (strlen($time_out_pm) > 8) {
                                                                $time_out_pm = trim(substr($time_out_pm, 0, 8));
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
                                                $ot_hours_in_month += $ot_hours;  ?>
                                            </tr> <?php
                                        }
                                    }
                                    
                                    $total_rendered_hours += $rendered_hours_in_month + $ot_hours_in_month; ?>

                                    <th colspan="12"><?= "Total in ".$row["month"] ?></th>
                                    <th><?= $rendered_hours_in_month ?></th>
                                    <th><?= $ot_hours_in_month ?></th>
                                </tbody> <?php
                            } 
                            if ($db->rowCount() != 0) { ?>
                                <tbody>
                                    <th colspan="12">Total Rendered Hours</th>
                                    <th colspan="2"><?= $total_rendered_hours ?></th>
                                </tbody> <?php
                            } ?>
                        </table>
                    </div> <?php
                if ($db->rowCount() == 0) { ?>
                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } ?>
            </div> 
        </div><?php
    } else { ?>
        <div class="d-flex justify-content-center w-100"> <?php
            include_once "access_denied.php"; ?>
        </div> <?php
    } ?>
</div>
<script> <?php
    if (isset($_SESSION["excel"])) { ?>
        var table2excel = new Table2Excel();
        table2excel.export(document.getElementById("dtr"),
            "<?= $intern_info["last_name"].", ".$intern_info["first_name"]."'s Daily Time Record" ?>"); <?php
        unset($_SESSION["excel"]);
    } ?>
</script>
<?php
    require_once "../Templates/footer.php";
?>