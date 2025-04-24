<?php
session_start();
include('DB.php');  // Include DB.php for database connection

$adminName = $_GET['adminName'] ?? '';  // Admin search query

// Query to fetch admin data based on search parameters
$query = "SELECT * FROM Admin WHERE 1=1";
if (!empty($adminName)) {
    $adminName = mysqli_real_escape_string($conn, $adminName);  // Escape user input
    $query .= " AND name LIKE '%$adminName%'";  // Add search condition for name
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Admin</title>
</head>
<body>
    <h1>Admin Table</h1>
    <div style="display: flex;">
        <!-- Search form on the left -->
        <div style="width: 40%; padding-right: 20px;">
            <form method="get" action="search_admin.php">
                <label>Admin Name:</label><br>
                <input type="text" name="adminName" value="<?= htmlspecialchars($adminName) ?>"><br><br>
                <input type="submit" value="Search">
            </form>
        </div>

        <!-- Admin table on the right -->
        <div style="width: 60%;">
            <table border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th>Name</th>
                    <th>Admin ID</th>
                    <th>Password</th>
                </tr>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['adminID']) ?></td>
                            <td>*****</td> <!-- Hide password for security -->
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No results found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div>
        <a href="home.php">Back to Home</a> | 
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

<?php mysqli_close($conn); ?>
