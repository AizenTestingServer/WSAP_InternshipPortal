<?php
    require_once "../Controllers/Date.php";

    function navSideBar($target_active) {
        $date = new Date(); ?>
        <div class="side-nav border-end p-1 vh-100 position-fixed">
            <div class="d-flex align-items-center flex-column">
                <img class="img-responsive" src="../Assets/img/Brand_Logo/WSAP.png" alt=""
                    onerror="this.src='../Assets/img/profile_imgs/no_image_found.jpeg';">
                <span class="fw-bold">WSAP IP</span>
            </div>
            <ul class="side-nav-list px-1 mt-4 text-center">
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
                <li class="dropdown-toggler">
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
                    <ul class="dropdown-list">
                        <!--
                        <li><a href="#">Organizational Chart</a></li>
                        <li><a href="#">Promotions</a></li>
                        <li><a href="brands.php">Websites</a></li>
                        -->
                    </ul>
                </li>
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
                    </ul>
                </li>
                <!-- <li class="dropdown-toggler">
                    <a <?php
                    if ($target_active == "tasks") { ?>
                        class="active" <?php
                    } else { ?>
                        class="inactive" <?php
                    } ?>>
                        <div class="icon-container">
                            <i class="fa-solid fa-tasks fa-2x"></i>
                        </div> Tasks
                    </a>
                    <ul class="dropdown-list text-start">
                        <li><a href="tasks.php">My Tasks</a></li>
                        <li><a href="interns_tasks.php">Interns' Tasks</a></li>
                    </ul>
                </li> -->
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
                        <li><a href="admins.php">Admins</a></li>
                        <li><a href="roles.php">Roles</a></li>
                        <li><a href="assign_roles.php">Assign Roles</a></li>
                    </ul>
                </li>
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
                </li>
            </ul>
        </div> <?php
    }



?>