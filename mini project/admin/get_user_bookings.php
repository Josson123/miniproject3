<?php
// get_user_bookings.php
session_start();
include('includes.php');

$conn = new mysqli("localhost", "root", "", "bikerental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['username'])) {
    $username = $_GET['username'];
    
    $stmt = $conn->prepare("SELECT b.*, bk.bike_name 
                           FROM booking b 
                           JOIN bike bk ON b.bike_no = bk.bike_no 
                           WHERE b.user_name = ? 
                           ORDER BY b.booking_date DESC");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table class='table table-bordered'>
                <thead>
                    <tr>
                        <th>Booking No</th>
                        <th>Bike</th>
                        <th>Pickup Date</th>
                        <th>Dropoff Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            $status = "Pending";
            if ($row['pickup_status'] == 1) {
                $status = "Picked Up";
            }
            
            echo "<tr>
                    <td>" . htmlspecialchars($row['booking_no']) . "</td>
                    <td>" . htmlspecialchars($row['bike_name']) . "</td>
                    <td>" . htmlspecialchars($row['pickup_date']) . "</td>
                    <td>" . htmlspecialchars($row['dropoff_date']) . "</td>
                    <td>" . $status . "</td>
                  </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p>No booking records found for this user.</p>";
    }
    
    $stmt->close();
} else {
    echo "<p>Invalid request</p>";
}

$conn->close();
?>