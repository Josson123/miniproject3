<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Sidebar Styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding: 20px;
            color: white;
        }
        .sidebar h2 {
            color: #f8f9fa;
        }
        .sidebar ul {
            padding: 0;
        }
        .sidebar .list-group-item {
            background-color: #343a40;
            border: none;
            color: #f8f9fa;
            padding: 15px;
            transition: background-color 0.3s ease;
        }
        .sidebar .list-group-item:hover {
            background-color: #495057;
            color: white;
        }
        .sidebar a {
            color: #f8f9fa;
            text-decoration: none;
        }
        .sidebar a:hover {
            color: #ffffff;
        }

        /* Content wrapper to give space to the sidebar */
        .content-wrapper {
            margin-left: 270px; /* Adjust based on sidebar width */
            padding: 20px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2><b>Admin Actions</b></h2>
        <ul class="list-group">
            <li class="list-group-item"><a href="admin.php">Admin Home</a></li>
            <li class="list-group-item"><a href="admin_add_bike.php">Add Bike</a></li>
            <li class="list-group-item"><a href="admin_manage_bikes.php">Manage Bikes</a></li>
            <li class="list-group-item"><a href="admin_view_bookings.php">View Bookings</a></li>
            <li class="list-group-item"><a href="admin_users.php">Users</a></li>
            <li class="list-group-item"><a href="admin_logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="content-wrapper">
       <!-- <h1>Welcome to the Admin Dashboard</h1>
        <p>Here you can manage bikes, bookings, and users of the Bike Rental system.</p>
    </div>

    <!-- Bootstrap JS for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>