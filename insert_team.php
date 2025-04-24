<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
    if (!$conn) die("Connection failed: " . mysqli_connect_error());

    $name = mysqli_real_escape_string($conn, $_POST['teamName']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    $query = "INSERT INTO Team (teamName, location) VALUES ('$name', '$location')";
    $result = mysqli_query($conn, $query);

    echo $result ? "Team inserted successfully." : "Insert failed: " . mysqli_error($conn);
    mysqli_close($conn);
}
?>
<h2>Insert Team</h2>
<form method="post">
    Team Name: <input type="text" name="teamName" required><br><br>
    Location: <input type="text" name="location" required><br><br>
    <input type="submit" value="Insert">
</form>
<a href="home.php">Back to Home</a>
