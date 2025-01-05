<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$usernameTaken = false;
$emailTaken = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $number = $_POST['phoneno'];

    // Check if username or email already exists
    $query = "SELECT * FROM user WHERE user_name = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['user_name'] === $username) {
                $usernameTaken = true;
            }
            if ($row['email'] === $email) {
                $emailTaken = true;
            }
        }
    } else {
        // Encrypt password and insert into database
        $pass_encode = password_hash($password, PASSWORD_DEFAULT);
        $insert = "INSERT INTO user (user_name, email, password, phone_no) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($insert)) {
            $stmt->bind_param("ssss", $username, $email, $pass_encode, $number);
            if ($stmt->execute()) {
                header('Location: login.php');
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - RevRides Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login_style.css">
    <style>
         <style>
        nav {
          position: fixed; /* Fix the navbar at the top */
          top: 0; /* Align to the top */
          left: 0; /* Align to the left */
          right: 0; /* Align to the right */
          backdrop-filter: blur(10px);
          z-index: 1000; /* Ensure the navbar is above other elements */
          height: 60px; /* Set a fixed height for the navbar */
        }

        a.top-left-link {
         position: absolute !important;
         top: 10px !important;
         left: 10px !important;
          color: #110687 !important;
         
         text-decoration: none !important;
          z-index: 1000; /* Ensure it stays above other elements */
          text-decoration: none;
          text-decoration: none;
          font-size: 35px;
          font-weight: 600;
         }
    </style>    
</head>
<body>
    <main>
         <nav>
        <a href="home.php" class="top-left-link">RevRides Rental</a>
        </nav>
        <div class="wrapper">
            <h2>Sign Up</h2>
            <form action="New profile.php" method="POST" onsubmit="return validateForm()">
                
                <div class="input-field">
                    <input type="text" name="username" id="username" maxlength="20" required>
                    <label for="username">Username</label>
                    <?php if ($usernameTaken): ?>
                        <p class="error-message" id="username-error" style="color: red;">Username already taken</p>
                    <?php endif; ?>
                </div>
                
                <div class="input-field">
                    <input type="password" name="password" id="password" maxlength="10" required>
                    <label for="password">Password</label>
                </div>

                <div class="input-field">
                    <input type="text" name="phoneno" id="phoneno" pattern="\d{10}" required>
                    <label for="phoneno">Phone Number</label>
                    <p class="error-message" id="phone-error" style="color: red; display: none;">Phone no. must be of 10 digits</p>
                </div>
               
                <div class="input-field">
                    <input type="email" name="email" id="email" required>
                    <label for="email">Email</label>
                    <?php if ($emailTaken): ?>
                        <p class="error-message" id="email-error" style="color: red;">Email already taken</p>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="submit">Submit</button>
            </form>
            <div class="register">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </main>

    <!-- JavaScript to handle form validation and auto-hide error messages -->
    <script>
        // Hide error messages after a few seconds
        setTimeout(() => {
            const usernameError = document.getElementById('username-error');
            const emailError = document.getElementById('email-error');
            if (usernameError) usernameError.style.display = 'none';
            if (emailError) emailError.style.display = 'none';
        }, 750); // Hides after 0.75 seconds

        // Validate phone number in real-time
        document.getElementById('phoneno').addEventListener('input', function() {
            const phoneError = document.getElementById('phone-error');
            if (this.value.length !== 10) {
                phoneError.style.display = 'block';
            } else {
                phoneError.style.display = 'none';
            }
        });
    </script>
</body>
</html>
