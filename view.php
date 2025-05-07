<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Ensure functions.php is included for time conversion
require_once 'includes/auth.php';

// Entity must come from GET parameters only
$entity = $_GET['entity'] ?? 'swimmer';
$user = getCurrentUser();

include 'includes/header.php';
include 'includes/sidebar.php';

?>

<div class="main">
    <div class="container">
        <?php
        // Show message if any
        if (isset($_SESSION['message'])) {
            echo showMessage($_SESSION['message']);
            unset($_SESSION['message']);
        }

        // Initialize variables for the generic viewer or specific sections
        $dbTable = null;
        $queryResult = null; // For find_recruit or generic viewer results
        $error = ''; // For find_recruit specific errors

        // --- Find Recruit Specific Logic and HTML (Integrated) ---
        if ($entity === 'find_recruit') {
            // --- Database Connection (using existing $conn from db.php) ---
            // Check if connection is still valid if needed, though db.php should handle persistence
            if ($conn->connect_error) {
                error_log("Database Connection failed in find_recruit section: " . $conn->connect_error);
                $error = "Sorry, there was a problem connecting to the database for the search. Please try again later.";
            } else {

                // --- Initialize Variables (using existing $user) ---
                $submittedData = $_SESSION['submittedData'] ?? null;
                $clearSession = true; // Initialize clearSession flag
                echo "<script>document.title = 'Find Recruit';</script>"; // Set page title

                // --- Handle Form Submission ---
                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    // Capture submitted data into session
                    $_SESSION['submittedData'] = [
                        'gender' => $_POST['gender'] ?? '',
                        'states' => $_POST['states'] ?? [],
                        'event' => $_POST['event'] ?? '',
                        'minTime' => $_POST['minTime'] ?? '',
                        'maxTime' => $_POST['maxTime'] ?? ''
                    ];

                    // Redirect to prevent form resubmission
                    header("Location: " . $_SERVER['PHP_SELF'] . "?entity=find_recruit"); // Redirect back to find_recruit entity
                    exit;
                }

                // --- Process Submitted Data (after redirect) ---
                // This block runs when the page is loaded *after* a POST request (due to the redirect)
                if ($submittedData !== null) { // Ensure submittedData is not null (meaning a search was attempted)
                    try {
                        $gender = $submittedData['gender'] ?? '';
                        $states = $submittedData['states'] ?? [];
                        $event = $submittedData['event'] ?? '';
                        $minTimeStr = $submittedData['minTime'] ?? '';
                        $maxTimeStr = $submittedData['maxTime'] ?? '';

                        // Convert times to a comparable format (e.g., seconds)
                        $minTimeSeconds = timeToSeconds($minTimeStr);
                        $maxTimeSeconds = timeToSeconds($maxTimeStr);

                        // Basic validation for time conversion
                        if ($minTimeSeconds === false || $maxTimeSeconds === false) {
                             $error = "Invalid time format or values. Please use mm:ss:ms (e.g., 01:23:45) with valid seconds/milliseconds.";
                             $clearSession = false; // Don't clear session if there's an error
                        } else {

                            // --- Build the SQL Query using prepared statements ---
                            $query = "SELECT s.name, s.gender, s.hometown, s.team, s.powerIndex, sw.time
                                            FROM Swimmer s
                                            JOIN Swim sw ON s.swimmerID = sw.swimmerID
                                            WHERE 1=1"; // Start with a true condition to easily append AND clauses

                            $params = [];
                            $types = "";

                            if (!empty($gender)) {
                                $query .= " AND s.gender = ?";
                                $params[] = $gender;
                                $types .= "s";
                            }

                            // Handle multiple states - Extracting state from hometown
                            if (!empty($states) && is_array($states)) {
                                // Create placeholders for each state
                                $placeholders = implode(',', array_fill(0, count($states), '?'));
                                // Use SUBSTRING_INDEX to get the part after the last comma and space from 'hometown'
                                $query .= " AND SUBSTRING_INDEX(s.hometown, ', ', -1) IN ($placeholders)";
                                $params = array_merge($params, $states); // Add the selected state abbreviations to parameters
                                $types .= str_repeat("s", count($states)); // Add 's' type for each state parameter
                            }

                            if (!empty($event)) {
                                $query .= " AND sw.eventName = ?";
                                $params[] = $event;
                                $types .= "s";
                            }

                            // Use the converted numeric time for comparison
                            // Assuming 'sw.time' column in the database is stored as a numeric type (like float or decimal representing seconds)
                            if (!empty($minTimeStr) && $minTimeSeconds !== false) { // Check if conversion was successful
                                 $query .= " AND sw.time >= ?";
                                 $params[] = $minTimeSeconds;
                                 $types .= "d"; // Assuming double for time in seconds
                            }

                            if (!empty($maxTimeStr) && $maxTimeSeconds !== false) { // Check if conversion was successful
                                 $query .= " AND sw.time <= ?";
                                 $params[] = $maxTimeSeconds;
                                 $types .= "d"; // Assuming double for time in seconds
                            }

                             // Ordering the results
                             $query .= " ORDER BY sw.time ASC";

                            // --- Execute the Prepared Statement ---
                            $stmt = $conn->prepare($query);

                            if ($stmt === false) {
                                throw new Exception('Failed to prepare statement: ' . $conn->error);
                            }

                            // Bind parameters dynamically
                            if (!empty($params)) {
                                // The ...$params syntax "unpacks" the array into arguments for bind_param
                                $stmt->bind_param($types, ...$params);
                            }

                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result === false) {
                                throw new Exception('Query execution failed: ' . $stmt->error);
                            }

                            $queryResult = $result->fetch_all(MYSQLI_ASSOC);

                            $stmt->close();
                        }

                    } catch (Exception $e) {
                        $error = $e->getMessage();
                        $clearSession = false; // Don't clear session if there's an error
                    }
                }

                // Clear session flash data *after* processing if no error occurred
                if ($clearSession) {
                    unset($_SESSION['error'], $_SESSION['submittedData']);
                }
            }

            // --- Display Find Recruit Form and Results ---
            ?>
            <div class="search-form">
                <h1>Welcome <?= htmlspecialchars($user['name']) ?>! Search Swimmer</h1>
                <?php if ($error): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form id="searchForm" method="post" action="">
                    <label for="gender">Select Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select a gender</option>
                        <option value="M" <?= (isset($submittedData['gender']) && $submittedData['gender'] === 'M') ? 'selected' : '' ?>>Male</option>
                        <option value="F" <?= (isset($submittedData['gender']) && $submittedData['gender'] === 'F') ? 'selected' : '' ?>>Female</option>
                    </select>

                    <div id="statesSection" style="display: none;">
                        <label for="states">Select State(s):</label>
                        <select id="states" name="states[]" multiple required>
                            <?php
                            $states = [
                                'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
                                'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
                                'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
                                'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
                                'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
                                'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
                                'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
                                'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
                            ];
                            foreach ($states as $code => $name) {
                                $selected = (isset($submittedData['states']) && is_array($submittedData['states']) && in_array($code, $submittedData['states'])) ? 'selected' : '';
                                echo "<option value=\"$code\" $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div id="eventSection" style="display: none;">
                        <label for="event">Choose an Event:</label>
                        <select id="event" name="event" required>
                            <option value="">Select an event</option>
                            <optgroup label="Freestyle">
                                <?php
                                $freestyleEvents = ["50y Freestyle", "100y Freestyle", "200y Freestyle", "500y Freestyle", "1000y Freestyle", "1650y Freestyle"];
                                foreach ($freestyleEvents as $e) {
                                     $selected = (isset($submittedData['event']) && $submittedData['event'] === $e) ? 'selected' : '';
                                     echo "<option value=\"$e\" $selected>$e</option>";
                                }
                                ?>
                            </optgroup>
                            <optgroup label="Backstroke">
                                 <?php
                                 $backstrokeEvents = ["50y Backstroke", "100y Backstroke", "200y Backstroke"];
                                 foreach ($backstrokeEvents as $e) {
                                      $selected = (isset($submittedData['event']) && $submittedData['event'] === $e) ? 'selected' : '';
                                      echo "<option value=\"$e\" $selected>$e</option>";
                                 }
                                 ?>
                            </optgroup>
                            <optgroup label="Butterfly">
                                 <?php
                                 $butterflyEvents = ["50y Butterfly", "100y Butterfly", "200y Butterfly"];
                                 foreach ($butterflyEvents as $e) {
                                      $selected = (isset($submittedData['event']) && $submittedData['event'] === $e) ? 'selected' : '';
                                      echo "<option value=\"$e\" $selected>$e</option>";
                                 }
                                 ?>
                            </optgroup>
                            <optgroup label="Breaststroke">
                                 <?php
                                 $breaststrokeEvents = ["50y Breaststroke", "100y Breaststroke", "200y Breaststroke"];
                                 foreach ($breaststrokeEvents as $e) {
                                      $selected = (isset($submittedData['event']) && $submittedData['event'] === $e) ? 'selected' : '';
                                      echo "<option value=\"$e\" $selected>$e</option>";
                                 }
                                 ?>
                            </optgroup>
                            <optgroup label="IM">
                                 <?php
                                 $imEvents = ["100y IM", "200y IM", "400y IM"];
                                 foreach ($imEvents as $e) {
                                      $selected = (isset($submittedData['event']) && $submittedData['event'] === $e) ? 'selected' : '';
                                      echo "<option value=\"$e\" $selected>$e</option>";
                                 }
                                 ?>
                            </optgroup>
                        </select>
                    </div>

                    <div id="timeSection" style="display: none;">
                        <label for="minTime">Minimum Time (mm:ss:ms):</label>
                        <input type="text" id="minTime" name="minTime" class="time-input" placeholder="e.g., 01:23:45"  value="<?= htmlspecialchars($submittedData['minTime'] ?? '') ?>">
                         <label for="maxTime">Maximum Time (mm:ss:ms):</label>
                        <input type="text" id="maxTime" name="maxTime" class="time-input" placeholder="e.g., 01:23:45"  value="<?= htmlspecialchars($submittedData['maxTime'] ?? '') ?>">
                         <input type="submit" value="Search">
                    </div>
                </form>

                <?php if (!empty($queryResult)): ?>
                    <h2>Results</h2>
                    <table>
                        <thead>
                             <tr>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Hometown</th>
                                <th>Team</th>
                                <th>Power Index</th>
                                <th>Time</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queryResult as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['hometown']) ?></td>
                                    <td><?= htmlspecialchars($row['team']) ?></td>
                                    <td><?= htmlspecialchars($row['powerIndex']) ?></td>
                                    <td><?= htmlspecialchars(formatTime($row['time'])) ?></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($submittedData !== null && empty($error)): // Only show "no results" if a search was performed and no PHP error occurred ?>
                     <p>No swimmers found matching your criteria.</p>
                <?php endif; ?>

            </div>

            <script>
                // JavaScript for the Find Recruit form visibility and validation
                function updateVisibility() {
                    const gender = document.getElementById('gender').value;
                    const statesSelect = document.getElementById('states');
                    const statesSelected = statesSelect && statesSelect.selectedOptions ? statesSelect.selectedOptions.length > 0 : false;
                    const eventValue = document.getElementById('event').value;

                    const statesSection = document.getElementById('statesSection');
                    if (statesSection) {
                       statesSection.style.display = gender !== '' ? 'block' : 'none';
                    }

                    const eventSection = document.getElementById('eventSection');
                    const isStatesSectionVisibleAndSelected = statesSection && statesSection.style.display !== 'none' && statesSelected;
                    if (eventSection) {
                        eventSection.style.display = isStatesSectionVisibleAndSelected ? 'block' : 'none';
                    }

                    const timeSection = document.getElementById('timeSection');
                    const isEventSectionVisibleAndSelected = eventSection && eventSection.style.display !== 'none' && eventValue !== '';
                    if (timeSection) {
                         timeSection.style.display = isEventSectionVisibleAndSelected ? 'block' : 'none';
                    }
                }

                const genderSelect = document.getElementById('gender');
                if (genderSelect) genderSelect.addEventListener('change', updateVisibility);

                const statesSelect = document.getElementById('states');
                if (statesSelect) statesSelect.addEventListener('change', updateVisibility);

                const eventSelect = document.getElementById('event');
                if (eventSelect) eventSelect.addEventListener('change', updateVisibility);

                function validateTimeFormat(input, errorSpan) {
                    const regex = /^\d+:[0-5]\d:[0-9]\d$/;
                    const isValid = regex.test(input.value);
                    // Removed error span elements from HTML as per original code comments
                    // errorSpan.textContent = isValid ? '' : 'Please use mm:ss:ms format (e.g., 01:23:45) with seconds 00-59 and ms 00-99.';
                    return isValid;
                }

                const minTimeInput = document.getElementById('minTime');
                // const minTimeErrorSpan = document.getElementById('minTimeError'); // Removed
                if (minTimeInput /* && minTimeErrorSpan */) { // Adjusted check
                     minTimeInput.addEventListener('input', function() {
                         // validateTimeFormat(this, minTimeErrorSpan); // Adjusted call
                         validateTimeFormat(this, { textContent: function(msg){ console.log('Time validation error placeholder:', msg); } }); // Placeholder for console logging
                     });
                }

                const maxTimeInput = document.getElementById('maxTime');
                // const maxTimeErrorSpan = document.getElementById('maxTimeError'); // Removed
                 if (maxTimeInput /* && maxTimeErrorSpan */) { // Adjusted check
                    maxTimeInput.addEventListener('input', function() {
                         // validateTimeFormat(this, maxTimeErrorSpan); // Adjusted call
                         validateTimeFormat(this, { textContent: function(msg){ console.log('Time validation error placeholder:', msg); } }); // Placeholder for console logging
                     });
                 }

                 // Call updateVisibility on page load to show sections based on potentially restored session data
                 updateVisibility();

                 // Also add event listeners to multi-select for initial state
                 if (statesSelect && statesSelect.selectedOptions.length > 0) {
                    updateVisibility();
                 }

            </script>

            <?php
        } else {
            // --- Generic Table Viewer Logic ---
            // This part handles displaying data for other entities (conferences, meets, etc.)

            $tableMap = [
                'conferences' => 'Conference',
                'meets' => 'Meet',
                'swims' => 'Swim',
                'teams' => 'Team',
                "swimmers" => "Swimmer"
            ];
            $dbTable = $tableMap[$entity] ?? null; // Set $dbTable based on entity

            if ($dbTable === null) {
                 echo showMessage("Invalid table specified", true);
                 // Don't include footer here, let the main footer handle it
            } else {
                // Build query based on entity
                if ($dbTable === 'Swim') {
                    $query = "SELECT s.eventName, s.meetName, s.meetDate, s.swimmerID,
                                sw.name AS swimmerName, s.time
                                FROM Swim s
                                JOIN Swimmer sw ON s.swimmerID = sw.swimmerID WHERE 1=1";
                } else {
                    $query = "SELECT * FROM $dbTable WHERE 1=1";
                }

                ?>

                <h2><?= htmlspecialchars($dbTable) ?> Search</h2>
                    <form method="POST" action="view.php?entity=<?= htmlspecialchars($entity) ?>">

                        <?php if ($dbTable === 'Conference'): ?>
                            <input type="text" name="name" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <input type="text" name="state" placeholder="Search by State" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Meet'): ?>
                            <input type="text" name="name" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Swimmer'): ?>
                            <input type="text" name="name" placeholder="Search by Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <input type="number" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                            <select name="gender">
                                <option value="">Any Gender</option>
                                <option value="M" <?= ($_POST['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                                <option value="F" <?= ($_POST['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
                            </select>
                            <input type="text" name="hometown" placeholder="Search by Hometown" value="<?= htmlspecialchars($_POST['hometown'] ?? '') ?>">
                            <input type="text" name="team" placeholder="Search by Team" value="<?= htmlspecialchars($_POST['team'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Swim'): ?>
                            <input type="text" name="eventName" placeholder="Search by Event Name" value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>">
                            <input type="text" name="meetName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['meetName'] ?? '') ?>">
                            <input type="text" name="meetDate" placeholder="Search by Meet Date (YYYY-MM-DD)" value="<?= htmlspecialchars($_POST['meetDate'] ?? '') ?>">
                            <input type="text" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                            <input type="text" name="time" placeholder="Search by Time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Team'): ?>
                            <input type="text" name="teamName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['teamName'] ?? '') ?>">
                            <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                            <input type="text" name="confName" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['confName'] ?? '') ?>">
                            <input type="text" name="state" placeholder="Search by Conference State" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                        <?php endif; ?>

                        <input type="submit" value="Search">
                    </form>
                <?php
                // Process POST parameters for the current search
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $params = [];
                     $types = ""; // Initialize types string for prepared statement

                    if ($dbTable === 'Conference') {
                        if (!empty($_POST['name'])) {
                            $query .= " AND name LIKE ?";
                            $params[] = '%' . $_POST['name'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['state'])) {
                            $query .= " AND state = ?";
                            $params[] = $_POST['state'];
                            $types .= 's';
                        }
                    } else if ($dbTable === 'Meet') {
                        if (!empty($_POST['name'])) {
                            $query .= " AND meetName LIKE ?";
                            $params[] = '%' . $_POST['name'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['location'])) {
                            $query .= " AND location = ?";
                            $params[] = $_POST['location'];
                            $types .= 's';
                        }
                    } elseif ($dbTable === 'Swimmer') {
                        // Handle Swimmer table specific filters
                        if (!empty($_POST['name'])) {
                            $query .= " AND name LIKE ?";
                            $params[] = '%' . $_POST['name'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['swimmerID'])) {
                            $query .= " AND swimmerID = ?";
                            $params[] = $_POST['swimmerID'];
                            $types .= 'i'; // Swimmer ID is an integer
                        }
                        if (!empty($_POST['gender'])) {
                            $query .= " AND gender = ?";
                            $params[] = $_POST['gender'];
                            $types .= 's';
                        }
                        if (!empty($_POST['hometown'])) {
                            $query .= " AND hometown LIKE ?";
                            $params[] = '%' . $_POST['hometown'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['team'])) {
                            $query .= " AND team LIKE ?";
                            $params[] = '%' . $_POST['team'] . '%';
                            $types .= 's';
                        }
                    } else if ($dbTable === 'Swim') {
                        // Handle Swim table specific filters
                        if (!empty($_POST['eventName'])) {
                            $query .= " AND s.eventName LIKE ?";
                            $params[] = '%' . $_POST['eventName'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['meetName'])) {
                            $query .= " AND s.meetName LIKE ?";
                            $params[] = '%' . $_POST['meetName'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['meetDate'])) {
                            $query .= " AND s.meetDate = ?"; // Use = for exact date search
                            $params[] = $_POST['meetDate'];
                            $types .= 's';
                        }
                        if (!empty($_POST['swimmerID'])) {
                            $query .= " AND s.swimmerID = ?";
                            $params[] = $_POST['swimmerID'];
                            $types .= 'i'; // Swimmer ID is an integer
                        }
                         if (!empty($_POST['time'])) {
                            // Assuming time in DB is stored as seconds (float)
                            // For generic search, allow LIKE search on the string representation of time
                            $query .= " AND s.time LIKE ?";
                            $params[] = '%' . $_POST['time'] . '%'; // Searching by string representation
                            $types .= 's';
                         }
                    } else if ($dbTable === 'Team') {
                        if (!empty($_POST['teamName'])) {
                            $query .= " AND teamName LIKE ?";
                            $params[] = '%' . $_POST['teamName'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['location'])) {
                            $query .= " AND location = ?";
                            $params[] = $_POST['location'];
                            $types .= 's';
                        }
                        if (!empty($_POST['confName'])) {
                            $query .= " AND confName LIKE ?";
                            $params[] = '%' . $_POST['confName'] . '%';
                            $types .= 's';
                        }
                        if (!empty($_POST['state'])) {
                            $query .= " AND confState = ?";
                            $params[] = $_POST['state'];
                            $types .= 's';
                        }
                    }
                }

                if ($dbTable === 'Swim') {
                    $query .= " ORDER BY s.meetDate DESC, s.eventName";
                }

                // Prepare and execute the query
                $stmt = $conn->prepare($query);

                // Bind the parameters dynamically
                if (!empty($params)) {
                    // Use the dynamically built $types string
                    $stmt->bind_param($types, ...$params);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    
                    // Get column names from the first row
                    $firstRow = $result->fetch_assoc();
                    echo "<tr>";
                    foreach (array_keys($firstRow) as $column) {
                        echo "<th>" . htmlspecialchars($column) . "</th>";
                    }
                    
                    // Add Actions column for Meet, Swimmers and Teams
                    if ($dbTable === 'Swimmer' || $dbTable === 'Team' || $dbTable === 'Meet') {
                        echo "<th>Actions</th>";
                    }
                    echo "</tr>";
                    
                    // Reset result pointer
                    $result->data_seek(0);
                    
                    // Display data
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row as $column => $value) {
                            // Format time for better readability if this is the time column and it's the Swim table
                            if ($column === 'time' && $dbTable === 'Swim') {
                                echo "<td>" . secondsToTime($value) . "</td>";
                            } else {
                                echo "<td>" . htmlspecialchars($value) . "</td>";
                            }
                        }
                        
                        // Add links to profiles
                        if ($dbTable === 'Swimmer') {
                            echo "<td><a href='swimmer_profile.php?id=" . $row['swimmerID'] . "' class='button'>View Profile</a></td>";
                        } elseif ($dbTable === 'Team') {
                            echo "<td><a href='team_profile.php?team=" . urlencode($row['teamName']) . "' class='button'>View Profile</a></td>";
                        } elseif ($dbTable === 'Meet') {
                            echo "<td><a href='meet_profile.php?name=" . urlencode($row['meetName']) . "&date=" . urlencode($row['date']) . "' class='button'>View Profile</a></td>";
                        }
                        
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                } else {
                    echo showMessage("No data found");
                }
            } // closes else for if ($dbTable === null)
        } // closes else for if ($entity === 'find_recruit')
        ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>