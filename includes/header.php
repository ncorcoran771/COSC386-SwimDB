<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/~knorton3/COSC386-SwimDB/assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?= SITE_NAME ?></h1>
        </header>
        <main>
            <?php
            // Display message if exists
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }
            ?>