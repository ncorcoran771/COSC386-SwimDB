<body>
<?php
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);
// Get the entity parameter from URL if it exists
$entity = isset($_GET['entity']) ? $_GET['entity'] : '';
?>
<div class="sidebar">
        <img src="assets/logo.jpg" alt="Swim Data logo" style="width:200px;height:200px;">
        <a <?= ($currentPage == 'home.php') ? 'class="active"' : '' ?> href="home.php">Home</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'conferences') ? 'class="active"' : '' ?> href="view.php?entity=conferences">View Conferences</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'meets') ? 'class="active"' : '' ?> href="view.php?entity=meets">View Meets</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'swims') ? 'class="active"' : '' ?> href="view.php?entity=swims">View Swim Records</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'teams') ? 'class="active"' : '' ?> href="view.php?entity=teams">View Teams</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'swimmers') ? 'class="active"' : '' ?> href="view.php?entity=swimmers">View Swimmers</a>
        <a <?= ($currentPage == 'event_records.php') ? 'class="active"' : '' ?> href="event_records.php">Event Records</a>
        <a <?= ($currentPage == 'view.php' && $entity == 'find_recruit') ? 'class="active"' : '' ?> href="view.php?entity=find_recruit">Find Recruit</a>
    </div>
</body>