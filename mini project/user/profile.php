<?php
// Start session at the very beginning
session_start();

// Include database connection
include("includes.php");

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login if not logged in
    echo "<script>alert('Please login to see profile');</script>";
    echo "<script>window.location.href='home.php';</script>";  
    exit();
}

// Create database connection
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Return Success Alert
if (isset($_GET['return_success'])) {
    echo "<script>alert('Your ride is successfully returned. Hope you have enjoyed the ride!');</script>";
}

// Fetch the logged-in user's information from the database
$username = $_SESSION['username'];
$sql = "SELECT user_name, email, phone_no FROM user WHERE user_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Robust user fetch with error handling
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Destroy session and redirect if user not found
    session_destroy();
    echo "<script>alert('User  account not found. Please login again.');</script>";
    echo "<script>window.location.href='home.php';</script>";
    exit();
}

// Handle Booking Cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_no = $_POST['booking_no'];
    
    // Begin a transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        //```php
        // Check if cancellation is allowed (must be a day before pickup)
        $check_cancel_sql = "SELECT pickup_date, bike_no FROM booking WHERE booking_no = ? AND user_name = ?";
        $check_cancel_stmt = $conn->prepare($check_cancel_sql);
        $check_cancel_stmt->bind_param("ss", $booking_no, $username);
        $check_cancel_stmt->execute();
        $check_cancel_result = $check_cancel_stmt->get_result();
        
        if ($check_cancel_result->num_rows === 0) {
            throw new Exception("Booking not found or does not belong to you");
        }
        
        $booking_details = $check_cancel_result->fetch_assoc();
        
        $pickup_date = new DateTime($booking_details['pickup_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($pickup_date);
        
        if ($interval->days > 1) {
            // Update bike booking status to 0 in the bike table
            $update_bike_status_sql = "UPDATE bike SET booking_status = 0 WHERE bike_no = ?";
            $update_bike_status_stmt = $conn->prepare($update_bike_status_sql);
            $update_bike_status_stmt->bind_param("s", $booking_details['bike_no']);
            
            if (!$update_bike_status_stmt->execute()) {
                throw new Exception("Error updating bike booking status");
            }
            
            // Cancel the booking
            $cancel_sql = "DELETE FROM booking WHERE booking_no = ? AND user_name = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param("ss", $booking_no, $username);
            
            if (!$cancel_stmt->execute()) {
                throw new Exception("Error cancelling booking");
            }
            
            // Commit the transaction
            $conn->commit();
            
            echo "<script>alert('Booking successfully cancelled.');</script>";
        } else {
            echo "<script>alert('Booking can only be cancelled a day before the pickup date.');</script>";
        }
    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $conn->rollback();
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Fetch current bookings with pickup status (excluding returned ones)
$current_bookings_sql = "SELECT b.booking_no, b.pickup_date, b.dropoff_date, b.bike_no, b.otp, b.pickup_status 
                         FROM booking b 
                         LEFT JOIN picked_up_bikes p ON b.booking_no = p.booking_no 
                         WHERE b.user_name = ? 
                         AND (p.returned IS NULL OR p.returned = 0)
                         AND b.dropoff_date >= CURDATE()";
$current_bookings_stmt = $conn->prepare($current_bookings_sql);
$current_bookings_stmt->bind_param("s", $username);
$current_bookings_stmt->execute();
$current_bookings_result = $current_bookings_stmt->get_result();

// Fetch past bookings (including returned ones)
$past_bookings_sql = "SELECT b.booking_no, b.pickup_date, b.dropoff_date, b.bike_no 
                      FROM booking b 
                      LEFT JOIN picked_up_bikes p ON b.booking_no = p.booking_no 
                      WHERE b.user_name = ? 
                      AND p.returned = 1
                      ORDER BY b.dropoff_date DESC LIMIT 3";
$past_bookings_stmt = $conn->prepare($past_bookings_sql);
$past_bookings_stmt->bind_param("s", $username);
$past_bookings_stmt->execute();
$past_bookings_result = $past_bookings_stmt->get_result();

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes'])) {
    $new_email = trim($_POST['email']);
    $new_phone_no = trim($_POST['phone_no']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email and phone number
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } else {
        // Begin transaction for profile update
        $conn->begin_transaction();

        try {
            // Update basic user details
            $update_sql = "UPDATE user SET email = ?, phone_no = ? WHERE user_name = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sss", $new_email, $new_phone_no, $username);

            if (!$update_stmt->execute()) {
                throw new Exception("Error updating profile details");
            }

            // Handle password change if new password is provided
            if (!empty($new_password)) {
                // Verify current password first
                $password_check_sql = "SELECT password FROM user WHERE user_name = ?";
                $password_check_stmt = $conn->prepare($password_check_sql);
                $password_check_stmt->bind_param("s", $username);
                $password_check_stmt->execute();
                $password_result = $password_check_stmt->get_result();
                $password_row = $password_result->fetch_assoc();

                // Validate current password
                if (!password_verify($current_password, $password_row['password'])) {
                    throw new Exception("Current password is incorrect");
                }

                // Validate new password
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match");
                }

                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_sql = "UPDATE user SET password = ? WHERE user_name = ?";
                $update_password_stmt = $conn->prepare($update_password_sql);
                $update_password_stmt->bind_param("ss", $hashed_password, $username);

                if (!$update_password_stmt->execute()) {
                    throw new Exception("Error updating password");
                }
            }

            // Commit transaction
            $conn->commit();

            // Refresh user data
            $user['email'] = $new_email;
            $user['phone_no'] = $new_phone_no;

            echo "<script>alert('Profile updated successfully');</script>";
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    }
}

// Fetch past bookings that have been returned
$past_bookings_sql = "SELECT b.booking_no, b.pickup_date, b.dropoff_date, b.bike_no 
                      FROM booking b 
                      INNER JOIN picked_up_bikes p ON b.booking_no = p.booking_no 
                      WHERE b.user_name = ? 
                      AND p.returned = 1
                      ORDER BY b.dropoff_date DESC";
$past_bookings_stmt = $conn->prepare($past_bookings_sql);
$past_bookings_stmt->bind_param("s", $username);
$past_bookings_stmt->execute();
$past_bookings_result = $past_bookings_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
    body, html {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      height: 100%;
      font-family: Arial, sans-serif;
    }

    .bg-img{
        background: url("../images/home/profile.jpg") repeat center center/cover;
        width: 100%;
        height: 100vh;
        position: relative;
        z-index: 1;
    }

    .gradient-bg {
      background: linear-gradient(to right, #e0c397, #8b5e3c, #e0c397);
      width: 100%;
      min-height: 100vh;
      position: relative;
    }

    .content-container {
      position: relative;
      padding-top: 100px;
      z-index: 2;
    }

    .profile-section, .current-bookings, .past-bookings {
      padding: 20px;
      border-radius: 5px;
      margin-top: 20px;
      margin-bottom: 20px;
      background-color: rgba(249, 249, 249, 0.9);
    }

    .current-bookings {
      background-color: rgba(231, 243, 254, 0.9);
      border: 1px solid #2196F3;
    }

    .past-bookings {
      background-color: rgba(252, 228, 236, 0.9);
      border: 1px solid #E91E63;
    }
    .btn-cancel {
      background-color: #dc3545;
      color: white;
    }
    .otp-display {
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
      padding: 10px;
      margin-top: 10px;
      border-radius: 5px;
    }
    .Changanacherry{
      color: #721c24; /* Blue color for the link */
    text-decoration: none; /* Remove underline */
    font-weight: bold; /* Bold text for emphasis */
    
    color:rgb(215, 9, 98); /* Darker shade of blue on hover */ 
    }
    </style>
</head>
<body>
<div class=bg-img>
  <div class="gradient-bg ```php
">
    <div class="container content-container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <!-- Profile Section -->
          <div class="profile-section">
            <h5 class="text-center">My Profile</h5>
            <table class="table table-borderless mt-3">
              <tbody>
                <tr>
                  <th>Username:</th>
                  <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                </tr>
                <tr>
                  <th>Email:</th>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                </tr>
                <tr>
                  <th>Phone Number:</th>
                  <td><?php echo htmlspecialchars($user['phone_no']); ?></td>
                </tr>
              </tbody>
            </table>
            <div class="text-center mt-5">
              <button id="editBtn" class="btn btn-primary">Edit Profile</button>
            </div>
            <form id="editForm" action="profile.php" method="POST" style="display:none;" class="mt-4">
              <div class="form-group mb-3">
                <label for="email">Email:</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
              <div class="form-group mb-3">
                <label for="phone_no">Phone Number:</label>
                <input type="text" class="form-control" name="phone_no" value="<?php echo htmlspecialchars($user['phone_no']); ?>" required>
              </div>
              <div class="form-group mb-3">
                <label for="current_password">Current Password:</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="current_password">
                  <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">Show</button>
                </div>
              </div>
              <div class="form-group mb-3">
                <label for="new_password">New Password:</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="new_password">
                  <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">Show</button>
                </div>
              </div>
              <div class="form-group mb-3">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" class="form-control" name="confirm_password">
              </div>
              <button type="submit" name="save_changes" class="btn btn-success">Save Changes</button>
            </form>
          </div>

          <!-- Current Bookings Section -->
          <div class="current-bookings">
            <h5>Current Bookings</h5>
            <div class="alert alert-warning">
              Booking cancellation must be done a day before the pickup date.<br>
              <div class="Changanacherry">
                Pickup And Dropoff Center: 
              <a href="https://maps.app.goo.gl/9RSZiG67G4YEMk7y5" target="_blank" rel="noopener">
              Changanacherry</a>
              </div>   
            </div>
            <table class="table">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Pickup Date</th>
                  <th>Dropoff Date</th>
                  <th>Bike No</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $current_date = new DateTime();
                while ($booking = $current_bookings_result->fetch_assoc()): 
                    $pickup_date = new DateTime($booking['pickup_date']);
                    $is_pickup_date = $current_date->format('Y-m-d') === $pickup_date->format('Y-m-d');
                    $is_before_pickup = $current_date < $pickup_date;
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($booking['booking_no']); ?></td>
                    <td><?php echo htmlspecialchars($booking['pickup_date']); ?></td>
                    <td><?php echo htmlspecialchars($booking['dropoff_date']); ?></td>
                    <td><?php echo htmlspecialchars($booking['bike_no']); ?></td>
                    <td>
                      <a href="booking_details.php?booking_id=<?php echo htmlspecialchars($booking['booking_no']); ?>" 
                      class="btn btn-info me-2">View Details</a>
                      
                      <?php if ($is_before_pickup && !$booking['pickup_status']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                          <input type="hidden" name="booking_no" value="<?php echo htmlspecialchars($booking['booking_no']); ?>">
                          <button type="submit ```php
" name="cancel_booking" class="btn btn-cancel">Cancel Booking</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($is_pickup_date && !empty($booking['otp']) && !$booking['pickup_status']): ?>
                        <div class="mt-2">
                          <button class="btn btn-primary" onclick="toggleOTP()">See OTP</button>
                          <div id="otpDisplay" class="otp-display" style="display:none;">
                            OTP: <?php echo htmlspecialchars($booking['otp']); ?>
                          </div>
                        </div>
                      <?php elseif ($booking['pickup_status']): ?>
                        <div class="alert alert-success mt-2">
                          Pickup successful
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <!-- Past Bookings Section -->
          <div class="past-bookings">
            <h5>Past Bookings</h5>
            <table class="table">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Pickup Date</th>
                  <th>Dropoff Date</th>
                  <th>Bike No</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($past_booking = $past_bookings_result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($past_booking['booking_no']); ?></td>
                    <td><?php echo htmlspecialchars($past_booking['pickup_date']); ?></td>
                    <td><?php echo htmlspecialchars($past_booking['dropoff_date']); ?></td>
                    <td><?php echo htmlspecialchars($past_booking['bike_no']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById("editBtn").addEventListener("click", function() {
    document.getElementById("editForm").style.display = "block";
  });

  document.getElementById("toggleCurrentPassword").addEventListener("click", function() {
    const passwordInput = document.querySelector('input[name="current_password"]');
    passwordInput.type = passwordInput.type === "password" ? "text" : "password";
    this.textContent = passwordInput.type === "password" ? "Show" : "Hide";
  });

  document.getElementById("toggleNewPassword").addEventListener("click", function() {
    const passwordInput = document.querySelector('input[name="new_password"]');
    passwordInput.type = passwordInput.type === "password" ? "text" : "password";
    this.textContent = passwordInput.type === "password" ? "Show" : "Hide";
  });

  function toggleOTP() {
    const otpDisplay = document.getElementById('otpDisplay');
    otpDisplay.style.display = otpDisplay.style.display === 'none' ? 'block' : 'none';
  }

  // Check for missed pickup on page load
  window.onload = function() {
    <?php
    // Check for missed pickups
    $missed_pickup_sql = "SELECT booking_no FROM booking WHERE user_name = ? AND pickup_date < CURDATE() AND dropoff_date >= CURDATE()";
    $missed_pickup_stmt = $conn->prepare($missed_pickup_sql);
    $missed_pickup_stmt->bind_param("s", $username);
    $missed_pickup_stmt->execute();
    $missed_pickup_result = $missed_pickup_stmt->get_result();

    if ($missed_pickup_result->num_rows > 0) {
        echo "window.location.href = 'home.php?compensation_alert=true';";
    }
    ?>
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>