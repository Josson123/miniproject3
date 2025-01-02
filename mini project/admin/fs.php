<?php
session_start();
include('includes.php');
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Get current date and time
$currentDateTime = date('Y-m-d H:i:s'); // Format: YYYY-MM-DD HH:MM:SS

$conn = new mysqli("localhost", "root", "", "bikerental");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch relevant statistics (total bikes, total bookings, bikes available, bookings today)
$totalBikesQuery = "SELECT COUNT(*) AS total_bikes FROM bike";
$totalBikesResult = $conn->query($totalBikesQuery);
$totalBikes = $totalBikesResult->fetch_assoc()['total_bikes'];

$totalBookingsQuery = "SELECT COUNT(*) AS total_bookings FROM booking";
$totalBookingsResult = $conn->query($totalBookingsQuery);
$totalBookings = $totalBookingsResult->fetch_assoc()['total_bookings'];

// Bikes available
$bikesAvailableQuery = "SELECT COUNT(*) AS bikes_available FROM bike WHERE booking_status = '1'";
$bikesAvailableResult = $conn->query($bikesAvailableQuery);
$bikesAvailable = $bikesAvailableResult->fetch_assoc()['bikes_available'];

// Bookings today
$bookingsTodayQuery = "SELECT COUNT(*) AS bookings_today FROM booking WHERE DATE(booking_date) = CURDATE()";
$bookingsTodayResult = $conn->query($bookingsTodayQuery);
$bookingsToday = $bookingsTodayResult->fetch_assoc()['bookings_today'];

// Handle OTP generation
if(isset($_POST['generate_otp'])) {
    $booking_no = $_POST['booking_no'];
    $otp = mt_rand(100000, 999999);
    
    $update_otp = "UPDATE booking SET otp = ? WHERE booking_no = ?";
    $stmt = $conn->prepare($update_otp);
    $stmt->bind_param("is", $otp, $booking_no);
    $stmt->execute();
}

// Han
if(isset($_POST['confirm_pickup'])) {
    $booking_no = $_POST['booking_no'];
    $user_name = $_POST['user_name'];
    $dropoff_date = $_POST['dropoff_date'];
    
    $insert_pickup = "INSERT INTO picked_up_bikes (booking_no, user_name, dropoff_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_pickup);
    $stmt->bind_param("sss", $booking_no, $user_name, $dropoff_date);
    
    if($stmt->execute()) {
        echo "<script>
            alert('Pickup successful!');
            document.getElementById('row-$booking_no').style.display = 'none';
        </script>";
    }
}

// Handle return confirmation
if(isset($_POST['confirm_return'])) {
    $booking_no = $_POST['booking_no'];
    $update_return = "UPDATE picked_up_bikes SET returned = 1 WHERE booking_no = ?";
    $stmt = $conn->prepare($update_return);
    $stmt->bind_param("s", $booking_no);
    $stmt->execute();
}

// Fetch today's pickups
$pickup_query = "SELECT b.booking_no, b.pickup_date, b.user_name, b.otp, bk.bike_name,b.dropoff_date 
                 FROM booking b INNER JOIN bike bk ON b.bike_no = bk.bike_no
                 WHERE DATE(b.pickup_date) = CURDATE()";
$pickup_result = $conn->query($pickup_query);
if ($pickup_result === false) {
    error_log('Error in pickup query: ' . $conn->error);
    echo "<div class='alert alert-danger'>Error fetching today's pickups. Please check the logs.</div>";
} else {
    error_log('Pickup query returned ' . $pickup_result->num_rows . ' rows.');
}


// Fetch today's drop-offs (example for reference)
$dropoff_query = "SELECT b.booking_no, b.dropoff_date, b.user_name, bk.bike_name 
                  FROM booking b INNER JOIN bike bk ON b.bike_no = bk.bike_no
                  WHERE DATE(b.dropoff_date) = CURDATE()";
$dropoff_result = $conn->query($dropoff_query)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Include Bootstrap for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-card {
            border-radius: 10px;
            margin: 10px 0;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .top-right {
            position: absolute;
            top: 25px;
            right: 30px;
            font-size: 14px;
            color:white;
        }

        .admin-header {
            background-color: #343a40;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 10px;
        }
        .admin-actions {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <h1>Welcome, Admin: <?php echo $_SESSION['username']; ?>!</h1>
        <div class="top-right">
        <?php echo date('l, F j, Y h:i A'); ?>
        </div>

    </div>
            <div class="col-md-9">
                <h2>Dashboard Overview</h2>
                <div class="row">
                    <div class="col-md-3 dashboard-card bg-primary text-white">
                        <h4>Total Bikes</h4>
                        <p><?php echo $totalBikes; ?></p>
                    </div>
                    <div class="col-md-3 dashboard-card bg-success text-white">
                        <h4>Total Bookings</h4>
                        <p><?php echo $totalBookings; ?></p>
                    </div>
                    <div class="col-md-3 dashboard-card bg-warning text-white">
                        <h4>Bikes Available</h4>
                        <p><?php echo $bikesAvailable; ?></p>
                    </div>
                    <div class="col-md-3 dashboard-card bg-danger text-white">
                        <h4>Bookings Today</h4>
                        <p><?php echo $bookingsToday; ?></p>
                    </div>
                </div>
            </div>    

<!-- Add after the dashboard overview cards -->
<div class="col-md-12 mt-4">
    <h3>Today's Pickups</h3>
    <?php if($pickup_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Booking No</th>
                        <th>Pickup Date</th>
                        <th>Bike Name</th>
                        <th>User Name</th>
                        <th>Dropoff date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $pickup_result->fetch_assoc()): ?>
                        <tr id="pickup-row-<?php echo $row['booking_no']; ?>">
    <td><?php echo $row['booking_no']; ?></td>
    <td><?php echo $row['pickup_date']; ?></td>
    <td><?php echo $row['bike_name']; ?></td>
    <td><?php echo $row['user_name']; ?></td>
    <td><?php echo $row['dropoff_date']; ?></td>
    <td>
    <tr id="row-<?php echo $row['booking_no']; ?>">
    <td><?php echo $row['booking_no']; ?></td>
    <td><?php echo $row['pickup_date']; ?></td>
    <td><?php echo $row['bike_name']; ?></td>
    <td><?php echo $row['user_name']; ?></td>
    <td><?php echo $row['dropoff_date']; ?></td>
    <td>
        <?php if(!$row['otp']): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="booking_no" value="<?php echo $row['booking_no']; ?>">
                <button type="submit" name="generate_otp" class="btn btn-primary">Get OTP</button>
            </form>
        <?php else: ?>
            <div>OTP: <?php echo $row['otp']; ?></div>
            <form method="POST" class="d-inline">
                <input type="hidden" name="booking_no" value="<?php echo $row['booking_no']; ?>">
                <input type="hidden" name="user_name" value="<?php echo $row['user_name']; ?>">
                <input type="hidden" name="dropoff_date" value="<?php echo $row['dropoff_date']; ?>">
                <button type="submit" name="confirm_pickup" class="btn btn-success">Confirm Pickup</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No Pickups Today</div>
    <?php endif; ?>
</div>

<div class="col-md-12 mt-4">
    <h3>Today's Dropoffs</h3>
    <?php if($dropoff_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Booking No</th>
                        <th>Dropoff Date</th>
                        <th>Bike Name</th>
                        <th>User Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $dropoff_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['booking_no']; ?></td>
                            <td><?php echo $row['dropoff_date']; ?></td>
                            <td><?php echo $row['bike_name']; ?></td>
                            <td><?php echo $row['user_name']; ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="booking_no" value="<?php echo $row['booking_no']; ?>">
                                    <button type="submit" name="confirm_return" class="btn btn-success">Confirm Return</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No Dropoffs Today</div>
    <?php endif; ?>
</div>

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    document.addEventListener('DOMContentLoaded', function() {
    const pickupForms = document.querySelectorAll('.pickup-form');
    
    pickupForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const bookingNo = this.querySelector('[name="booking_no"]').value;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Bike picked up successfully!');
                    // Hide the row
                    const row = document.getElementById('pickup-row-' + bookingNo);
                    if (row) {
                        row.style.display = 'none';
                    }
                    
                    // Check if there are any visible rows left
                    const visibleRows = document.querySelectorAll('tr[id^="pickup-row-"]:not([style*="display: none"])');
                    if (visibleRows.length === 0) {
                        // If no visible rows, show "No Pickups Today" message
                        const table = document.querySelector('.table-responsive');
                        if (table) {
                            table.innerHTML = '<div class="alert alert-info">No Pickups Today</div>';
                        }
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to confirm pickup'));
                }
            })
            .catch(error => {
                alert('Error confirming pickup: ' + error);
            });
        });
    });
});
</script>
</body>
</html>