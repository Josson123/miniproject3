<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $check_suspension = $conn->prepare("SELECT suspended FROM user WHERE user_name = ?");
    $check_suspension->bind_param("s", $username);
    $check_suspension->execute();
    $result = $check_suspension->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['suspended'] == 1) {
            // Clear session and redirect to login with message
            session_destroy();
            header("Location: login.php?error=account_suspended");
            exit();
        }
    }
    $check_suspension->close();
}

$pickup_date = isset($_GET['pickup_date']) ? $_GET['pickup_date'] : '';
$dropoff_date = isset($_GET['dropoff_date']) ? $_GET['dropoff_date'] : '';
$bike_class = isset($_GET['bike_class']) ? $_GET['bike_class'] : 'all';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RevRides Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <style href>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        ::selection {
            color: #000;
            background: #fff;
        }
        nav {
            position: fixed;
            background: #1b1b1b;
            width: 100%;
            padding: 10px 0;
            z-index: 12;
        }
        nav .menu {
            max-width: 1250px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        .menu .logo a {
            text-decoration: none;
            color: #fff;
            font-size: 35px;
            font-weight: 600;
        }
        .menu ul {
            display: inline-flex;
        }
        .menu ul li {
            list-style: none;
            margin-left: 7px;
        }
        .menu ul li:first-child {
            margin-left: 0px;
        }
        .menu ul li a {
            text-decoration: none;
            color: #fff;
            font-size: 18px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .menu ul li a:hover {
            background: #fff;
            color: black;
        }
        .img {
            background: url("../images/home/img.jpg") no-repeat;
            width: 100%;
            height: 100vh;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .img::before {
            content: "";
            position: absolute;
            height: 100%;
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
        }
        .center {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            padding: 0 20px;
            text-align: center;
            z-index: 1;
        }
        .center .title {
            color: #fff;
            font-size: 55px;
            font-weight: 600;
        }
        .center .sub_title {
            color: #fff;
            font-size: 52px;
            font-weight: 600;
        }
        .center .btns {
            margin-top: 20px;
        }
        .center .btns button {
            height: 55px;
            width: 170px;
            border-radius: 5px;
            border: none;
            margin: 0 10px;
            border: 2px solid white;
            font-size: 20px;
            font-weight: 500;
            padding: 0 10px;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
        }
        .center .btns button:first-child {
            color: #fff;
            background: none;
        }
        .btns button:first-child:hover {
            background: white;
            color: black;
        }
        .center .btns button:last-child {
            background: white;
            color: black;
        }

        /* Additional styles for bike rental features */
        #rental-section {
        background: url("../images/home/img2.jpg") no-repeat center center/cover;
        min-height: 800px;
        position: relative;
        padding: 50px 0;
        display: flex;  /* Add this */
        justify-content: center;
        align-items: center;
        }
  
      


        Form Container 
.rental-form {

  max-width: 700px;
  margin: auto;
  padding: 2rem;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  color: #fff;
  position: absolute;  /* Change from relative to absolute */
  top: 50%;           /* Add this */
  left: 50%;          /* Add this */
  transform: translate(-50%, -50%); /* Add this */
  text-align: center;
  position: relative;
  z-index: 2;
}

/* Form Title */
.rental-form h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 1.5rem;
}

/* Horizontal Row Layout */
.form-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Form Group Styling */
.form-group {
  flex: 1;
  min-width: 150px;
}

/* Labels */
.rental-form .form-label {
  font-weight: 500;
  color: #fff;
  margin-bottom: 0.5rem;
  display: block;
}

/* Inputs and Select Fields */
.rental-form .form-control {
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.8rem;
    border-radius: 5px;
    transition: background 0.3s ease;
}

/* Add new styles for the select element */
.rental-form select.form-control {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Style the options within the select */
.rental-form select.form-control option {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: white;
    padding: 10px;
}
/* Submit Button */
.rental-form .btn-primary {
  margin-top: 1.5rem;
  background-color: #ffc107;
  border: none;
  padding: 0.8rem 1.2rem;
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  border-radius: 5px;
  transition: background-color 0.3s ease;
}

.rental-form .btn-primary:hover {
  background-color: #e0a800;
  color: #fff;
}
        .rental-form {
            background: rgba(0, 0, 0, 0.7);
            
            padding: 2rem;
            border-radius: 10px;
            max-width: 700px;
            margin: 0 auto;
            color: #fff;
            position: relative;
            z-index: 2;
        }

        #bikes-list {
            background-color: #000;
            padding: 20px;
            margin: 0;
        }

        .contact-us {
            background: linear-gradient(to right, black, teal, yellow);
            color: #fff;
            padding: 30px;
            margin: 0;
        }

        footer {
            background: linear-gradient(to right, black, teal, yellow);
            color: #000;
            text-align: center;
            padding: 10px 0;
        }

        .welcome-banner {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            color: #fff;
            animation: fadeOut 3s forwards;
            animation-delay: 2s;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* Social media styling */
        .social-media {
        margin-top: 15px;
        }

        .social-media p {
         display: flex;
        align-items: center;
        font-size: 1.1rem;
        }

        .social-media img {
        width: 24px;
        height: 24px;
        margin-left: 10px;
        transition: transform 0.2s ease-in-out;
        }

        .social-media img:hover {
        transform: scale(1.2);
        }

    </style>
</head>
<body>
    <nav>
        <div class="menu">
            <div class="logo">
                <a href="home.php">RevRides Rental</a>
            </div>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <li><a href="logout.php" onclick="return confirmLogout()">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
        <div class="welcome-banner">
            Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </div>
    <?php endif; ?>

    <div class="img">
        <div class="center">
            <div class="title">RevRides Rental</div>
            <div class="sub_title">Find Your Perfect Ride</div>
            <div class="btns">
                <button id="rent-bike-btn">Rent a Bike</button>
            </div>
        </div>
    </div>
 
    <section id="rental-section">
    <div class="rental-form">
        <h2><center>Book your rides in just a click</center></h2>
        <form id="bike-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="pickup-date" class="form-label">Pickup Date:</label>
                    <input type="date" id="pickup-date" class="form-control" value="<?php echo htmlspecialchars($pickup_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="dropoff-date" class="form-label">Drop-off Date:</label>
                    <input type="date" id="dropoff-date" class="form-control" value="<?php echo htmlspecialchars($dropoff_date); ?>" required>
                </div>
                <div class="form-group">
                    <label for="bike-class" class="form-label">Bike Class:</label>
                    <select id="bike-class" class="form-control" required>
                        <option value="all" <?php echo $bike_class === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="standard" <?php echo $bike_class === 'standard' ? 'selected' : ''; ?>>Standard</option>
                        <option value="mountain" <?php echo $bike_class === 'mountain' ? 'selected' : ''; ?>>Mountain</option>
                        <option value="premium" <?php echo $bike_class === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-primary w-100 mt-4" id="check-availability-btn">Check Availability</button>
        </form>
    </div>
</section>

    <section id="bikes-section">
        <div id="bikes-list" class="row mx-0"></div>
    </section>

    <section class="full-width-section">
        <div class="contact-us">
            <h5>Contact Us</h5>
            <p>Head Office: Kottayam<br>
            Pickup and Dropoff Point: Changanacherry<br>
            For Enquiry: +91 8547236599<br>
            Email: revridesrental@gmail.com</p>
            <div class="social-media">
                <p>Follow us on:
                    <img src="../images/home/FB logo.png" alt="Facebook">
                    <img src="../images/home/instagram.png" alt="Instagram">
                </p>
            </div>
        </div>
    </section>

    <footer>
        <p>©RevRides Rental. All Rights Reserved</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#rent-bike-btn').on('click', function() {
                document.getElementById('rental-section').scrollIntoView({
                    behavior: 'smooth'
                });
            });

            $('#check-availability-btn').on('click', function() {
                const pickupDate = $('#pickup-date').val();
                const dropoffDate = $('#dropoff-date').val();
                const bikeClass = $('#bike-class').val();

                if (!pickupDate || !dropoffDate) {
                    alert('Please insert both Pickup and Drop-off dates.');
                    return;
                }

                $.ajax({
                    url: 'check_bike_availability.php',
                    type: 'POST',
                    data: {
                        pickup_date: pickupDate,
                        dropoff_date: dropoffDate,
                        bike_class: bikeClass
                    },
                    success: function(response) {
                        $('#bikes-list').html(response).hide().fadeIn(500);
                        document.getElementById('bikes-section').scrollIntoView({
                            behavior: 'smooth'
                        });
                    },
                    error: function() {
                        alert('Error fetching available bikes');
                    }
                });
            });

            window.confirmLogout = function() {
                alert("Logout successful");
                return true;
            };
        });
    </script>
</body>
</html>