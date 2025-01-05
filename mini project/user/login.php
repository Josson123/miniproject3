<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bikerental");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Modified query to check suspension status
    $stmt = $conn->prepare("SELECT user_name, email, phone_no, password, suspended FROM user WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['suspended'] == 1) {
            $loginError = "Your account has been suspended. Please contact support.";
        } else if (password_verify($password, $row['password'])) {
            // Store user details in session
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $row['user_name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['phone_no'] = $row['phone_no'];

            header('Location: home.php');
            exit();
        } else {
            $loginError = "Incorrect password.";
        }
    } else {
        $loginError = "Username not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>RevRides Login </title>
     <link rel="stylesheet" href="login_style.css">
     <style>
        nav {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          backdrop-filter: blur(10px);
          z-index: 1000;
          height: 60px;
        }

        a.top-left-link {
         position: absolute !important;
         top: 10px !important;
         left: 10px !important;
         color: #110687 !important;
         text-decoration: none !important;
         z-index: 1000;
         font-size: 35px;
         font-weight: 600;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
     </style>   
</head>
<body>  
        <nav>
            <a href="home.php" class="top-left-link">RevRides Rental</a>
        </nav>
       <div class="wrapper">
            <?php if (!empty($loginError)): ?>
                <p class="error-message"><?php echo $loginError; ?></p>
            <?php endif; ?>
            
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <div class="input-field">
                    <input type="text" name="username" required>
                    <label>Username</label>
                </div>
                <div class="input-field">
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <button type="submit">Log In</button>
            </form>
            
            <div class="register">
                <p>Don't have an account? <a href="New Profile.php">Register</a></p>
            </div>
       </div>
</body>
</html>