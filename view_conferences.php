<?php
session_start();
$conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$name = $_GET['name'] ?? '';
$state = $_GET['state'] ?? '';

$query = "SELECT * FROM Conference WHERE 1=1";
if (!empty($name)) {
    $name = mysqli_real_escape_string($conn, $name);
    $query .= " AND name LIKE '%$name%'";
}
if (!empty($state)) {
    $state = mysqli_real_escape_string($conn, $state);
    $query .= " AND state LIKE '%$state%'";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Conferences</title>
</head>
<body>
    <h1>Conference Table</h1>
    <div style="display: flex;">
        <!-- Search form on the left -->
        <div style="width: 40%; padding-right: 20px;">
            <form method="get" action="view_conferences.php">
                <label>Name:</label><br>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>"><br><br>
                <label>State:</label><br>
                <input type="text" name="state" value="<?= htmlspecialchars($state) ?>"><br><br>
                <input type="submit" value="Search">
            </form>
        </div>

        <!-- Conference table on the right -->
        <div style="width: 60%;">
            <table border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th>Name</th>
                    <th>State</th>
                </tr>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['state']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">No results found.</td></tr>
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
