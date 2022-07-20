<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }
    
    require_once "../Controllers/Database.php";

    $db = new Database();

    $db->query("SELECT * FROM brands ORDER BY name");
    $db->execute();

    require_once "../Templates/header_view.php";
    setTitle("Brands");
?> 
<div class="my-container"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("brands");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Brands</h3>
            </div>
        </div>

        <div class="brand_grid fs-inter"> <?php
            while ($row = $db->fetch()) { ?>
                <div class="boxes">
                    <div class="content position-relative p-1 pb-3">
                        <h5 class="mb-0"><?= $row["abbreviation"] ?></h5>
                        <p class="fs-a"><?= $row["name"] ?></p>
                        <div class="position-absolute bottom-0">
                            <a href="<?= $row["fb_link"] ?>" target="<?= $row["fb_link"] ?>"><i class="fa-brands fa-facebook"></i></a>
                            <a href="<?= $row["insta_link"] ?>" target="<?= $row["insta_link"] ?>"><i class="fa-brands fa-instagram"></i></a>
                            <a href="<?= $row["twitter_link"] ?>" target="<?= $row["twitter_link"] ?>"><i class="fa-brands fa-twitter"></i></a>
                            <a href="<?= $row["web_link"] ?>" target="<?= $row["web_link"] ?>"><i class="fa-solid fa-globe"></i></a>
                        </div>
                    </div>
                    <div class="logo">
                        <a href="<?= $row["web_link"] ?>" target="<?= $row["web_link"] ?>">
                            <img class="img-fluid" src="<?= $row["image"] ?>" alt=""
                                onerror="this.src='../Assets/img/no_image_found.jpeg';">
                        </a>
                    </div>
                </div> <?php
            } ?>
        </div>
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>