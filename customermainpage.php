<?php
session_start();
require_once 'db.php';

// Fetch brands for dropdown
$brands = [];
$sql = "SELECT BrandName, BrandImage FROM `03_brand` ORDER BY BrandName ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Fetch categories for dropdown
$categories = [];
$categoryQuery = "SELECT CategoryName FROM `04_category` ORDER BY CategoryName ASC";
$categoryResult = $conn->query($categoryQuery);
if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($cat = $categoryResult->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Get customer information
$customerName = 'Guest';
$profileLink = 'customer_login.php';
if (isset($_SESSION['customer_id'])) {
    $CustomerID = $_SESSION['customer_id'];
    $stmt = $conn->prepare("SELECT Cust_First_Name FROM 02_customer WHERE CustomerID = ?");
    $stmt->bind_param("i", $CustomerID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $customerName = 'Hello, ' . htmlspecialchars($row['Cust_First_Name']);
        $profileLink = 'customer_profile.php';
    }
}

// Fetch popular products
$popular_products = [];
$popular_query = "SELECT * FROM `05_product` ORDER BY Product_Stock_Quantity DESC LIMIT 6";
$popular_result = mysqli_query($conn, $popular_query);
if ($popular_result) {
    while ($row = mysqli_fetch_assoc($popular_result)) {
        $popular_products[] = $row;
    }
}

// Fetch recommended products
$like_products = [];
$customer_id = $_SESSION['customer_id'] ?? null;

if ($customer_id) {
    $customer_id = (int)$customer_id;

    // 查询最近浏览的产品（最多6个）
    $like_query = "
        SELECT p.*
        FROM (
            SELECT ProductID, MAX(Viewed_At) AS LastViewed
            FROM `15_view_history`
            WHERE CustomerID = $customer_id
            GROUP BY ProductID
        ) AS vh
        JOIN `05_product` p ON vh.ProductID = p.ProductID
        ORDER BY vh.LastViewed DESC
        LIMIT 6
    ";

    $like_result = mysqli_query($conn, $like_query);

    if ($like_result && mysqli_num_rows($like_result) > 0) {
        while ($row = mysqli_fetch_assoc($like_result)) {
            $like_products[] = $row;
        }
    }
}

// 如果登录但没浏览记录，或查询失败，则随机推荐
if (empty($like_products)) {
    $fallback_query = "SELECT * FROM `05_product` ORDER BY RAND() LIMIT 6";
    $fallback_result = mysqli_query($conn, $fallback_query);
    if ($fallback_result) {
        while ($row = mysqli_fetch_assoc($fallback_result)) {
            $like_products[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>watch | Landing, Ecommerce &amp; Business Templatee</title>


    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicons/favicon.ico">
    <link rel="manifest" href="assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link href="assets/css/theme.css" rel="stylesheet" />

  </head>


  <body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">  
      <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3 d-block" data-navbar-on-scroll="data-navbar-on-scroll">
        <div class="container"><a class="navbar-brand d-inline-flex" href="customermainpage.php"><img src="assets/img/Screenshot 2025-03-20 113245.png"></a>
          <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
          <div class="collapse navbar-collapse border-top border-lg-0 mt-4 mt-lg-0" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item px-2"><a class="nav-link fw-bold active" aria-current="page" href="#collections">WATCHES</a></li>
              
              <li class="nav-item px-2 dropdown brands-dropdown position-relative">
                <a class="nav-link fw-bold" href="#" id="brandDropdown">BRANDS</a>
                <div class="brand-dropdown-content">
                  <?php if (!empty($brands)): ?>
                    <?php foreach ($brands as $brand): ?>
                      <a href="customer_products.php?brand=<?= urlencode($brand['BrandName']) ?>" class="brand-item">
                        <img src="uploads/<?= htmlspecialchars($brand['BrandImage']) ?>" alt="<?= htmlspecialchars($brand['BrandName']) ?>" style="width: 50px; height: auto;">
                        <span><?= htmlspecialchars($brand['BrandName']) ?></span>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style='color: #ccc;'>No brands found.</p>
                  <?php endif; ?>
                </div>
              </li>

              <li class="nav-item px-2 dropdown brands-dropdown position-relative">
                <a class="nav-link fw-bold" href="#" id="categoriesDropdown">CATEGORIES</a>
                <div class="brand-dropdown-content">
                  <a href="customer_products.php" class="brand-item">
                    <span>All Watches</span>
                  </a>
                  <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                      <a href="customer_products.php?category=<?= urlencode($category['CategoryName']) ?>" class="brand-item">
                        <span><?= htmlspecialchars($category['CategoryName']) ?></span>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p style='color: #ccc; padding-left: 10px;'>No categories found.</p>
                  <?php endif; ?>
                </div>
              </li>


              <li class="nav-item px-2"><a class="nav-link fw-bold" href="cart.php"><img src="img/Cart_icon.png" alt="Cart" style="width:24px; height:24px;"></a></li>
              
              <?php if (isset($_SESSION['customer_id'])): ?>
              <li class="nav-item px-2">
                <a class="nav-link fw-bold" href="view_history.php">VIEW HISTORY</a>
              </li>
              <?php endif; ?>

              <li class="nav-item px-2 d-flex align-items-center">
                <a class="nav-link fw-bold d-flex align-items-center" href="<?= $profileLink ?>">
                  <img src="img/user_icon.png" alt="profile" style="width:24px; height:24px;" class="me-1">
                  <span class="text-white"><?= $customerName ?></span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <section class="py-0" id="header">
        <div class="bg-holder" style="background-image:url(assets/img/gallery/header-bg.png);background-position:right top;background-size:contain;">
        </div>
        <!--/.bg-holder-->

        <div class="container">
          <div class="row align-items-center min-vh-75 min-vh-xl-100">
            <div class="col-md-8 col-lg-6 text-md-start text-center">
              <h1 class="display-1 lh-sm text-uppercase text-light">Welcome to  <br class="d-none d-xxl-block" /> TIGO</h1>
            </div>
          </div>
        </div>
      </section>
      <section class="bg-black py-8 pt-0" id="store">
        <div class="bg-holder" style="background-image:url(assets/img/gallery/store-bg.png);background-position:left bottom;background-size:contain;">
        </div>
        <!--/.bg-holder-->

        <div class="container-lg">
          <div class="row flex-center">
            <div class="col-6 order-md-0 text-center text-md-start"></div>
            <div class="col-sm-10 col-md-6 col-lg-6 text-center text-md-start">
              <div class="col-4 position-relative ms-auto py-5"><a class="carousel-control-prev carousel-icon z-index-2" href="#carouselExampleFade" role="button" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></a><a class="carousel-control-next carousel-icon z-index-2" href="#carouselExampleFade" role="button" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></a></div>
              <div class="carousel slide carousel-fade" id="carouselExampleFade" data-bs-ride="carousel">
                <div class="carousel-inner">
                  <div class="carousel-item active">
                    <div class="row h-100">
                      <div class="col-12">
                        <h5 class="fs-3 fs-lg-5 lh-sm text-uppercase">Our store</h5>
                        <p class="my-4 pe-xl-5">Memphis clinched a spot in the play-in tournament with the victory, but the fight for seeding continues. The race for the No. 8 spot in the West -- and the safety net of having to win just one of two games to make the playoffs -- could come down to the regular seasons final day, when Memphis and Golden State meet. The good thing for the Grizzlies is they dont have to leave home until that matchup as they have games against Dallas and two against Sacramento before the finale.</p>
                      </div>
                    </div>
                  </div>
                  <div class="carousel-item">
                    <div class="row h-100">
                      <div class="col-12">
                        <h5 class="fs-3 fs-lg-5 lh-sm text-uppercase">Our store</h5>
                        <p class="my-4 pe-xl-5">Memphis clinched a spot in the play-in tournament with the victory, but the fight for seeding continues. The race for the No. 8 spot in the West -- and the safety net of having to win just one of two games to make the playoffs -- could come down to the regular seasons final day, when Memphis and Golden State meet. The good thing for the Grizzlies is they dont have to leave home until that matchup as they have games against Dallas and two against Sacramento before the finale.</p>
                      </div>
                    </div>
                  </div>
                  <div class="carousel-item">
                    <div class="row h-100">
                      <div class="col-12">
                        <h5 class="fs-3 fs-lg-5 lh-sm text-uppercase">Our store</h5>
                        <p class="my-4 pe-xl-5">Memphis clinched a spot in the play-in tournament with the victory, but the fight for seeding continues. The race for the No. 8 spot in the West -- and the safety net of having to win just one of two games to make the playoffs -- could come down to the regular seasons final day, when Memphis and Golden State meet. The good thing for the Grizzlies is they dont have to leave home until that matchup as they have games against Dallas and two against Sacramento before the finale.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section class="py-0 pb-6" id="collections">
      <div class="container">
        <div class="row h-100">
          <div class="col-lg-7 mt-7">
            <h5 class="fs-3 fs-lg-5 lh-sm mb-0 text-uppercase">Collections</h5>
          </div>
          <div class="col-12">
            <nav>
              <div class="nav nav-tabs watch-tabs mb-4 justify-content-end" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-popular-tab" data-bs-toggle="tab" data-bs-target="#nav-popular" type="button" role="tab" aria-selected="true">POPULAR</button>
                <button class="nav-link" id="nav-like-tab" data-bs-toggle="tab" data-bs-target="#nav-like" type="button" role="tab" aria-selected="false">YOU MAY ALSO LIKE</button>
              </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
              <!-- Popular Products -->
              <div class="tab-pane fade show active" id="nav-popular" role="tabpanel">
                <div class="row">
                  <?php foreach ($popular_products as $product): ?>
                    <div class="col-sm-6 col-md-4 mb-3">
                      <div class="card bg-black text-white p-6 pb-8">
                        <div style="background-color: white; padding: 10px; border-radius: 8px;">
                          <img class="card-img" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                        </div>
                        <div class="card-img-overlay bg-dark-gradient d-flex flex-column-reverse align-items-center text-center">
                          <h6 class="text-primary">RM<?= number_format($product['Product_Price'], 2) ?></h6>
                          <h4 class="text-light mb-2"><?= htmlspecialchars($product['ProductName']) ?></h4>
                        </div>
                        <a class="stretched-link" href="product_details.php?id=<?= $product['ProductID'] ?>"></a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Recommended Products -->
              <div class="tab-pane fade" id="nav-like" role="tabpanel">
                <div class="row">
                  <?php foreach ($like_products as $product): ?>
                    <div class="col-sm-6 col-md-4 mb-3">
                      <div class="card bg-black text-white p-6 pb-8">
                        <div style="background-color: white; padding: 10px; border-radius: 8px;">
                          <img class="card-img" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']) ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                        </div>
                        <div class="card-img-overlay bg-dark-gradient d-flex flex-column-reverse align-items-center">
                          <h6 class="text-primary">RM<?= number_format($product['Product_Price'], 2) ?></h6>
                          <h4 class="text-light mb-2"><?= htmlspecialchars($product['ProductName']) ?></h4>
                        </div>
                        <a class="stretched-link" href="product_details.php?id=<?= $product['ProductID'] ?>"></a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>



      <!-- ============================================-->
      <!-- <section> begin ============================-->
      <section class="py-6 bg-dark">

        <div class="container">
          <div class="row">
            <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0 d-flex flex-center"><img src="assets/img/gallery/rado.png" alt="brands" /></div>
            <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0 d-flex flex-center"><img src="assets/img/gallery/swatch.png" alt="brands" /></div>
            <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0 d-flex flex-center"><img src="assets/img/gallery/omega-1.png" alt="brands" /></div>
            <div class="col-sm-6 col-lg-3 mb-4 mb-lg-0 d-flex flex-center"><img src="assets/img/gallery/zenith.png" alt="brands" /></div>
          </div>
        </div>
        <!-- end of .container-->

      </section>
      <!-- <section> close ============================-->
      <!-- ============================================-->




      <!-- ============================================-->
      <!-- <section> begin ============================-->
      <section id="testimonial">

        <div class="container">
          <div class="row">
            <div class="col-lg-7 mx-auto text-center my-5">
              <h5 class="fs-3 fs-lg-5 lh-sm mb-0 text-uppercase">what customers are saying</h5>
            </div>
          </div>
          <div class="row flex-center h-100">
            <div class="col-xl-9">
              <div class="carousel slide" id="carouselTestimonials" data-bs-ride="carousel">
                <div class="carousel-inner">
                  <div class="carousel-item active" data-bs-interval="10000">
                    <div class="row h-100 justify-content-center">
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">Love this Watch</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">I first time ordered used TIGO and I received the watch in good condition.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">Beautiful</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">my 4th time purchase from TIGO.nice, affordable & arrogant</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="carousel-item" data-bs-interval="5000">
                    <div class="row h-100 justify-content-center">
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">Excellent</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">Response to chat fast✅
                              Good customer service✅
                              Affordable price✅</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">Excellent</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">I bought this set as a gift. The delivery was impressive.Thank you so much</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="carousel-item">
                    <div class="row h-100 justify-content-center">
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">1000% satisfied</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">I was waiting to purchase this watch for a long time since it's kinda esthetic to wear this watch</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow card-span p-3 bg-black">
                          <div class="card-body">
                            <div class="d-flex align-items-center">
                              <div class="flex-1 ms-4">
                                <h6 class="fs-lg-1 mb-1 text-uppercase">Good</h6>
                              </div>
                            </div>
                            <p class="mb-0 mt-4 fw-light lh-lg">they're selling watches in a good price with so many discounts sometimes worth buying here then buying from the official brand store</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row mt-5 flex-center">
                  <div class="col-auto position-relative z-index-2">
                    <ol class="carousel-indicators">
                      <li class="active mx-2" data-bs-target="#carouselTestimonials" data-bs-slide-to="0"></li>
                      <li class="mx-2" data-bs-target="#carouselTestimonials" data-bs-slide-to="1"></li>
                      <li class="mx-2" data-bs-target="#carouselTestimonials" data-bs-slide-to="2"></li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- end of .container-->

      </section>
      <!-- <section> close ============================-->
      <!-- ============================================-->




      <!-- ============================================-->
      <!-- <section> begin ============================-->
      <section class="py-0 pt-7" id="contact">

        <div class="container">
          <div class="row">

            <div class="col-6 col-sm-4 col-xl-3 mb-3">
              <h5 class="lh-lg fw-bold text-light">MENU</h5>
              <ul class="list-unstyled mb-md-4 mb-lg-0">
                <li class="lh-lg"><a class="text-800 text-decoration-none text-uppercase fw-bold" href="Shipping_info.php">Shipping Info</a></li>
                <li class="lh-lg"><a class="text-800 text-decoration-none text-uppercase fw-bold" href="Payment_info.html">Payment info</a></li>
              </ul>
            </div>

            <div class="col-6 col-sm-4 col-xl-3 mb-3">
              <h4 class="lh-lg fw-bold text-light">WATCH</h4>
            </div>

            <div class="col-6 col-sm-4 col-xl-3 mb-3">
              <h4 class="lh-lg fw-bold text-light">WATCH</h4>
            </div>

            <div class="col-6 col-sm-4 col-xl-3 mb-3">
              <h5 class="mb-5"></h5>
              <ul class="list-unstyled mb-md-4 mb-lg-0">
                <li class="lh-lg"><a class="text-800 text-decoration-none text-uppercase fw-bold" href="About_Us.html">About Us</a></li>
                <li class="lh-lg"><a class="text-800 text-decoration-none text-uppercase fw-bold" href="Contact_Us.php">Contact Us</a></li>
                <li class="lh-lg"><a class="text-800 text-decoration-none text-uppercase fw-bold" href="customer_profile.php">my account</a></li>
              </ul>
            </div>

            <div class="col-12 col-xl-3">
              <h5 class="lh-lg fw-bold text-light text-uppercase">Signup For our Latest News</h5>
              <div class="row input-group-icon mb-5">
                <div class="col-12">
                  <form class="row row-cols-lg-auto g-0 align-items-center">
                    <div class="col-8 col-lg-9">
                      <label class="visually-hidden" for="colFormLabel">Username</label>
                      <div class="input-group">
                        <input class="rounded-end-0 form-control" id="colFormLabel" type="email" placeholder="email address" />
                      </div>
                    </div>
                    <div class="col-4 col-lg-3">
                      <button class="btn btn-primary rounded-start-0" type="submit">Submit</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <div class="border-bottom border-700"></div>
          <div class="row flex-center my-3">
            <div class="col-md-6 order-1 order-md-0">
                <svg class="bi bi-suit-heart-fill" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="#EB6453" viewBox="0 0 16 16">
                </svg>&nbsp;&nbsp;<a class="text-500" href="https://themewagon.com/" target="_blank"> </a>
              </p>
            </div>
            <div class="col-md-6">
              <div class="text-center text-md-end"><a href="#!"><span class="me-4" data-feather="facebook"></span></a><a href="#!"> <span class="me-4" data-feather="instagram"></span></a><a href="#!"> <span class="me-4" data-feather="youtube"></span></a><a href="#!"> <span class="me-4" data-feather="twitter"></span></a></div>
            </div>
          </div>
        </div>
        <!-- end of .container-->

      </section>
      <!-- <section> close ============================-->
      <!-- ============================================-->


    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->




    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="vendors/@popperjs/popper.min.js"></script>
    <script src="vendors/bootstrap/bootstrap.min.js"></script>
    <script src="vendors/is/is.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>
    <script src="vendors/feather-icons/feather.min.js"></script>
    <script>
      feather.replace();
    </script>
    <script src="assets/js/theme.js"></script>
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
    // BRAND dropdown
    const brandDropdown = document.querySelector('.brands-dropdown');
    const brandContent = brandDropdown.querySelector('.brand-dropdown-content');

    brandDropdown.addEventListener('mouseenter', () => {
      brandContent.style.display = 'block';
    });
    brandDropdown.addEventListener('mouseleave', () => {
      brandContent.style.display = 'none';
    });

    // CATEGORY dropdown (you can give it a separate class if you want)
    const categoryDropdown = document.querySelector('#categoriesDropdown').parentElement;
    const categoryContent = categoryDropdown.querySelector('.brand-dropdown-content');

    categoryDropdown.addEventListener('mouseenter', () => {
      categoryContent.style.display = 'block';
    });
    categoryDropdown.addEventListener('mouseleave', () => {
      categoryContent.style.display = 'none';
    });
  });

    </script>

<style>
.brands-dropdown {
  position: relative;
}

.brands-dropdown .brand-dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  background-color: #222;
  border-radius: 10px;
  padding: 15px;
  z-index: 999;
  flex-wrap: wrap;         /* 允许多行 */
  gap: 20px;               /* 品牌之间间距 */
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  min-width: 400px;        /* ✅ 设置一个合适的宽度，让它横向显示 */
  max-width: 800px;        /* ✅ 可选：最大宽度 */
}

.brands-dropdown:hover .brand-dropdown-content {
  display: flex !important; /* ✅ 鼠标悬停时才显示 加 !important 防止被内联覆盖 */
}

.brand-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #fff;
  text-decoration: none;
  width: 100px;             /* ✅ 每个品牌项宽度相同，便于横向排布 */
}

.brand-item img {
  width: 60px;
  height: 60px;
  background: #fff;
  padding: 5px;
  border-radius: 8px;
  object-fit: contain;
}

</style>


    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;700&amp;display=swap" rel="stylesheet">
  </body>

</html>