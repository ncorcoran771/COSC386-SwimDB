<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assuming these files handle session_start(), DB connection ($conn), auth, and helper functions
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Now includes timeToSeconds and formatTime
require_once 'includes/auth.php';

// Entity must come from GET parameters only
$entity = $_GET['entity'] ?? 'swimmer'; // Default to 'swimmer' if not specified
$user = getCurrentUser(); // Assuming this is defined in includes/auth.php

include 'includes/header.php';
include 'includes/sidebar.php';

?>

<body>
    <div class="main">
        <div class="container">
            <?php
            // Show message if any from previous actions (e.g., add/edit/delete)
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // --- Specific Swimmer Search Functionality ---
            if ($entity === 'swimmers'):
                // Initialize variables for Swimmer Search
                // Retrieve submitted data and errors from session after PRG redirect
                $error = $_SESSION['error'] ?? '';
                $submittedData = $_SESSION['submittedData'] ?? null;
                $queryResult = null; // Variable to store the search results

                // Initialize clearSession flag (for clearing session data after processing)
                $clearSession = true;

                // --- Handle Swimmer Search Form Submission (POST) ---
                if ($_SERVER["REQUEST_METHOD"] === "POST") {
                    // Capture submitted data into session
                    $_SESSION['submittedData'] = [
                        'gender' => $_POST['gender'] ?? '',
                        'states' => $_POST['states'] ?? [],
                        'event' => $_POST['event'] ?? '',
                        'minTime' => $_POST['minTime'] ?? '',
                        'maxTime' => $_POST['maxTime'] ?? ''
                    ];

                    // Redirect to prevent form resubmission (PRG pattern)
                    // Redirect back to this page with the correct entity parameter
                    header("Location: " . $_SERVER['PHP_SELF'] . "?entity=swimmers");
                    exit;
                }

                // --- Process Submitted Swimmer Search Data (after redirect) ---
                // This block runs when the page is loaded *after* a POST request (due to the redirect)
                // and $submittedData is retrieved from the session.
                if ($submittedData !== null && $conn) { // Ensure submittedData is not null (meaning a search was attempted)
                    try {
                        $gender = $submittedData['gender'] ?? '';
                        $states = $submittedData['states'] ?? [];
                        $event = $submittedData['event'] ?? '';
                        $minTimeStr = $submittedData['minTime'] ?? '';
                        $maxTimeStr = $submittedData['maxTime'] ?? '';

                        // Convert times to a comparable format (seconds)
                        // Use the timeToSeconds function from includes/functions.php
                        $minTimeSeconds = timeToSeconds($minTimeStr);
                        $maxTimeSeconds = timeToSeconds($maxTimeStr);

                        // Basic validation for time conversion
                        if ($minTimeSeconds === false || $maxTimeSeconds === false) {
                            $_SESSION['error'] = "Invalid time format or values. Please use mm:ss:ms (e.g., 01:23:45) with valid seconds/milliseconds.";
                            $clearSession = false; // Don't clear session if there's an error
                        } else {
                            // --- Build the SQL Query for Swimmer Search using prepared statements ---
                            // This query joins Swimmer and Swim tables to get relevant data
                            $query = "SELECT s.name, s.gender, s.hometown, s.team, s.powerIndex, sw.time, sw.eventName
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
                            if ($minTimeSeconds !== false) { // Check if conversion was successful
                                $query .= " AND sw.time >= ?";
                                $params[] = $minTimeSeconds;
                                $types .= "d"; // Assuming double for time in seconds
                            }

                            if ($maxTimeSeconds !== false) { // Check if conversion was successful
                                $query .= " AND sw.time <= ?";
                                $params[] = $maxTimeSeconds;
                                $types .= "d"; // Assuming double for time in seconds
                            }

                            // Optional: Add a check if no filters were applied and you want to prevent showing all swimmers/swims
                            // if (count($params) == 0) {
                            //      $_SESSION['error'] = "Please select search criteria.";
                            //      $clearSession = false;
                            // }


                            $query .= " ORDER BY sw.time ASC"; // Order results by time

                            // --- Execute the Prepared Statement ---
                            $stmt = $conn->prepare($query);

                            if ($stmt === false) {
                                // Log the error for debugging, show user-friendly message
                                error_log("Failed to prepare swimmer search statement: " . $conn->error);
                                throw new Exception('An internal error occurred. Please try again later.');
                            }

                            // Bind parameters dynamically
                            if (!empty($params)) {
                                // The ...$params syntax "unpacks" the array into arguments for bind_param
                                $stmt->bind_param($types, ...$params);
                            }

                            $executeSuccess = $stmt->execute();

                            if ($executeSuccess === false) {
                                // Log the error for debugging, show user-friendly message
                                error_log("Swimmer search query execution failed: " . $stmt->error);
                                throw new Exception('An internal error occurred during search. Please try again later.');
                            }

                            $result = $stmt->get_result();

                            if ($result === false) {
                                // Log the error for debugging, show user-friendly message
                                error_log("Swimmer search get_result failed: " . $stmt->error);
                                throw new Exception('An internal error occurred processing results. Please try again later.');
                            }

                            $queryResult = $result->fetch_all(MYSQLI_ASSOC);

                            $stmt->close();
                        } // End else (time conversion successful)

                    } catch (Exception $e) {
                        $_SESSION['error'] = $e->getMessage();
                        $clearSession = false; // Don't clear session if there's an error
                        $queryResult = null; // Clear results on error
                    }
                }

                // Clear session flash data *after* processing if no error occurred
                if ($clearSession) {
                    unset($_SESSION['error'], $_SESSION['submittedData']);
                }

                // --- Include CSS for Swimmer Search Page ---
                // Ideally, move this to a separate CSS file linked in includes/header.php
                ?>
                <style>
                    /* CSS from the first script */
                     .container {
                        /* Keep or merge styles */
                        width: 80%;
                        max-width: 900px;
                    }

                    .search-form h1 { margin-top: 0; margin-bottom: 20px; text-align: center; }
                    .search-form select, .search-form input[type="text"], .search-form input[type="number"] { width: calc(100% - 22px); padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
                    .search-form input[type="text"].time-input { /* Add specific time input styles if needed */ }
                    .search-form select[multiple] { height: 100px; }
                    .search-form input[type="submit"] { padding: 10px 15px; margin: 10px 0; border: none; border-radius: 5px; background-color: #00796b; color: white; cursor: pointer; display: block; width: 100%; font-size: 1em; }
                    .search-form input[type="submit"]:hover { background-color: #004d40; }
                    .error { color: red; font-size: 0.9em; margin-top: 5px; display: block; }
                    label { display: block; margin-top: 15px; margin-bottom: 5px; font-weight: bold; }

                    /* Table styles from the first script */
                    table { margin-top: 30px; width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    tr:hover { background-color: #e0f2f7; }
                </style>

                <div class="search-form">
                    <h1>Search Swimmers by Time</h1>
                    <?php if ($error): ?>
                        <p class="error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <form id="swimmerSearchForm" method="post" action="">
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
                                $selectedStates = $submittedData['states'] ?? [];
                                foreach ($states as $code => $name) {
                                    $selected = in_array($code, $selectedStates) ? 'selected' : '';
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
                                    $selectedEvent = $submittedData['event'] ?? '';
                                    foreach ($freestyleEvents as $e) {
                                        $selected = ($selectedEvent === $e) ? 'selected' : '';
                                        echo "<option value=\"$e\" $selected>$e</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Backstroke">
                                     <?php
                                    $backstrokeEvents = ["50y Backstroke", "100y Backstroke", "200y Backstroke"];
                                     foreach ($backstrokeEvents as $e) {
                                         $selected = ($selectedEvent === $e) ? 'selected' : '';
                                         echo "<option value=\"$e\" $selected>$e</option>";
                                     }
                                     ?>
                                </optgroup>
                                <optgroup label="Butterfly">
                                     <?php
                                    $butterflyEvents = ["50y Butterfly", "100y Butterfly", "200y Butterfly"];
                                     foreach ($butterflyEvents as $e) {
                                         $selected = ($selectedEvent === $e) ? 'selected' : '';
                                         echo "<option value=\"$e\" $selected>$e</option>";
                                     }
                                     ?>
                                </optgroup>
                                <optgroup label="Breaststroke">
                                     <?php
                                    $breaststrokeEvents = ["50y Breaststroke", "100y Breaststroke", "200y Breaststroke"];
                                     foreach ($breaststrokeEvents as $e) {
                                         $selected = ($selectedEvent === $e) ? 'selected' : '';
                                         echo "<option value=\"$e\" $selected>$e</option>";
                                     }
                                     ?>
                                </optgroup>
                                <optgroup label="IM">
                                     <?php
                                    $imEvents = ["100y IM", "200y IM", "400y IM"];
                                     foreach ($imEvents as $e) {
                                         $selected = ($selectedEvent === $e) ? 'selected' : '';
                                         echo "<option value=\"$e\" $selected>$e</option>";
                                     }
                                     ?>
                                </optgroup>
                            </select>
                        </div>

                        <div id="timeSection" style="display: none;">
                            <label for="minTime">Minimum Time (mm:ss:ms):</label>
                            <input type="text" id="minTime" name="minTime" class="time-input" placeholder="e.g., 01:23:45" required value="<?= htmlspecialchars($submittedData['minTime'] ?? '') ?>">
                            <span id="minTimeError" class="error"></span><br>
                            <label for="maxTime">Maximum Time (mm:ss:ms):</label>
                            <input type="text" id="maxTime" name="maxTime" class="time-input" placeholder="e.g., 01:23:45" required value="<?= htmlspecialchars($submittedData['maxTime'] ?? '') ?>">
                            <span id="maxTimeError" class="error"></span><br>
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
                                    <th>Event</th> <th>Time</th>
                                    <th>Profile</th> </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queryResult as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['gender']) ?></td>
                                        <td><?= htmlspecialchars($row['hometown']) ?></td>
                                        <td><?= htmlspecialchars($row['team']) ?></td>
                                        <td><?= htmlspecialchars($row['powerIndex']) ?></td>
                                         <td><?= htmlspecialchars($row['eventName']) ?></td>
                                        <td><?= htmlspecialchars(formatTime($row['time'])) ?></td> <td><a href='swimmer_profile.php?id=<?= htmlspecialchars($row['swimmerID']) ?>' class='button'>View Profile</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($submittedData !== null && empty($error)): // Only show "no results" if a search was performed and no PHP error occurred ?>
                        <p>No swimmers found matching your criteria.</p>
                    <?php endif; ?>

                    <?php
                     // --- Include JavaScript for Swimmer Search Page ---
                     // Place script tag here or in includes/footer.php if it runs after DOM is ready
                     ?>
                    <script>
                        function updateVisibility() {
                            const gender = document.getElementById('gender').value;
                            const statesSelect = document.getElementById('states');
                            const statesSection = document.getElementById('statesSection');
                            const eventSelect = document.getElementById('event');
                            const eventSection = document.getElementById('eventSection');
                            const timeSection = document.getElementById('timeSection');

                            // Check selectedOptions length for multiple select
                            const statesSelected = statesSelect && statesSelect.selectedOptions ? statesSelect.selectedOptions.length > 0 : false;
                            const eventValue = eventSelect ? eventSelect.value : '';


                            // States section visible if gender is selected
                            if (statesSection) {
                               statesSection.style.display = gender !== '' ? 'block' : 'none';
                            }

                            // Event section visible if states section is visible and states are selected
                            const isStatesSectionVisibleAndSelected = statesSection && statesSection.style.display !== 'none' && statesSelected;
                            if (eventSection) {
                                eventSection.style.display = isStatesSectionVisibleAndSelected ? 'block' : 'none';
                            }

                            // Time section visible if event section is visible and event is selected
                            const isEventSectionVisibleAndSelected = eventSection && eventSection.style.display !== 'none' && eventValue !== '';
                            if (timeSection) {
                                 timeSection.style.display = isEventSectionVisibleAndSelected ? 'block' : 'none';
                            }

                             // Required fields are handled by the HTML 'required' attribute,
                             // but ensure sections are required if visible.
                             // Note: HTML required works on form submission, not just visibility.
                             // We rely on server-side validation and the JS visibility logic
                             // to guide the user.
                        }

                        // Add event listeners only if the elements exist
                        const genderSelect = document.getElementById('gender');
                        if (genderSelect) genderSelect.addEventListener('change', updateVisibility);

                        const statesSelect = document.getElementById('states');
                        if (statesSelect) statesSelect.addEventListener('change', updateVisibility); // Use change for multi-select

                        const eventSelect = document.getElementById('event');
                        if (eventSelect) eventSelect.addEventListener('change', updateVisibility);


                        // --- Time Format Validation ---
                        function validateTimeFormat(input, errorSpan) {
                             if (input.value.trim() === '') {
                                 errorSpan.textContent = ''; // Clear error if input is empty
                                 return true; // Consider empty valid if not required
                             }
                             // Regex: starts with 1+ digits (minutes), followed by ':', 2 digits (seconds 00-59), ':', 2 digits (milliseconds 00-99)
                             const regex = /^\d+:[0-5]\d:[0-9]\d$/;
                             const isValid = regex.test(input.value);
                             errorSpan.textContent = isValid ? '' : 'Use mm:ss:ms (e.g., 01:23:45) with seconds 00-59, ms 00-99.';
                             return isValid;
                        }

                        // Add input listeners for time validation
                        const minTimeInput = document.getElementById('minTime');
                        const minTimeErrorSpan = document.getElementById('minTimeError');
                        if (minTimeInput && minTimeErrorSpan) {
                             minTimeInput.addEventListener('input', function() {
                                 validateTimeFormat(this, minTimeErrorSpan);
                             });
                        }

                        const maxTimeInput = document.getElementById('maxTime');
                        const maxTimeErrorSpan = document.getElementById('maxTimeError');
                         if (maxTimeInput && maxTimeErrorSpan) {
                            maxTimeInput.addEventListener('input', function() {
                                 validateTimeFormat(this, maxTimeErrorSpan);
                             });
                         }

                        // Add form submit listener to perform validation before submission
                        const searchForm = document.getElementById('swimmerSearchForm');
                        if (searchForm) {
                            searchForm.addEventListener('submit', function(event) {
                                let formIsValid = true;

                                // Check if time inputs are visible and validate if they are not empty
                                if (timeSection && timeSection.style.display !== 'none') {
                                    if (minTimeInput && minTimeInput.value.trim() !== '') {
                                        if (!validateTimeFormat(minTimeInput, minTimeErrorSpan)) {
                                            formIsValid = false;
                                        }
                                    }
                                    if (maxTimeInput && maxTimeInput.value.trim() !== '') {
                                         if (!validateTimeFormat(maxTimeInput, maxTimeErrorSpan)) {
                                            formIsValid = false;
                                        }
                                    }
                                    // Add check if both min and max are empty when section is visible and required
                                    // This simple validation only checks format if value is not empty.
                                    // Server-side will handle required check based on business logic.
                                }

                                if (!formIsValid) {
                                    event.preventDefault(); // Prevent form submission if validation fails
                                    alert("Please fix the errors in the form."); // Optional alert
                                }
                            });
                        }


                        // Call updateVisibility on page load to show sections based on potentially restored session data
                        // Use DOMContentLoaded to ensure elements exist
                        document.addEventListener('DOMContentLoaded', function() {
                             updateVisibility();
                        });
                    </script>

            <?php
            // --- End Specific Swimmer Search ---

            // --- Generic Table Viewer Functionality ---
            else: // This runs for entities other than 'swimmers'
                $tableMap = [
                    'conferences' => 'Conference',
                    'meets' => 'Meet',
                    'swims' => 'Swim', // This is for viewing *all* swims, not the specific swimmer search
                    'teams' => 'Team',
                    "swimmers" => "Swimmer" // This is for viewing *all* swimmers
                ];

                $dbTable = $tableMap[$entity] ?? '';

                if (empty($dbTable)) {
                    echo showMessage("Invalid table specified", true);
                    // The footer will be included below the container
                } else {

                    // Build base query based on entity
                    if ($dbTable === 'Swim') {
                        // Generic view shows more swim details linked to swimmer
                        $query = "SELECT s.eventName, s.meetName, s.meetDate, s.swimmerID,
                                    sw.name AS swimmerName, s.time
                                    FROM Swim s
                                    JOIN Swimmer sw ON s.swimmerID = sw.swimmerID WHERE 1=1";
                    } else {
                        // Generic view selects all columns for other tables
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
                        <?php elseif ($dbTable === 'Swimmer'): // Generic Swimmer Search ?>
                            <input type="text" name="name" placeholder="Search by Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            <input type="number" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                            <select name="gender">
                                <option value="">Any Gender</option>
                                <option value="M" <?= ($_POST['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                                <option value="F" <?= ($_POST['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
                            </select>
                            <input type="text" name="hometown" placeholder="Search by Hometown" value="<?= htmlspecialchars($_POST['hometown'] ?? '') ?>">
                            <input type="text" name="team" placeholder="Search by Team" value="<?= htmlspecialchars($_POST['team'] ?? '') ?>">
                            <input type="number" name="powerIndex" placeholder="Search by Power Index" value="<?= htmlspecialchars($_POST['powerIndex'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Swim'): // Generic Swim Search ?>
                            <input type="text" name="eventName" placeholder="Search by Event Name" value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>">
                            <input type="text" name="meetName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['meetName'] ?? '') ?>">
                            <input type="text" name="meetDate" placeholder="Search by Meet Date (YYYY-MM-DD)" value="<?= htmlspecialchars($_POST['meetDate'] ?? '') ?>">
                            <input type="text" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                             <input type="text" name="time" placeholder="Search by Time (partial match)" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
                        <?php elseif ($dbTable === 'Team'): ?>
                            <input type="text" name="teamName" placeholder="Search by Team Name" value="<?= htmlspecialchars($_POST['teamName'] ?? '') ?>">
                            <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                            <input type="text" name="confName" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['confName'] ?? '') ?>">
                            <input type="text" name="state" placeholder="Search by Conference State" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                        <?php endif; ?>

                        <input type="submit" value="Search">
                    </form>
                    <?php

                    // Process POST parameters for the current generic search
                    $params = [];
                    $types = "";
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                        if ($dbTable === 'Conference') {
                            if (!empty($_POST['name'])) { $query .= " AND name LIKE ?"; $params[] = '%' . $_POST['name'] . '%'; $types .= "s"; }
                            if (!empty($_POST['state'])) { $query .= " AND state = ?"; $params[] = $_POST['state']; $types .= "s"; }
                        } else if ($dbTable === 'Meet') {
                            if (!empty($_POST['name'])) { $query .= " AND meetName LIKE ?"; $params[] = '%' . $_POST['name'] . '%'; $types .= "s"; }
                            if (!empty($_POST['location'])) { $query .= " AND location = ?"; $params[] = $_POST['location']; $types .= "s"; }
                        } elseif ($dbTable === 'Swimmer') { // Generic Swimmer Filters
                            if (!empty($_POST['name'])) { $query .= " AND name LIKE ?"; $params[] = '%' . $_POST['name'] . '%'; $types .= "s"; }
                            if (!empty($_POST['swimmerID'])) { $query .= " AND swimmerID = ?"; $params[] = $_POST['swimmerID']; $types .= "i"; } // Assuming SwimmerID is INT
                            if (!empty($_POST['gender'])) { $query .= " AND gender = ?"; $params[] = $_POST['gender']; $types .= "s"; }
                            if (!empty($_POST['hometown'])) { $query .= " AND hometown LIKE ?"; $params[] = '%' . $_POST['hometown'] . '%'; $types .= "s"; }
                            if (!empty($_POST['team'])) { $query .= " AND team LIKE ?"; $params[] = '%' . $_POST['team'] . '%'; $types .= "s"; }
                            if (!empty($_POST['powerIndex'])) { $query .= " AND powerIndex = ?"; $params[] = $_POST['powerIndex']; $types .= "i"; } // Assuming powerIndex is INT
                        } else if ($dbTable === 'Swim') { // Generic Swim Filters
                             if (!empty($_POST['eventName'])) { $query .= " AND s.eventName LIKE ?"; $params[] = '%' . $_POST['eventName'] . '%'; $types .= "s"; }
                             if (!empty($_POST['meetName'])) { $query .= " AND s.meetName LIKE ?"; $params[] = '%' . $_POST['meetName'] . '%'; $types .= "s"; }
                             if (!empty($_POST['meetDate'])) { $query .= " AND s.meetDate LIKE ?"; $params[] = '%' . $_POST['meetDate'] . '%'; $types .= "s"; } // Using LIKE for partial date search
                             if (!empty($_POST['swimmerID'])) { $query .= " AND s.swimmerID = ?"; $params[] = $_POST['swimmerID']; $types .= "i"; } // Assuming swimmerID is INT
                             if (!empty($_POST['time'])) { $query .= " AND s.time LIKE ?"; $params[] = '%' . $_POST['time'] . '%'; $types .= "s"; } // Using LIKE for string search on time
                        } else if ($dbTable === 'Team') {
                            if (!empty($_POST['teamName'])) { $query .= " AND teamName LIKE ?"; $params[] = '%' . $_POST['teamName'] . '%'; $types .= "s"; }
                            if (!empty($_POST['location'])) { $query .= " AND location = ?"; $params[] = $_POST['location']; $types .= "s"; }
                            if (!empty($_POST['confName'])) { $query .= " AND confName LIKE ?"; $params[] = '%' . $_POST['confName'] . '%'; $types .= "s"; }
                            if (!empty($_POST['state'])) { $query .= " AND confState = ?"; $params[] = $_POST['state']; $types .= "s"; }
                        }
                    }

                    // Add ordering for generic views
                    if ($dbTable === 'Swim') {
                        $query .= " ORDER BY s.meetDate DESC, s.eventName";
                    } elseif ($dbTable === 'Swimmer') {
                         $query .= " ORDER BY name";
                    } elseif ($dbTable === 'Team') {
                         $query .= " ORDER BY teamName";
                    } elseif ($dbTable === 'Meet') {
                         $query .= " ORDER BY meetDate DESC";
                    } elseif ($dbTable === 'Conference') {
                         $query .= " ORDER BY name";
                    }


                    // Prepare and execute the query for generic viewer
                    $stmt = $conn->prepare($query);

                    if ($stmt === false) {
                         // Log the error for debugging, show user-friendly message
                        error_log("Failed to prepare generic query statement for entity: $entity - " . $conn->error);
                        echo showMessage("An internal error occurred. Please try again later.", true);
                    } else {
                        // Bind the parameters dynamically
                        if (!empty($params)) {
                            $bind_params = [];
                            $bind_params[] = $types;
                            for($i=0; $i<count($params); $i++){
                                $bind_params[] = &$params[$i]; // Pass by reference
                            }
                            // Use call_user_func_array for dynamic binding
                             call_user_func_array([$stmt, 'bind_param'], $bind_params);
                        }

                        $executeSuccess = $stmt->execute();

                        if ($executeSuccess === false) {
                            // Log the error for debugging, show user-friendly message
                            error_log("Generic query execution failed for entity: $entity - " . $stmt->error);
                             echo showMessage("An internal error occurred during search. Please try again later.", true);
                        } else {
                            $result = $stmt->get_result();

                            if ($result && $result->num_rows > 0) {
                                echo "<table>";

                                // Get column names from the first row
                                $firstRow = $result->fetch_assoc();
                                echo "<tr>";
                                foreach (array_keys($firstRow) as $column) {
                                    // Adjust column names for display if needed (e.g., swimmerName)
                                     $displayColumn = ($column === 'swimmerName' && $dbTable === 'Swim') ? 'Swimmer Name' : ucwords(str_replace('_', ' ', $column));
                                     echo "<th>" . htmlspecialchars($displayColumn) . "</th>";
                                }

                                // Add Actions column for Swimmers and Teams in generic view
                                if ($dbTable === 'Swimmer' || $dbTable === 'Team') {
                                    echo "<th>Actions</th>";
                                }
                                echo "</tr>";

                                // Reset result pointer
                                $result->data_seek(0);

                                // Display data
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    foreach ($row as $column => $value) {
                                        // Format time for better readability if this is the time column in generic Swim view
                                        if ($column === 'time' && $dbTable === 'Swim') {
                                            // Use the formatTime function we added, or your existing secondsToTime
                                             echo "<td>" . htmlspecialchars(formatTime($value)) . "</td>"; // Using formatTime
                                            // echo "<td>" . htmlspecialchars(secondsToTime($value)) . "</td>"; // If secondsToTime works like formatTime
                                        } else {
                                            echo "<td>" . htmlspecialchars($value) . "</td>";
                                        }
                                    }

                                    // Add links to profiles in generic view
                                    if ($dbTable === 'Swimmer') {
                                        echo "<td><a href='swimmer_profile.php?id=" . urlencode($row['swimmerID']) . "' class='button'>View Profile</a></td>";
                                    } elseif ($dbTable === 'Team') {
                                        echo "<td><a href='team_profile.php?team=" . urlencode($row['teamName']) . "' class='button'>View Profile</a></td>";
                                    }

                                    echo "</tr>";
                                }

                                echo "</table>";
                            } else {
                                echo showMessage("No data found");
                            }
                        } // End else (execute success)
                        $stmt->close();
                    } // End else (prepare success)
                } // End else (dbTable is valid)

            endif; // End specific swimmer search conditional

            // Close the database connection at the end of the script (assuming $conn is available)
            if ($conn) {
                $conn->close();
            }

            // Footer included outside the conditional block so it's always present
            include 'includes/footer.php';
            ?>
        </div>
    </div>
</body>
</html>
