<?php
session_start();

// --- Database Connection ---
// Added error handling for the database connection
$conn = new mysqli('localhost', 'ncorcoran1', 'ncorcoran1', 'athleticsRecruitingDB');

if ($conn->connect_error) {
    // Log the error instead of displaying it directly on a live site
    error_log("Database Connection failed: " . $conn->connect_error);
    // Display a user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// --- Initialize Variables ---
$user = $_SESSION['user'] ?? 'Guest';
$error = $_SESSION['error'] ?? '';
$submittedData = $_SESSION['submittedData'] ?? null;
$queryResult = null; // Variable to store the results

// Clear session flash data *before* handling POST, but after retrieving it
// Initialize clearSession flag
$clearSession = true;

// --- Function to Convert mm:ss:ms to seconds (float) ---
// This assumes the database stores time as seconds or can compare floats.
// You might need to adjust this based on your actual database schema.
// It assumes 'ms' is hundredths of a second based on standard swim timing.
function timeToSeconds($timeStr) {
    if (empty($timeStr)) {
        return 0.0; // Or null, depending on how you want to handle empty input
    }
    $parts = explode(':', $timeStr);
    // Expecting 'mm:ss:ms' -> 3 parts
    if (count($parts) !== 3) {
        // Invalid format
        return false; // Indicate an invalid conversion
    }
    $minutes = (int)$parts[0];
    $seconds = (int)$parts[1];
    // Assuming milliseconds are stored as hundredths (e.g., 45 means 0.45 seconds)
    $milliseconds = (int)$parts[2];

    // Basic validation: seconds and milliseconds parts should be 0-99
    if ($seconds < 0 || $seconds > 59 || $milliseconds < 0 || $milliseconds > 99) {
         return false; // Indicate invalid values
    }

    return ($minutes * 60) + $seconds + ($milliseconds / 100.0);
}

function formatTime($totalSeconds) {
    // Ensure input is numeric and non-negative
    if (!is_numeric($totalSeconds) || $totalSeconds < 0) {
        return "N/A"; // Or some other indicator for invalid data
    }

    // Get the whole number of seconds
    $wholeSeconds = floor($totalSeconds);

    // Get the milliseconds part (as hundredths)
    // Calculate the fractional part, multiply by 100, and round to nearest integer
    $milliseconds = round(($totalSeconds - $wholeSeconds) * 100);

    // Calculate minutes
    $minutes = floor($wholeSeconds / 60);

    // Calculate remaining seconds after extracting minutes
    $seconds = $wholeSeconds % 60;

    // Format the output string as mm:ss:ms
    // Use sprintf with %02d to pad with leading zeros if needed
    return sprintf("%02d:%02d:%02d", $minutes, $seconds, $milliseconds);
}

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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Process Submitted Data (after redirect) ---
// This block runs when the page is loaded *after* a POST request (due to the redirect)
if ($submittedData !== null && $conn) { // Ensure submittedData is not null (meaning a search was attempted)
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
             $_SESSION['error'] = "Invalid time format or values. Please use mm:ss:ms (e.g., 01:23:45) with valid seconds/milliseconds.";
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

            // Ensure at least one filter was applied beyond the base JOIN, otherwise the query is too broad
            // (Optional: You might remove this if you want to allow searches with no filters)
             if (count($params) == 0 && (empty($gender) && empty($states) && empty($event) && empty($minTimeStr) && empty($maxTimeStr))) {
                 // This case should theoretically not happen if inputs are required and JS works,
                 // but as a server-side check:
                 // throw new Exception("Please select criteria to search.");
                 // Or just let the empty result set show.
             }


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
        $_SESSION['error'] = $e->getMessage();
        $clearSession = false; // Don't clear session if there's an error
    }
}

// Clear session flash data *after* processing if no error occurred
if ($clearSession) {
    unset($_SESSION['error'], $_SESSION['submittedData']);
}

// Close the database connection at the end of the script
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Search Swimmer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e0f7fa;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 50px;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%; /* Adjusted width for potentially wider tables */
            max-width: 900px; /* Added max-width */
            margin-bottom: 50px; /* Add space at the bottom */
        }


        .search-form h1 {
            margin-top: 0; /* Remove default h1 top margin */
            margin-bottom: 20px;
            text-align: center;
        }

        .search-form select,
        .search-form input[type="text"] {
            width: calc(100% - 22px); /* Adjust width considering padding and border */
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }

         .search-form input[type="text"].time-input { /* Added class for time inputs */
             width: calc(50% - 16px); /* Adjust width for two inputs side-by-side if needed, currently full width */
         }


        .search-form select[multiple] {
            height: 100px;
        }

        .search-form input[type="submit"] {
            padding: 10px 15px;
            margin: 10px 0; /* Adjust margin */
            border: none;
            border-radius: 5px;
            background-color: #00796b;
            color: white;
            cursor: pointer;
            display: block; /* Make button take full width for better spacing */
            width: 100%;
            font-size: 1em;
        }

        .search-form input[type="submit"]:hover {
            background-color: #004d40;
        }

        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px; /* Add margin for spacing */
            display: block; /* Ensure it takes its own line */
        }

        label {
            display: block;
            margin-top: 15px; /* Increased margin for better spacing */
            margin-bottom: 5px; /* Add margin below label */
            font-weight: bold; /* Make labels bold */
        }

        table {
            margin-top: 30px; /* Increased margin above table */
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px; /* Increased padding */
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #e0f2f7; /* Highlight row on hover */
        }
    </style>
</head>

<body>
    <div class="container"> <div class="search-form">
            <h1>Welcome <?= htmlspecialchars($user) ?>! Search Swimmer</h1>
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
    </div>

    <script>
        function updateVisibility() {
            const gender = document.getElementById('gender').value;
            const statesSelect = document.getElementById('states');
            // Check selectedOptions length for multiple select
            const statesSelected = statesSelect && statesSelect.selectedOptions ? statesSelect.selectedOptions.length > 0 : false;
            const eventValue = document.getElementById('event').value;

            // States section visible if gender is selected
            const statesSection = document.getElementById('statesSection');
            if (statesSection) {
               statesSection.style.display = gender !== '' ? 'block' : 'none';
            }


            // Event section visible if states section is visible and states are selected
            const eventSection = document.getElementById('eventSection');
             const isStatesSectionVisibleAndSelected = statesSection && statesSection.style.display !== 'none' && statesSelected;
            if (eventSection) {
                eventSection.style.display = isStatesSectionVisibleAndSelected ? 'block' : 'none';
            }


            // Time section visible if event section is visible and event is selected
            const timeSection = document.getElementById('timeSection');
            const isEventSectionVisibleAndSelected = eventSection && eventSection.style.display !== 'none' && eventValue !== '';
            if (timeSection) {
                 timeSection.style.display = isEventSectionVisibleAndSelected ? 'block' : 'none';
            }
        }

        // Add event listeners only if the elements exist
        const genderSelect = document.getElementById('gender');
        if (genderSelect) genderSelect.addEventListener('change', updateVisibility);

        const statesSelect = document.getElementById('states');
        if (statesSelect) statesSelect.addEventListener('change', updateVisibility); // Use change for multi-select

        const eventSelect = document.getElementById('event');
        if (eventSelect) eventSelect.addEventListener('change', updateVisibility);


        function validateTimeFormat(input, errorSpan) {
            // Regex: starts with 1+ digits (minutes), followed by ':', 2 digits (seconds 00-59), ':', 2 digits (milliseconds 00-99)
            const regex = /^\d+:[0-5]\d:[0-9]\d$/;
            const isValid = regex.test(input.value);
            errorSpan.textContent = isValid ? '' : 'Please use mm:ss:ms format (e.g., 01:23:45) with seconds 00-59 and ms 00-99.';
            return isValid;
        }

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


         // Call updateVisibility on page load to show sections based on potentially restored session data
        updateVisibility();
    </script>
</body>
</html>