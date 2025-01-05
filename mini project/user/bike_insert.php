<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = 'images\local';
$uploadError = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bikeNumber = $_POST['bike_number'];
    $bikeName = $_POST['bike_name'];
    $bikeClass = $_POST['bike_class'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $bookingStatus = $_POST['booking_status'];

    // Handling the file upload
    if (!empty($_FILES['bike_img']['name'])) {
        $targetFile = $uploadDir . basename($_FILES['bike_img']['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate file type
        $validTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $validTypes)) {
            if (move_uploaded_file($_FILES['bike_img']['tmp_name'], $targetFile)) {
                // Insert into the database
                $stmt = $conn->prepare("INSERT INTO bike (bike_no,bike_name, bike_class, booking_status, bike_img, brand, price) VALUES (?,?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssissd", $bikeNumber, $bikeName, $bikeClass, $bookingStatus, $targetFile, $brand, $price);

                if ($stmt->execute()) {
                    echo "<p>New bike inserted successfully!</p>";
                } else {
                    echo "<p>Error inserting bike: " . $stmt->error . "</p>";
                }

                $stmt->close();
            } else {
                $uploadError = "Sorry, there was an error uploading your file.";
            }
        } else {
            $uploadError = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        $uploadError = "Please upload an image for the bike.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Bike</title>
</head>
<body>
    <h2>Insert a New Bike</h2>

    <?php if (!empty($uploadError)): ?>
        <p style="color:red;"><?php echo $uploadError; ?></p>
    <?php endif; ?>

    <form action="bike_insert.php" method="POST" enctype="multipart/form-data">
        <label for="bike_number">Bike Number:</label>
        <input type="text" name="bike_number" id="bike_number" required>
        <br>

        <label for="bike_class">Bike name:</label>
        <input type="text" name="bike_name" id="bike_name" required>
        <br>

        <label for="bike_class">Bike Class:</label>
        <input type="text" name="bike_class" id="bike_class" required>
        <br>

        <label for="brand">Brand:</label>
        <input type="text" name="brand" id="brand" required>
        <br>

        <label for="price">Price:</label>
        <input type="number" step="0.01" name="price" id="price" required>
        <br>

        <label for="booking_status">Booking Status (0 for available, 1 for booked):</label>
        <input type="number" name="booking_status" id="booking_status" required>
        <br>

        <label for="bike_img">Bike Image:</label>
        <input type="file" name="bike_img" id="bike_img" accept="image/*" required>
        <br>

        <button type="submit">Insert Bike</button>
    </form>
</body>
</html>
