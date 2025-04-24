<?php
session_start();
$conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$meetName = $_GET['meetName'] ?? '';
$location = $_GET['location'] ?? '';

$query = "SELECT * FROM Meet WHERE 1=1";
if (!empty($meetName)) {
    $meetName = mysqli_real_escape_string($conn, $meetName);
    $query .= " AND meetName LIKE '%$meetName%'";
}
if (!empty($location)) {
    $location = mysqli_real_escape_string($conn, $location);
    $query .= " AND location LIKE '%$location%'";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Meets</title>
</head>
<body>
    <h1>Meet Table</h1>
    <div style="display: flex;">
        <!-- Search form on the left -->
        <div style="width: 40%; padding-right: 20px;">
            <form method="get" action="view_meets.php">
                <label>Meet Name:</label><br>
                <input type="text" name="meetName" value="<?= htmlspecialchars($meetName) ?>"><br><br>
                <label>Location:</label><br>
                <input type="text" name="location" value="<?= htmlspecialchars($location) ?>"><br><br>
                <input type="submit" value="Search">
            </form>
        </div>

        <!-- Meet table on the right -->
        <div style="width: 60%;">
            <table border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th>Meet Name</th>
                    <th>Location</th>
                    <th>Date</th>
                </tr>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['meetName']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No results found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Navigation Links -->
    <div>
        <a href="home.php">Back to Home</a> | 
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
