<?php
session_start();
$conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$nameSearch = $_GET['name'] ?? '';
$powerOp = $_GET['power_operator'] ?? '';
$powerValue = $_GET['power_value'] ?? '';
$genderSearch = $_GET['gender'] ?? '';
$swimmerIDSearch = $_GET['swimmerID'] ?? '';
$hometownSearch = $_GET['hometown'] ?? '';
$teamSearch = $_GET['team'] ?? '';

$query = "SELECT * FROM Swimmer WHERE 1=1";

if (!empty($nameSearch)) {
    $nameEscaped = mysqli_real_escape_string($conn, $nameSearch);
    $query .= " AND name LIKE '%$nameEscaped%'";
}
if (!empty($powerOp) && in_array($powerOp, ['=', '<', '>']) && is_numeric($powerValue)) {
    $powerValEscaped = (int)$powerValue;
    $query .= " AND powerIndex $powerOp $powerValEscaped";
}
if (!empty($genderSearch)) {
    $genderEscaped = mysqli_real_escape_string($conn, $genderSearch);
    $query .= " AND gender = '$genderEscaped'";
}
if (!empty($swimmerIDSearch)) {
    $idEscaped = (int)$swimmerIDSearch;
    $query .= " AND swimmerID = $idEscaped";
}
if (!empty($hometownSearch)) {
    $hometownEscaped = mysqli_real_escape_string($conn, $hometownSearch);
    $query .= " AND hometown LIKE '%$hometownEscaped%'";
}
if (!empty($teamSearch)) {
    $teamEscaped = mysqli_real_escape_string($conn, $teamSearch);
    $query .= " AND team LIKE '%$teamEscaped%'";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Swimmers</title>
</head>
<body>
    <h1>Swimmer Search</h1>
    <form method="GET" action="view_swimmers.php">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($nameSearch) ?>"></label><br>

        <label>Power Index:
            <select name="power_operator">
                <option value="">Any</option>
                <option value="=" <?= $powerOp === '=' ? 'selected' : '' ?>>=</option>
                <option value="<" <?= $powerOp === '<' ? 'selected' : '' ?>><</option>
                <option value=">" <?= $powerOp === '>' ? 'selected' : '' ?>>></option>
            </select>
            <input type="number" name="power_value" value="<?= htmlspecialchars($powerValue) ?>">
        </label><br>

        <label>Gender:
            <select name="gender">
                <option value="">Any</option>
                <option value="M" <?= $genderSearch === 'M' ? 'selected' : '' ?>>Male</option>
                <option value="F" <?= $genderSearch === 'F' ? 'selected' : '' ?>>Female</option>
            </select>
        </label><br>

        <label>Swimmer ID: <input type="number" name="swimmerID" value="<?= htmlspecialchars($swimmerIDSearch) ?>"></label><br>
        <label>Hometown: <input type="text" name="hometown" value="<?= htmlspecialchars($hometownSearch) ?>"></label><br>
        <label>Team: <input type="text" name="team" value="<?= htmlspecialchars($teamSearch) ?>"></label><br>

        <input type="submit" value="Search">
    </form>

    <h2>Results</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Name</th>
            <th>Power Index</th>
            <th>Gender</th>
            <th>Swimmer ID</th>
            <th>Hometown</th>
            <th>Team</th>
        </tr>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['powerIndex']) ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['swimmerID']) ?></td>
                    <td><?= htmlspecialchars($row['hometown']) ?></td>
                    <td><?= htmlspecialchars($row['team']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No swimmers found.</td></tr>
        <?php endif; ?>
    </table>
                       <div>
        <a href="home.php">Back to Home</a> | 
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
