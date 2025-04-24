<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Optional: Redirect if not logged in
// if (!isLoggedIn()) {
//     redirect('index.php', 'Please login first');
// }

$user = getCurrentUser();
$username = htmlspecialchars($user['name']);
$role = $user['type'];

include 'includes/header.php';
?>

<h1>Welcome to Swim Data, <?= $username ?>!</h1>
<p>Choose what you would like to do:</p>

<?php if ($role === 'swimmer' || $role === 'admin' || $role === 'guest'): ?>
    <h2>Swimmer Options</h2>
    <div class="nav">
        <a href="swimmer/search.php">Search Swimmers by Time</a>
    </div>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
    <h2>Administrator Tools</h2>
    <div class="nav">
        <a href="admin/operations.php?action=insert&entity=swimmer">Add New Swimmer</a>
        <a href="admin/operations.php?action=search&entity=swimmer">Search Swimmers</a>
        <a href="admin/operations.php?action=delete&entity=swimmer">Delete Swimmer</a>
        <a href="admin/operations.php?action=insert&entity=swim">Add Swim Times</a>
        <a href="admin/operations.php?action=search&entity=admin">Search Admin</a>
        <a href="admin/operations.php?action=insert&entity=admin">Add Admin</a>
        <a href="admin/operations.php?action=delete&entity=admin">Delete Admin</a>
    </div>

    <h2>View Data</h2>
    <div class="nav">
        <a href="admin/operations.php?action=view&entity=conferences">View Conferences</a>
        <a href="admin/operations.php?action=view&entity=meets">View Meets</a>
        <a href="admin/operations.php?action=view&entity=swims">View Swim Records</a>
        <a href="admin/operations.php?action=view&entity=teams">View Teams</a>
    </div>
<?php endif; ?>

<p><a href="auth.php?action=logout">Logout</a></p>

<?php include 'includes/footer.php'; ?>