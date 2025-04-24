<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('../index.php', 'Please login first');
}

$user = getCurrentUser();
include '../includes/header.php';

// Extract search parameters
$event = $_GET['event'] ?? '';
$minTimeStr = $_GET['minTime'] ?? '';
$maxTimeStr = $_GET['maxTime'] ?? '';
?>

<h1>Search Swimmers</h1>

<?php
// Display messages if any
if (isset($_SESSION['message'])) {
    echo showMessage($_SESSION['message']);
    unset($_SESSION['message']);
}
?>

<form id="searchForm" method="get">
    <div>
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
    </div>

    <div id="timeForm" style="display:none">
        <div>
            <label for="minTime">Minimum Time (mm:ss:ms):</label>
            <input type="text" id="minTime" name="minTime" placeholder="e.g., 01:23:45" value="<?= htmlspecialchars($minTimeStr) ?>" required>
            <span id="minTimeError" class="error"></span>
        </div>
        <div>
            <label for="maxTime">Maximum Time (mm:ss:ms):</label>
            <input type="text" id="maxTime" name="maxTime" placeholder="e.g., 01:23:45" value="<?= htmlspecialchars($maxTimeStr) ?>" required>
            <span id="maxTimeError" class="error"></span>
        </div>
        <button type="submit" class="button">Search</button>
    </div>
</form>

<script>
// Time form toggle and validation scripts
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

// Initialize on page load
window.onload = function() {
    showTimeForm();
    
    // Add input validation
    document.getElementById('minTime').addEventListener('input', function() {
        validateTimeFormat(this, document.getElementById('minTimeError'));
    });
    
    document.getElementById('maxTime').addEventListener('input', function() {
        validateTimeFormat(this, document.getElementById('maxTimeError'));
    });
    
    // Form submission validation
    document.getElementById('searchForm').addEventListener('submit', function(event) {
        const minTimeValid = validateTimeFormat(document.getElementById('minTime'), document.getElementById('minTimeError'));
        const maxTimeValid = validateTimeFormat(document.getElementById('maxTime'), document.getElementById('maxTimeError'));
        
        if (!minTimeValid || !maxTimeValid) {
            event.preventDefault();
        }
    });
};
</script>

<?php
// Handle search results
if (!empty($event) && !empty($minTimeStr) && !empty($maxTimeStr)) {
    // Convert to seconds
    $minTime = timeToSeconds($minTimeStr);
    $maxTime = timeToSeconds($maxTimeStr);
    
    // Search for swimmers
    $stmt = $conn->prepare(
        "SELECT s.name, s.gender, s.hometown, s.team, s.powerIndex, sw.time 
        FROM Swimmer s
        JOIN Swim sw ON s.swimmerID = sw.swimmerID
        WHERE sw.eventName = ? AND sw.time BETWEEN ? AND ?
        ORDER BY sw.time ASC"
    );
    
    $stmt->bind_param('sdd', $event, $minTime, $maxTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<h2>Results</h2>";
        echo "<table>";
        echo "<tr><th>Name</th><th>Gender</th><th>Hometown</th><th>Team</th><th>Power Index</th><th>Time</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hometown']) . "</td>";
            echo "<td>" . htmlspecialchars($row['team']) . "</td>";
            echo "<td>" . htmlspecialchars($row['powerIndex']) . "</td>";
            echo "<td>" . secondsToTime($row['time']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo showMessage("No swimmers found matching your criteria");
    }
}
?>

<p><a href="../home.php" class="button">Back to Home</a></p>

<?php include '../includes/footer.php'; ?>