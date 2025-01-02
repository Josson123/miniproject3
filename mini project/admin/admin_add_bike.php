<?php
session_start();
include('includes.php');
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = '../images/local/';
$uploadError = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bikeNumber = $_POST['bike_number'];
    $bikeName = $_POST['bike_name'];
    $bikeClass = $_POST['bike_class'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
   

    // Handling the file upload
    if (!empty($_FILES['bike_img']['name'])) {
        $targetFile = $uploadDir . basename($_FILES['bike_img']['name']);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate file type
        $validTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $validTypes)) {
            if (move_uploaded_file($_FILES['bike_img']['tmp_name'], $targetFile)) {
                // Insert into the database
                $stmt = $conn->prepare("INSERT INTO bike (bike_no, bike_name, bike_class,  bike_img, brand, price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssd", $bikeNumber, $bikeName, $bikeClass, $targetFile, $brand, $price);

                if ($stmt->execute()) {
                    echo "<script>
                            alert('Bike added successfully!!');
                            window.location.href = 'admin.php';
                          </script>";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            color: #555;
        }
        input, button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        button {
            background-color: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Insert a New Bike</h2>

        <?php if (!empty($uploadError)): ?>
            <p class="error"><?php echo $uploadError; ?></p>
        <?php endif; ?>

        <form action="admin_add_bike.php" method="POST" enctype="multipart/form-data">
            <label for="bike_number">Bike Number:</label>
            <input type="text" name="bike_number" id="bike_number" placeholder="Enter bike number" required>

            <label for="bike_class">Bike Name:</label>
            <input type="text" name="bike_name" id="bike_name" placeholder="Enter bike name" required>

            <label for="bike_class">Bike Class:</label>
            <input type="text" name="bike_class" id="bike_class" placeholder="Enter bike class" required>

            <label for="brand">Brand:</label>
            <input type="text" name="brand" id="brand" placeholder="Enter bike brand" required>

            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" id="price" placeholder="Enter price" required>

            

            <label for="bike_img">Bike Image:</label>
            <input type="file" name="bike_img" id="bike_img" accept="image/*" required>

            <button type="submit">Insert Bike</button>
        </form>
    </div>
</body>
</html>
