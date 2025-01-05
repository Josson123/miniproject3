<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camping Gear Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="home_style.css">
    <style>
        body {
            background-color: #333;
            color: #fff;
            scroll-behavior: smooth;
            padding-top: 70px;
        }
        nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: #1b1b1b;
        }
        .menu ul {
            display: flex;
            list-style: none;
            gap: 20px;
        }
        .menu ul li a {
            color: white;
            text-decoration: none;
        }
        .img {
            background-size: cover !important;
            background-position: center center !important;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .center {
            z-index: 2;
            position: relative;
        }
        .full-width-section {
            background-color: #000;
            color: white;
            padding: 50px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar Section -->
    <nav>
        <div class="menu">
            <div class="logo"><a href="#" style="color: white; text-decoration: none;">Camping Gear</a></div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="">Profile</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="img" style="background: url('images/camping-1.jpg') no-repeat center center/cover;">
        <div class="center">
            <h1 class="title text-white">Camping Gear Rental</h1>
            <h3 class="sub_title">Find Your Perfect Camping Gear</h3>
            <div class="btns">
                <button id="rent-gear-btn" class="btn btn-primary">Rent Gear</button>
            </div>
        </div>
    </div>

    <!-- Rental Form Section -->
    <section class="bg-image-section" id="rental-section" style="background: url('images/camping-2.jpg') no-repeat center center/cover;">
        <main class="container mt-5">
            <section class="rental-form">
                <h2 class="text-white text-center">Rent Camping Gear</h2>
                <center>
                <form id="gear-form">
                    <div class="form-row">
                        <div class="form-group mb-3">
                            <label for="pickup-date" class="form-label text-white">Pickup Date:</label>
                            <input type="date" id="pickup-date" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="dropoff-date" class="form-label text-white">Drop-off Date:</label>
                            <input type="date" id="dropoff-date" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="gear-type" class="form-label text-white">Gear Type:</label>
                            <select id="gear-type" class="form-select" required>
                                <option value="all">All Gear</option>
                                <option value="tents">Tents</option>
                                <option value="sleeping-bags">Sleeping Bags</option>
                                <option value="cooking">Cooking Equipment</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="check-availability-btn">Check Availability</button>
                </form>
                </center>
            </section>
        </main>
    </section>

    <!-- Available Gear Section -->
    <section id="gear-section">
        <div id="gear-list" class="row mx-0 mt-0 mb-0 bg-dark p-4"></div>
    </section>

    <!-- Contact Us Section -->
    <section class="full-width-section">
        <div class="contact-us container text-center">
            <h5>Contact Us</h5>
            <p>Head Office: Wilderness Base<br>
            Pickup Point: Campground Center<br>
            Enquiry: +91 8547236599<br>
            Email: campinggear@rental.com</p>
            <div class="social-media">
                <p>Follow us on: 
                    <img src="images/facebook-logo.png" alt="Facebook" width="30">
                    <img src="images/instagram-logo.png" alt="Instagram" width="30">
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>©Camping Gear Rental. All Rights Reserved</p>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function smoothScrollToSection(sectionId) {
                const section = document.getElementById(sectionId);
                if (section) {
                    section.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }

            $('#rent-gear-btn').on('click', function() {
                smoothScrollToSection('rental-section');
            });

            $('#check-availability-btn').on('click', function() {
                const pickupDate = $('#pickup-date').val();
                const dropoffDate = $('#dropoff-date').val();
                const gearType = $('#gear-type').val();

                if (!pickupDate || !dropoffDate) {
                    alert('Please insert both Pickup and Drop-off dates.');
                    return;
                }

                // Placeholder for AJAX call to check gear availability
                $.ajax({
                    url: 'check_gear_availability.php',
                    type: 'POST',
                    data: {
                        pickup_date: pickupDate,
                        dropoff_date: dropoffDate,
                        gear_type: gearType
                    },
                    success: function(response) {
                        $('#gear-list').html(response).hide().fadeIn(500);
                        smoothScrollToSection('gear-section');
                    },
                    error: function() {
                        alert('Error fetching available gear');
                    }
                });
            });
        });
    </script>
</body>
</html>