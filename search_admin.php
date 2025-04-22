<?php
session_start();
include('DB.php'); // Ensure DB connection

// Check if the user is logged in and retrieve their role and name
$role = $_SESSION['role'] ?? 'guest'; // Default to 'guest' if not logged in
$user = $_SESSION['userData']['name'] ?? 'Guest'; // Get user name from session

// Allow both 'admin' and 'guest' roles to search for admins, but restrict admin-only actions
if ($role === 'admin' || $role === 'guest') {
    // Handle search for admins
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $searchQuery = $_POST['searchQuery'] ?? '';

        // Use prepared statements to prevent SQL injection
        if ($stmt = $conn->prepare("SELECT * FROM Admin WHERE name LIKE ?")) {
            $searchQuery = "%$searchQuery%"; // Add wildcards for LIKE query
            $stmt->bind_param('s', $searchQuery); // Bind the search query as a string
            
            $stmt->execute(); // Execute the statement
            $result = $stmt->get_result(); // Get the result
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "Name: " . htmlspecialchars($row['name']) . "<br>";
                    echo "Role: " . htmlspecialchars($row['role']) . "<br><br>";
                }
            } else {
                echo "No admin found.";
            }
            
            $stmt->close(); // Close the prepared statement
        } else {
            echo "Error with the database query.";
        }
    }
} else {
    echo "You are not authorized to view this page.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Admin</title>
</head>
<body>
    <h1>Welcome <?= htmlspecialchars($user) ?>! Search Admin</h1>
    <form method="post">
        <input type="text" name="searchQuery" placeholder="Enter admin's name">
        <button type="submit">Search</button>
    </form>
    <a href="home.php">Back to Home</a>
</body>
</html>
