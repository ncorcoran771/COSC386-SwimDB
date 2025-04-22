<?php
session_start();
include('DB.php'); // Ensure DB connection

// Get user role from session
$role = $_SESSION['role'] ?? 'guest'; // Default to 'guest' if not set
if ($role !== 'swimmer' && $role !== 'admin' && $role !== 'guest') {
    echo "You are not authorized to view this page.";
    exit;
}

// Get user name from session
$user = $_SESSION['userData']['name'] ?? 'Guest';

// Handle search for swimmers
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $searchQuery = $_POST['searchQuery'] ?? '';

    // Use prepared statements to prevent SQL injection
    if ($stmt = $conn->prepare("SELECT * FROM Swimmer WHERE name LIKE ?")) {
        $searchQuery = "%$searchQuery%"; // Add wildcards for LIKE query
        $stmt->bind_param('s', $searchQuery); // Bind the search query as a string
        
        $stmt->execute(); // Execute the statement
        $result = $stmt->get_result(); // Get the result
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "Name: " . htmlspecialchars($row['name']) . "<br>";
                echo "Power Index: " . htmlspecialchars($row['powerIndex']) . "<br>";
                echo "Gender: " . htmlspecialchars($row['gender']) . "<br>";
                echo "Hometown: " . htmlspecialchars($row['hometown']) . "<br>";
                echo "Team: " . htmlspecialchars($row['team']) . "<br><br>";
            }
        } else {
            echo "No swimmer found.";
        }
        
        $stmt->close(); // Close the prepared statement
    } else {
        echo "Error with the database query.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Swimmer</title>
</head>
<body>
    <h1>Welcome <?= htmlspecialchars($user) ?>! Search Swimmer</h1>
    <form method="post">
        <input type="text" name="searchQuery" placeholder="Enter swimmer's name">
        <button type="submit">Search</button>
    </form>
    <a href="home.php">Back to Home</a>
</body>
</html>
