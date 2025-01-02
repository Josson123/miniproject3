<?php
session_start();
include('includes.php');
//include('includes.php'); // This will include your sidebar and other dependencies
// Check if admin is logged in, if not redirect to admin_login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "bikerental");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get bookings based on filters
function getBookings($conn, $dateFilter = '', $monthFilter = '', $yearFilter = '') {
    $query = "SELECT * FROM booking WHERE 1=1"; // Start with all records
    $params = [];
    $paramTypes = '';

    // Date filter
    if (!empty($dateFilter)) {
        $query .= " AND DATE(booking_date) = ?";
        $params[] = $dateFilter;
        $paramTypes .= 's';
    } 
    
    // Prepare the statement
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        // Bind parameters dynamically based on filter input
        $stmt->bind_param($paramTypes, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// Get today's date
$todayDate = date("Y-m-d");

// Initialize filter variables
$dateFilter = $monthFilter = $yearFilter = "";

// If the form is submitted for specific filters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['date'])) {
        $dateFilter = $_POST['date'];
    }
}

// Fetch bookings based on filters or show today's bookings by default
$bookings = getBookings($conn, $dateFilter ?: $todayDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings</title>
    <!-- Include Bootstrap for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main-content {
            margin-top: 20px;
        }
        .table {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <!--<div class="col-md-3">
               ?php include('includes.php'); ?>
            </div>-->

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <h1>View Bookings</h1>

                    <!-- Filter form -->
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label for="date" class="form-label">Filter by Date:</label>
                            <input type="date" id="date" name="date" class="form-control">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary mt-3">Filter</button>
                        </div>
                    </form>

                    <!-- Bookings Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Booking No</th>
                                <th>User Name</th>
                                <th>Bike ID</th>
                                <th>Booking Date</th>
                                <th>Pickup Date</th>
                                <th>Dropoff Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php while ($row = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['booking_no']; ?></td>
                                        <td><?php echo $row['user_name']; ?></td>
                                        <td><?php echo $row['bike_no']; ?></td>
                                        <td><?php echo $row['booking_date']; ?></td>
                                        <td><?php echo $row['pickup_date']; ?></td>
                                        <td><?php echo $row['dropoff_date']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No bookings found for the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
