<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for form fields
$pickup_date = isset($_GET['pickup_date']) ? $_GET['pickup_date'] : '';
$dropoff_date = isset($_GET['dropoff_date']) ? $_GET['dropoff_date'] : '';
$bike_class = isset($_GET['bike_class']) ? $_GET['bike_class'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent a Bike - RevRides Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="home_style.css">
    <style>
        /* Dark background for the entire page */
        body {
            background-color: #333;
            color: #fff;
            margin-top: 60px; /* Adjust this margin to the height of the navbar */
        }

        /* Navbar Styles */
        nav {
            position: fixed; /* Fix the navbar at the top */
            top: 0; /* Align to the top */
            left: 0; /* Align to the left */
            right: 0; /* Align to the right */
            background-color: #000; /* Navbar background color */
            z-index: 1000; /* Ensure the navbar is above other elements */
            height: 60px; /* Set a fixed height for the navbar */
        }

        .menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px; /* Add padding for the navbar */
            height: 100%; /* Ensure the menu takes the full height of the navbar */
        }

        .menu a {
            color: white; /* Navbar link color */
            text-decoration: none; /* Remove underline from links */
            padding: 10px; /* Add padding to links */
        }

        /* Cards Section Background */
        #bikes-list {
            background-color: #000;
            padding: 20px;
            border-radius: 10px;
        }

    </style>
</head>
<body>

     <!-- Navbar Section -->
     <nav>
        <div class="menu">
            <div class="logo"><a href="home.php">RevRides Rental</a></div>
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
</body>
</html>