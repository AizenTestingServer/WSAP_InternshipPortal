<?php
    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    function navSideBar($target_active) {
        $db = new Database();
        $date = new Date();
        
        $db->query("SELECT intern_personal_information.*, intern_roles.*, roles.*
        FROM intern_personal_information, intern_roles, roles
        WHERE intern_personal_information.id=intern_roles.intern_id AND
        intern_roles.role_id=roles.id AND roles.admin=1 AND
        intern_personal_information.id=:intern_id");
        $db->setInternId($_SESSION["intern_id"]);
        $db->execute();
        $admin_roles_count = $db->rowCount(); ?>
        <div class="side-nav border-end p-1 vh-100 position-fixed">
            <div class="d-flex align-items-center flex-column">
                <img class="img-responsive" src="../Assets/img/brand_logo/WSAP.png" alt=""
                    onerror="this.src='../Assets/img/no_image_found.jpeg';">
                <span class="fw-bold">WSAP IP</span>
            </div>
            <div class="navs py-4 d-flex justify-content-center align-items-center">
                <ul class="side-nav-list p-0 text-center">
                    <li>
                        <a <?php
                        if ($target_active == "dashboard") { ?>
                            class="active" <?php
                        } else { ?>
                            class="inactive"
                            href="dashboard.php" <?php
                        } ?>>
                            <div class="icon-container">
                                <i class="fa-solid fa-table-columns fa-2x"></i>
                            </div>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a <?php
                        if ($target_active == "brands") { ?>
                            class="active" <?php
                        } else { ?>
                            class="inactive"
                            href="brands.php" <?php
                        } ?>>
                            <div class="icon-container">
                                <i class="fa-solid fa-bullhorn fa-2x"></i>
                            </div> Brands
                        </a>
                    </li> <?php
                    if ($admin_roles_count != 0) { ?>
                        <li class="dropdown-toggler">
                            <a <?php
                            if ($target_active == "attendance") { ?>
                                class="active" <?php
                            } else { ?>
                                class="inactive" <?php
                            } ?>>
                                <div class="icon-container">
                                    <i class="fa-solid fa-clock fa-2x"></i>
                                </div> Attendance
                            </a>
                            <ul class="dropdown-list text-start">
                                <li><a href="attendance.php">My Attendance</a></li>
                                <li><a href="interns_attendance.php">Interns' Attendance</a></li>
                                <li><a href="calendar.php?month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>">Calendar</a></li>
                                <li><a href="daily_time_record.php">Interns' DTR</a></li>
                                <li><a href="offboarding_forecast.php">Offboarding Forecast</a></li>
                            </ul>
                        </li> <?php
                    } else { ?>
                        <li class="dropdown-toggler">
                            <a <?php
                            if ($target_active == "attendance") { ?>
                                class="active" <?php
                            } else { ?>
                                class="inactive" <?php
                            } ?>>
                                <div class="icon-container">
                                    <i class="fa-solid fa-clock fa-2x"></i>
                                </div> Attendance
                            </a>
                            <ul class="dropdown-list text-start">
                                <li><a href="attendance.php">My Attendance</a></li>
                                <li><a href="offboarding_forecast.php">Offboarding Forecast</a></li>
                            </ul>
                        </li> <?php
                    } ?>
                    <li class="dropdown-toggler">
                        <a <?php
                        if ($target_active == "interns") { ?>
                            class="active" <?php
                        } else { ?>
                            class="inactive" <?php
                        } ?>>
                            <div class="icon-container">
                                <i class="fa-solid fa-users fa-2x"></i>
                            </div> Interns
                        </a>
                        <ul class="dropdown-list text-start">
                            <li><a href="profile.php">My Profile</a></li>
                            <li><a href="interns.php">Interns</a></li>
                            <li><a href="admins.php">Admins</a></li> <?php
                            if ($admin_roles_count != 0) { ?>
                                <li><a href="roles.php">Roles</a></li>
                                <li><a href="assign_roles.php">Assign Roles</a></li> <?php
                            } ?>
                        </ul>
                    </li> <?php
                    if ($admin_roles_count != 0) { ?>
                        <li>
                            <a <?php
                            if ($target_active == "auditLogs") { ?>
                                class="active" <?php
                            } else { ?>
                                class="inactive"
                                href="audit_logs.php?day=<?= $date->getDay() ?>&month=<?= $date->getMonthName() ?>&year=<?= $date->getYear() ?>" <?php
                            } ?>>
                                <div class="icon-container">
                                    <i class="fa-solid fa-book fa-2x"></i>
                                </div>
                                Audit Logs
                            </a>
                        </li> <?php
                    } ?>
                </ul>
            </div>
            <div class="d-flex justify-content-center align-items-center mt-3">
                <ul class="side-nav-list p-0 text-center m-0">
                    <li>
                        <a class="text-danger" href="sign_out.php">
                            <div class="icon-container">
                                <i class="fa-solid fa-arrow-right-from-bracket fa-2x"></i>
                            </div>
                            Sign out
                        </a>
                    </li>
                </ul>
            </div>
        </div> <?php
    }



?>