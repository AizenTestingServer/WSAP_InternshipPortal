<?php
    function navSideBar($targetActive) { ?>
        <div class="side-nav border-end p-1 vh-100 position-fixed">
            <div class="d-flex align-items-center flex-column">
                <img class="img-responsive" src="../Assets/img/Brand_Logo/WSAP.png" alt="">
                <span class="fw-bold">WSAP IP</span>
            </div>
            <ul class="side-nav-list px-1 mt-4">
                <li>
                    <a <?php
                    if ($targetActive == "dashboard") { ?>
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
                    if ($targetActive == "brands") { ?>
                        class="active" <?php
                    } else { ?>
                        class="inactive" <?php
                    } ?>>
                        <div class="icon-container">
                            <i class="fa-solid fa-bullhorn fa-2x"></i>
                        </div> Brand
                    </a>
                    <ul class="dropdown-list">
                        <!--
                        <li><a href="#">Organizational Chart</a></li>
                    <li><a href="#">Promotions</a></li>
                        -->
                        <li><a href="brands.php">Websites</a></li>
                    </ul>
                </li>
                <!--
                <li>
                    <a href="department.php">
                        <div class="icon-container">
                            <i class="fa-solid fa-user-group fa-2x"></i>
                        </div> Department
                    </a>
                </li>
                <li class="dropdown-toggler">
                    <a href="#">
                        <div class="icon-container">
                            <i class="fa-solid fa-bullhorn fa-2x"></i>
                        </div> Brand
                    </a>
                    <ul class="dropdown-list">
                        <li><a href="#">Organizational Chart</a></li>
                        <li><a href="#">Promotions</a></li>
                        <li><a href="brands.php">Websites</a></li>
                    </ul>
                </li>
                -->
                <li class="dropdown-toggler">
                    <a <?php
                    if ($targetActive == "attendance") { ?>
                        class="active" <?php
                    } else { ?>
                        class="inactive" <?php
                    } ?>>
                        <div class="icon-container">
                            <i class="fa-solid fa-clock fa-2x"></i>
                        </div> Attendance
                    </a>
                    <ul class="dropdown-list">
                        <li><a href="attendance.php">My Attendance</a></li>
                        <li><a href="interns_attendance.php">Interns' Attendance</a></li>
                        <li><a href="calendar.php">Calendar</a></li>
                        <!-- 
                        <li><a href="attendance_it.php">IT Dept</a></li>
                        <li><a href="attendance_hr.php">HR Dept</a></li>
                        <li><a href="attendance_mktg.php">Marketing Dept</a></li>
                        <li><a href="attendance_ops.php">Operations Dept</a></li>
                        <li><a href="attendance_acct.php">Acct Dept</a></li>
                        <li><a href="attendance_bd.php">Business Dept</a></li>
                        -->
                    </ul>
                </li>
                <!--
                <li>
                    <a href="schedule.php">
                        <div class="icon-container">
                            <i class="fa-solid fa-calendar-week fa-2x"></i>
                        </div> Events
                    </a>
                </li>
                <li class="dropdown-toggler">
                    <a href="#">
                        <div class="icon-container"><i class="fa-solid fa-file-word fa-2x"></i></div> Request
                    </a>
                    <ul class="dropdown-list">
                        <li><a href="#">OJT Documents</a></li>
                        <li><a href="#">Change of Schedule</a></li>
                        <li><a href="#">COC / DTR</a></li>
                    </ul>
                </li>
                -->

                <li class="dropdown-toggler">
                    <a <?php
                    if ($targetActive == "interns") { ?>
                        class="active" <?php
                    } else { ?>
                        class="inactive" <?php
                    } ?>>
                        <div class="icon-container">
                            <i class="fa-solid fa-users fa-2x"></i>
                        </div> Interns
                    </a>
                    <ul class="dropdown-list">
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="interns.php">Interns</a></li>
                    </ul>
                </li>
                <!--
                <li><a href="help.php">
                        <div class="icon-container"><i class="fa-solid fa-handshake-angle fa-2x"></i></div> Help
                    </a>
                </li>
                -->
                <li>
                    <a <?php
                    if ($targetActive == "roles") { ?>
                        class="active" <?php
                    } else { ?>
                        class="inactive"
                        href="roles.php" <?php
                    } ?>>
                        <div class="icon-container">
                            <i class="fa-solid fa-user-gear fa-2x"></i>
                        </div>
                        Roles
                    </a>
                </li>
            </ul>
        </div> <?php
    }



?>