<?php
session_start();
include('includes.php');
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = '../images/local/';
$updateError = "";
$successMessage = "";

// Handle form submission for updating bike details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bike_no'])) {
    $bikeNo = $_POST['bike_no'];
    
    // Only update fields that are provided
    $updates = array();
    $types = "";
    $params = array();
    
    if (!empty($_POST['bike_class'])) {
        $updates[] = "bike_class = ?";
        $types .= "s";
        $params[] = $_POST['bike_class'];
    }
    
    if (!empty($_POST['price'])) {
        $updates[] = "price = ?";
        $types .= "d";
        $params[] = $_POST['price'];
    }

    // Handle image upload if provided
    if (!empty($_FILES['bike_img']['name'])) {
        $targetFile = $uploadDir . basename($_FILES['bike_img']['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        $validTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $validTypes)) {
            if (move_uploaded_file($_FILES['bike_img']['tmp_name'], $targetFile)) {
                $updates[] = "bike_img = ?";
                $types .= "s";
                $params[] = $targetFile;
            } else {
                $updateError = "Sorry, there was an error uploading your file.";
            }
        } else {
            $updateError = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    if (!empty($updates)) {
        $types .= "s"; // for the WHERE clause
        $params[] = $bikeNo;
        
        $sql = "UPDATE bike SET " . implode(", ", $updates) . " WHERE bike_no = ?";
        $stmt = $conn->prepare($sql);
        
        // Create array of references for bind_param
        $bindParams = array($types);
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
        
        if ($stmt->execute()) {
            echo "<script>alert('Changes saved successfully!'); window.location.href = 'admin_manage_bikes.php';</script>";
        } else {
            $updateError = "Error updating bike: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $bikeNo = $_GET['delete_id'];
    
    // Check if bike is booked
    $checkStmt = $conn->prepare("SELECT booking_status FROM bike WHERE bike_no = ?");
    $checkStmt->bind_param("s", $bikeNo);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $bike = $result->fetch_assoc();
    
    if ($bike['booking_status'] == 1) {
        echo "<script>alert('Cannot delete booked bikes!'); window.location.href = 'admin_manage_bikes.php';</script>";
    } else {
        $deleteStmt = $conn->prepare("DELETE FROM bike WHERE bike_no = ?");
        $deleteStmt->bind_param("s", $bikeNo);
        
        if ($deleteStmt->execute()) {
            echo "<script>alert('Bike deleted successfully!'); window.location.href = 'admin_manage_bikes.php';</script>";
        } else {
            echo "<script>alert('Error deleting bike!'); window.location.href = 'admin_manage_bikes.php';</script>";
        }
        $deleteStmt->close();
    }
    $checkStmt->close();
}

// Fetch all bikes
$result = $conn->query("SELECT * FROM bike");
$bikes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bikes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        button {
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 5px;
            width: auto !important;
            min-width: 80px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .form-container {
            display: none;
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group input[readonly] {
            background-color: #f5f5f5;
        }
        input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .success {
            color: green;
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Bikes</h2>

        <?php if (!empty($successMessage)): ?>
            <p class="success"><?php echo $successMessage; ?></p>
        <?php endif; ?>

        <?php if (!empty($updateError)): ?>
            <p class="error"><?php echo $updateError; ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Bike Number</th>
                    <th>Bike Name</th>
                    <th>Bike Class</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Booking Status</th>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bikes as $bike): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bike['bike_no']); ?></td>
                        <td><?php echo htmlspecialchars($bike['bike_name']); ?></td>
                        <td><?php echo htmlspecialchars($bike['bike_class']); ?></td>
                        <td><?php echo htmlspecialchars($bike['brand']); ?></td>
                        <td><?php echo htmlspecialchars($bike['price']); ?></td>
                        <td><?php echo $bike['booking_status'] ? 'Booked' : 'Available'; ?></td>
                        <td><img src="<?php echo htmlspecialchars($bike['bike_img']); ?>" alt="Bike Image" width="100"></td>
                        <td>
                            <button type="button" onclick="showEditForm('<?php echo $bike['bike_no']; ?>', '<?php echo $bike['booking_status']; ?>', '<?php echo htmlspecialchars($bike['bike_name']); ?>', '<?php echo htmlspecialchars($bike['brand']); ?>', '<?php echo htmlspecialchars($bike['bike_class']); ?>', '<?php echo htmlspecialchars($bike['price']); ?>')">Edit</button>
                            <button type="button" onclick="deleteBike('<?php echo $bike['bike_no']; ?>', '<?php echo $bike['booking_status']; ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="editForm" class="form-container">
            <h3>Edit Bike Details</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="bike_no" id="bike_no">
                
                <div class="form-group">
                    <label for="bike_name">Bike Name:</label>
                    <input type="text" name="bike_name" id="bike_name" readonly>
                </div>
                
                <div class="form-group">
                    <label for="brand">Brand:</label>
                    <input type="text" name="brand" id="brand" readonly>
                </div>
                
                <div class="form-group">
                    <label for="bike_class">Bike Class:</label>
                    <input type="text" name="bike_class" id="bike_class">
                </div>
                
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" step="0.01" name="price" id="price">
                </div>
                
                <div class="form-group">
                    <label for="bike_img">Bike Image:</label>
                    <input type="file" name="bike_img" id="bike_img" accept="image/*">
                </div>
                
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
    function showEditForm(bikeNo, bookingStatus, bikeName, brand, bikeClass, price) {
        if (bookingStatus === '1') {
            alert('Changes cannot be made on booked bikes');
            return;
        }
        
        const formContainer = document.getElementById('editForm');
        
        // Set values in the form
        document.getElementById('bike_no').value = bikeNo;
        document.getElementById('bike_name').value = bikeName;
        document.getElementById('brand').value = brand;
        document.getElementById('bike_class').value = bikeClass;
        document.getElementById('price').value = price;
        
        // Show the form
        formContainer.style.display = 'block';
        
        // Scroll to the form
        formContainer.scrollIntoView({ behavior: 'smooth' });
    }

    function deleteBike(bikeNo, bookingStatus) {
        if (bookingStatus === '1') {
            alert('Changes cannot be made on booked bikes');
            return;
        }
        
        if (confirm('Confirm to delete item?')) {
            window.location.href = 'admin_manage_bikes.php?delete_id=' + bikeNo;
        }
    }
    </script>
</body>
</html>