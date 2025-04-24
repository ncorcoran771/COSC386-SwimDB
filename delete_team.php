<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
    if (!$conn) die("Connection failed: " . mysqli_connect_error());

    $name = mysqli_real_escape_string($conn, $_POST['teamName']);
    $query = "DELETE FROM Team WHERE teamName = '$name'";
    $result = mysqli_query($conn, $query);

    echo $result ? "Team deleted successfully." : "Delete failed: " . mysqli_error($conn);
    mysqli_close($conn);
}
?>
<h2>Delete Team</h2>
<form method="post">
    Team Name: <input type="text" name="teamName" required><br><br>
    <input type="submit" value="Delete">
</form>
<a href="home.php">Back to Home</a>
