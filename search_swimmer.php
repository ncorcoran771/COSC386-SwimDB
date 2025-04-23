<?php
session_start();
$user = $_SESSION['user'] ?? 'Guest'; // Default to 'Guest' if $user is not set
$error = $_SESSION['error'] ?? '';
$results = $_SESSION['results'] ?? [];
$event = $_SESSION['event'] ?? '';
$minTimeStr = $_SESSION['form_data']['minTime'] ?? '';
$maxTimeStr = $_SESSION['form_data']['maxTime'] ?? '';

// Clear session data to avoid persistent messages
unset($_SESSION['error'], $_SESSION['results'], $_SESSION['event'], $_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Swimmer </title>
    <style>
        #timeForm {
            display: none;
            margin-top: 20px;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>Welcome <?= htmlspecialchars($user) ?>! Search Swimmer</h1>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form id="searchForm"  method="post">
        <label for="event">Choose an event:</label>
        <select id="event" name="event" onchange="showTimeForm()">
            <option value="">Select an event</option>
            <optgroup label="Freestyle">
                <option value="50y Freestyle" <?= $event === '50y Freestyle' ? 'selected' : '' ?>>50y Freestyle</option>
                <option value="100y Freestyle" <?= $event === '100y Freestyle' ? 'selected' : '' ?>>100y Freestyle</option>
                <option value="200y Freestyle" <?= $event === '200y Freestyle' ? 'selected' : '' ?>>200y Freestyle</option>
                <option value="500y Freestyle" <?= $event === '500y Freestyle' ? 'selected' : '' ?>>500y Freestyle</option>
                <option value="1000y Freestyle" <?= $event === '1000y Freestyle' ? 'selected' : '' ?>>1000y Freestyle</option>
                <option value="1650y Freestyle" <?= $event === '1650y Freestyle' ? 'selected' : '' ?>>1650y Freestyle</option>
            </optgroup>
            <optgroup label="Backstroke">
                <option value="50y Backstroke" <?= $event === '50y Backstroke' ? 'selected' : '' ?>>50y Backstroke</option>
                <option value="100y Backstroke" <?= $event === '100y Backstroke' ? 'selected' : '' ?>>100y Backstroke</option>
                <option value="200y Backstroke" <?= $event === '200y Backstroke' ? 'selected' : '' ?>>200y Backstroke</option>
            </optgroup>
            <optgroup label="Butterfly">
                <option value="50y Butterfly" <?= $event === '50y Butterfly' ? 'selected' : '' ?>>50y Butterfly</option>
                <option value="100y Butterfly" <?= $event === '100y Butterfly' ? 'selected' : '' ?>>100y Butterfly</option>
                <option value="200y Butterfly" <?= $event === '200y Butterfly' ? 'selected' : '' ?>>200y Butterfly</option>
            </optgroup>
            <optgroup label="Breaststroke">
                <option value="50y Breaststroke" <?= $event === '50y Breaststroke' ? 'selected' : '' ?>>50y Breaststroke</option>
                <option value="100y Breaststroke" <?= $event === '100y Breaststroke' ? 'selected' : '' ?>>100y Breaststroke</option>
                <option value="200y Breaststroke" <?= $event === '200y Breaststroke' ? 'selected' : '' ?>>200y Breaststroke</option>
            </optgroup>
            <optgroup label="IM">
                <option value="100y IM" <?= $event === '100y IM' ? 'selected' : '' ?>>100y IM</option>
                <option value="200y IM" <?= $event === '200y IM' ? 'selected' : '' ?>>200y IM</option>
                <option value="400y IM" <?= $event === '400y IM' ? 'selected' : '' ?>>400y IM</option>
            </optgroup>
        </select>

        <div id="timeForm">
            <label for="minTime">Minimum Time (mm:ss:ms):</label>
            <input type="text" id="minTime" name="minTime" placeholder="e.g., 01:23:45" value="<?= htmlspecialchars($minTimeStr) ?>" required>
            <span id="minTimeError" class="error"></span><br>
            <label for="maxTime">Maximum Time (mm:ss:ms):</label>
            <input type="text" id="maxTime" name="maxTime" placeholder="e.g., 01:23:45" value="<?= htmlspecialchars($maxTimeStr) ?>" required>
            <span id="maxTimeError" class="error"></span><br>
            <input type="submit" value="Search">
        </div>
    </form>


    <script>
        function showTimeForm() {
            const eventDropdown = document.getElementById('event');
            const timeForm = document.getElementById('timeForm');
            timeForm.style.display = eventDropdown.value !== "" ? 'block' : 'none';
        }

        // Validate mm:ss:ms format
        function validateTimeFormat(input, errorSpan) {
            const regex = /^\d+:\d{2}:\d{2}$/;
            const isValid = regex.test(input.value);
            errorSpan.textContent = isValid ? '' : 'Please use mm:ss:ms format (e.g., 01:23:45)';
            return isValid;
        }

        // Add validation on input
        document.getElementById('minTime').addEventListener('input', function() {
            validateTimeFormat(this, document.getElementById('minTimeError'));
        });

        document.getElementById('maxTime').addEventListener('input', function() {
            validateTimeFormat(this, document.getElementById('maxTimeError'));
        });

        // Prevent form submission if invalid
        document.getElementById('searchForm').addEventListener('submit', function(event) {
            const minTimeValid = validateTimeFormat(document.getElementById('minTime'), document.getElementById('minTimeError'));
            const maxTimeValid = validateTimeFormat(document.getElementById('maxTime'), document.getElementById('maxTimeError'));
            if (!minTimeValid || !maxTimeValid) {
                event.preventDefault();
            }
        });

        // Show time form if event is pre-selected
        window.onload = function() {
            showTimeForm();
        };
    </script>
</body>
</html>
