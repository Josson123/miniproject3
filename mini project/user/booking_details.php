<?php


$conn = new mysqli("localhost", "root", "", "bikerental");

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if booking_id is set in the URL
if (!isset($_GET['booking_id'])) {
    echo "<script>alert('Booking ID is missing.');</script>";
    echo "<script>window.location.href='profile.php';</script>";
    exit();
}

$booking_id = $_GET['booking_id']; // Corrected to use booking_id

// Fetch booking details from the database
$sql = "SELECT booking_no, user_name, pickup_date, dropoff_date, bike_no, booking_date FROM booking WHERE booking_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('No booking found with this ID.');</script>";
    echo "<script>window.location.href='profile.php';</script>";
    exit();
}

$booking = $result->fetch_assoc();

// Fetch bike details based on bike_no
$bike_details = getBikeDetails($booking['bike_no'], $conn);

// Function to fetch bike details based on bike_no
function getBikeDetails($bike_no, $conn) {
    $bike_sql = "SELECT bike_name, bike_class, bike_img, price FROM bike WHERE bike_no = ?";
    $bike_stmt = $conn->prepare($bike_sql);
    $bike_stmt->bind_param("i", $bike_no);
    $bike_stmt->execute();
    return $bike_stmt->get_result()->fetch_assoc();
}

// HTML and PHP for booking details page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Styles specific to booking_details.php */
        body {
            background-color: #343a40;
            color: #ffffff;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background-color: #495057;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
        }
        h1 {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }
        .image-container {
            flex: 1;
            max-width: 35%;
            margin-right: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .image-container img {
            width: 100%;
            height: auto;
            display: block;
        }
        .details {
            flex: 2;
            min-width: 60%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ffffff;
        }
        th {
            background-color: #6c757d;
        }
        .btn-back {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: #007bff;
            color: #ffffff;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><b>Booking Details</b></h1>

        <div class="image-container">
            <img src="<?php echo htmlspecialchars($bike_details['bike_img']); ?>" alt="Bike Image">
        </div>

        <div class="details">
            <table>
                <tbody>
                    <tr>
                        <th>Booking ID:</th>
                        <td><?php echo htmlspecialchars($booking['booking_no']); ?></td>
                    </tr>
                    <tr>
                        <th>User Name:</th>
                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Pickup Date:</th>
                        <td><?php echo htmlspecialchars($booking['pickup_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Dropoff Date:</th>
                        <td><?php echo htmlspecialchars($booking['dropoff_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Bike No:</th>
                        <td><?php echo htmlspecialchars($booking['bike_no']); ?></td>
                    </tr>
                    <tr>
                        <th>Bike Name:</th>
                        <td><?php echo htmlspecialchars($bike_details['bike_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Bike Class:</th>
                        <td><?php echo htmlspecialchars($bike_details['bike_class']); ?></td>
                    </tr>
                    <tr>
                        <th>Price:</th>
                        <td><?php echo htmlspecialchars($bike_details['price']); ?></td>
                    </tr>
                    <tr>
                        <th>Booking Date:</th>
                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                    </tr>
                </tbody>
            </table>
                 <div class="text-center">
                   <a href="profile.php" class="btn-back">Back to Profile</a>
                </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

