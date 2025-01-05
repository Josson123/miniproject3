<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bike_no = $_POST['bike_no'];
    $pickup_date = $_POST['pickup_date'];
    $dropoff_date = $_POST['dropoff_date'];

    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        
        // Check active bookings count
        $booking_count_query = "SELECT COUNT(*) as active_bookings 
                              FROM booking 
                              WHERE user_name = ? 
                              AND booking_date >= CURDATE()";
        $stmt_count = $conn->prepare($booking_count_query);
        $stmt_count->bind_param("s", $username);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $booking_count = $result_count->fetch_assoc()['active_bookings'];

        // Check existing booking for dates
        $existing_booking_query = "SELECT COUNT(*) as existing_bookings 
                                 FROM booking 
                                 WHERE user_name = ?
                                 AND ((pickup_date BETWEEN ? AND ?) 
                                 OR (dropoff_date BETWEEN ? AND ?))";
        $stmt_existing = $conn->prepare($existing_booking_query);
        $stmt_existing->bind_param("sssss", $username, $pickup_date, $dropoff_date, $pickup_date, $dropoff_date);
        $stmt_existing->execute();
        $result_existing = $stmt_existing->get_result();
        $existing_booking_count = $result_existing->fetch_assoc()['existing_bookings'];

        if ($booking_count >= 2) {
            echo '<script>
                alert("Maximum limit of 2 active bookings reached.");
                window.location.href = "home.php";
                </script>';
            exit;
        }

        if ($existing_booking_count > 0) {
            echo '<script>
                alert("You already have a booking for the selected dates.");
                window.location.href = "home.php";
                </script>';
            exit;
        }
    }

    // Fetch bike details
    $query = "SELECT * FROM bike WHERE bike_no = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bike_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $bike = $result->fetch_assoc();

    // Calculate rental days and price
    $pickup = new DateTime($pickup_date);
    $dropoff = new DateTime($dropoff_date);
    $num_days = ($pickup == $dropoff) ? 1 : $pickup->diff($dropoff)->days + 1;
    $total_price = $num_days * $bike['price'];
}

if (isset($_POST['confirm_booking'])) {
    $username = $_SESSION['username'];
    $booking_date = date('Y-m-d');

    try {
        $conn->begin_transaction();

        // First verify that the user exists
        $user_check_query = "SELECT user_name FROM user WHERE user_name = ?";
        $stmt = $conn->prepare($user_check_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            throw new Exception("User not found. Please log in again.");
        }

        // Get current date in DDMMYY format
        $date_prefix = date('dmy');

        // Get the latest booking number for today
        $serial_query = "SELECT MAX(CAST(SUBSTRING(booking_no, 7) AS UNSIGNED)) as latest_serial 
                        FROM booking 
                        WHERE DATE(booking_date) = CURDATE()";
        $serial_result = $conn->query($serial_query);
        $serial_row = $serial_result->fetch_assoc();
        
        // If no bookings exist for today, start with 1, otherwise increment the latest
        $next_serial = ($serial_row['latest_serial'] === null) ? 1 : $serial_row['latest_serial'] + 1;
        
        // Pad the serial number to 2 digits
        $serial_number = str_pad($next_serial, 2, '0', STR_PAD_LEFT);
        
        // Create booking number (DDMMYY + serial)
        $booking_no = $date_prefix . $serial_number;

        // Also verify that the bike exists and is available
        $bike_check_query = "SELECT bike_no, booking_status FROM bike WHERE bike_no = ? AND booking_status = 0";
        $stmt = $conn->prepare($bike_check_query);
        $stmt->bind_param("i", $bike_no);
        $stmt->execute();
        $bike_result = $stmt->get_result();
        
        if ($bike_result->num_rows === 0) {
            throw new Exception("Bike is not available for booking.");
        }

        // Update bike status
        $update_query = "UPDATE bike SET booking_status = 1 WHERE bike_no = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $bike_no);
        if (!$stmt->execute()) {
            throw new Exception("Error updating bike status: " . $stmt->error);
        }

        // Create booking with booking number
        $insert_query = "INSERT INTO booking (booking_no, pickup_date, dropoff_date, bike_no, user_name, booking_date, booking_amt) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssissd", $booking_no, $pickup_date, $dropoff_date, $bike_no, $username, $booking_date, $total_price);
        if (!$stmt->execute()) {
            throw new Exception("Error creating booking: " . $stmt->error);
        }

        $conn->commit();
        header("Location: booking_success.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo '<script>
            alert("' . $e->getMessage() . '");
            window.location.href = "home.php";
            </script>';
        exit;
    }
}
// ... (rest of the code remains the same)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: rgba(0, 0, 0, 0.5);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .image-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        h1, h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .details {
            margin: 20px 0;
        }

        .details p {
            margin: 10px 0;
            font-size: 16px;
            line-height: 1.6;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .button.cancel {
            background-color: #dc3545;
        }

        .button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Booking Confirmation</h1>
    <div class="image-container">
        <img src="<?php echo htmlspecialchars($bike['bike_img']); ?>" alt="<?php echo htmlspecialchars($bike['bike_name']); ?>">
    </div>
    
    <div class="details">
        <h2><?php echo htmlspecialchars($bike['bike_name']); ?></h2>
        <p><strong>Bike Brand:</strong> <?php echo htmlspecialchars($bike['brand']); ?></p>
        <p><strong>Bike Class:</strong> <?php echo htmlspecialchars($bike['bike_class']); ?></p>
        <p><strong>Rent per day:</strong> $<?php echo htmlspecialchars($bike['price']); ?></p>
        <p><strong>Total Rent for <?php echo $num_days; ?> days:</strong> $<?php echo $total_price; ?></p>
    </div>

    <form method="POST">
        <input type="hidden" name="bike_no" value="<?php echo $bike_no; ?>">
        <input type="hidden" name="pickup_date" value="<?php echo $pickup_date; ?>">
        <input type="hidden" name="dropoff_date" value="<?php echo $dropoff_date; ?>">
        <input type="hidden" name="confirm_booking" value="1">

        <div class="button-container">
            <button type="submit" class="button">Confirm Booking</button>
            <button type="button" class="button cancel" onclick="window.location.href='home.php'">Go Back</button>
        </div>
    </form>
</div>

</body>
</html>