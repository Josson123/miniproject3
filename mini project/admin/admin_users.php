<?php
session_start();
include('includes.php');

$conn = new mysqli("localhost", "root", "", "bikerental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user suspension
if (isset($_GET['suspend_user'])) {
    $username = $_GET['suspend_user'];
    
    // Add a suspended column to user table if it doesn't exist
    $conn->query("ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `suspended` TINYINT(1) DEFAULT 0");
    
    $stmt = $conn->prepare("UPDATE user SET suspended = 1 WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('User suspended successfully!'); window.location.href = 'admin_users.php';</script>";
    } else {
        echo "<script>alert('Error suspending user!'); window.location.href = 'admin_users.php';</script>";
    }
    $stmt->close();
}

// Handle user reactivation
if (isset($_GET['reactivate_user'])) {
    $username = $_GET['reactivate_user'];
    
    $stmt = $conn->prepare("UPDATE user SET suspended = 0 WHERE user_name = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('User reactivated successfully!'); window.location.href = 'admin_users.php';</script>";
    } else {
        echo "<script>alert('Error reactivating user!'); window.location.href = 'admin_users.php';</script>";
    }
    $stmt->close();
}

// Fetch all users
$result = $conn->query("SELECT * FROM user");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
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
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 2px;
        }
        .btn-view {
            background-color: #28a745;
        }
        .btn-suspend {
            background-color: #dc3545;
        }
        .btn-reactivate {
            background-color: #17a2b8;
        }
        button:hover {
            opacity: 0.8;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Management</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone_no']); ?></td>
                        <td><?php echo isset($user['suspended']) && $user['suspended'] ? 'Suspended' : 'Active'; ?></td>
                        <td>
                            <button type="button" class="btn-view" onclick="viewRecords('<?php echo $user['user_name']; ?>')">Records</button>
                            <?php if (!isset($user['suspended']) || !$user['suspended']): ?>
                                <button type="button" class="btn-suspend" onclick="suspendUser('<?php echo $user['user_name']; ?>')">Suspend User</button>
                            <?php else: ?>
                                <button type="button" class="btn-reactivate" onclick="reactivateUser('<?php echo $user['user_name']; ?>')">Reactivate User</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for showing booking records -->
    <div id="recordsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Booking Records</h3>
            <div id="recordsContent">
                <!-- Records will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    function viewRecords(username) {
        const modal = document.getElementById('recordsModal');
        const recordsContent = document.getElementById('recordsContent');
        
        // Fetch booking records using AJAX
        fetch(`get_user_bookings.php?username=${username}`)
            .then(response => response.text())
            .then(data => {
                recordsContent.innerHTML = data;
                modal.style.display = "block";
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading booking records');
            });
    }

    function suspendUser(username) {
        if (confirm('Are you sure you want to suspend this user?')) {
            window.location.href = `admin_users.php?suspend_user=${username}`;
        }
    }

    function reactivateUser(username) {
        if (confirm('Are you sure you want to reactivate this user?')) {
            window.location.href = `admin_users.php?reactivate_user=${username}`;
        }
    }

    function closeModal() {
        document.getElementById('recordsModal').style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('recordsModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>
</html>