<?php
  session_start();
  require_once "../Controllers/Functions.php";
  require_once "../Templates/header_view.php";
  require_once "../Controllers/Database.php";
  require_once "../Controllers/Date.php";
?>

<div class="my-container">
  <!--NAV BAR-->
  <?php
        include_once "nav_side_bar.php";
        navSideBar("dashboard");
    ?>



  <div class="main-section p-4">
    <div class="aside">
      <?php include_once "profile_settings.php"; ?>
    </div>


    <!--MAIN SECTION-->


    <div class="control-header">

      <div class="intern-header">
        <h3 class="text-center p-2">Intern Attendance</h3>
      </div>
      <div class="control-container">

        <!--SEARCH BUTTON/TEXT-->
        <div class="input-group mb-3">
          <button class="btn btn-outline-secondary " type="button" id="">Search</button>
          <input type="text" class="form-control" placeholder="" aria-label="Example text with button addon"
            aria-describedby="button-addon1">
        </div>
        <!--DEPARTMENT DROPDOWN-->
        <div class="dropdown-container">
        <div class="dropdown align-center">
          <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1"
            data-bs-toggle="dropdown" aria-expanded="false">
            Departments
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
            <li><a class="dropdown-item" href="#">Information Technology</a></li>
            <li><a class="dropdown-item" href="#">Human Resource</a></li>
            <li><a class="dropdown-item" href="#">Marketing</a></li>
            <li><a class="dropdown-item" href="#">Accounting</a></li>
            <li><a class="dropdown-item" href="#">Business Development</a></li>
          </ul>
        </div>
        <!--SORTING DROPDOWN-->
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1"
            data-bs-toggle="dropdown" aria-expanded="false">
            Sort by:
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
            <li><a class="dropdown-item" href="#">A-Z</a></li>
            <li><a class="dropdown-item" href="#">Z-A</a></li>
            <li><a class="dropdown-item" href="#">Oldest</a></li>
            <li><a class="dropdown-item" href="#">Newest</a></li>
            <li><a class="dropdown-item" href="#">Near Offboarding Date</a></li>
          </ul>
        </div>
        </div>
      
      </div>
      <div class="row mb-3">
        <div class="summary">
          <!--INTERN CARD 1-->
          <a class="clickable-card" href="profile.php">
            <div class="summary-boxes">
              <div class="top">
                <img src="../Assets/img/profile_imgs/default_female.png">
              </div>
              <div class="summary-total">
                <h5>Juan Dela Cruz</h5>
              </div>
              <div class="left"></div>
              <div class="right"></div>

              <div class="bottom">
                <div class="subheader d-inline-flex p-1">
                  <p class="me-2">Time In
                  <p class="me-2">8:00 AM</p>
                  </p>
                  <p class="me-2">Time Out
                  <p class="me-2">5:00 PM</p>
                  </p>
                </div>
              </div>
              <div class="bottom">

              </div>
            </div>
          </a>
          <!--INTERN CARD 2-->
          <a class="clickable-card" href="profile.php">
            <div class="summary-boxes">
              <div class="top">
                <img src="../Assets/img/profile_imgs/default_female.png">
              </div>
              <div class="summary-total">
                <h5>Pedro Penduko</h5>
              </div>
              <div class="left"></div>
              <div class="right"></div>

              <div class="bottom">
                <div class="subheader d-inline-flex p-1">
                  <p class="me-2">Time In
                  <p class="me-2">8:00 AM</p>
                  </p>
                  <p class="me-2">Time Out
                  <p class="me-2">5:00 PM</p>
                  </p>
                </div>
              </div>
              <div class="bottom">

              </div>
            </div>
          </a>
          <!--INTERN CARD 3-->
          <a class="clickable-card" href="profile.php">
            <div class="summary-boxes">
              <div class="top">
                <img src="../Assets/img/profile_imgs/default_female.png">
              </div>
              <div class="summary-total">
                <h5>Juan Dela Cruz</h5>
              </div>
              <div class="left"></div>
              <div class="right"></div>

              <div class="bottom">
                <div class="subheader d-inline-flex p-1">
                  <p class="me-2">Time In
                  <p class="me-2">8:00 AM</p>
                  </p>
                  <p class="me-2">Time Out
                  <p class="me-2">5:00 PM</p>
                  </p>
                </div>
              </div>
              <div class="bottom">

              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
    <?php
    require_once "../Templates/footer.php";
?>
  </div>