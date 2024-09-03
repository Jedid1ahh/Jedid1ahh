<?php
// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
require 'conn.php';

// Start the PHP session to store user-specific data
session_start();

// Retrieve the Telegram ID from the session or URL parameter
$telegram_id = $_SESSION['telegram_id'] ?? null;

// If the ID is provided in the URL, update the session and local variable
if (isset($_GET['id'])) {
    $_SESSION['telegram_id'] = $_GET['id'];
    $telegram_id = $_GET['id'];
}

// If no Telegram ID is available, stop execution
if (!$telegram_id) {
    die('Telegram ID is missing.');
}

/**
 * Function to get the current farming status for a user
 * @param mysqli $conn Database connection
 * @param int $telegram_id User's Telegram ID
 * @return array Associative array with farming_start and farming_end times
 */
function getFarmingStatus($conn, $telegram_id) {
    // Prepare the SQL query to fetch farming status
    $query = "SELECT farming_start, farming_end FROM users WHERE telegram_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $telegram_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Function to start the farming process for a user
 * @param mysqli $conn Database connection
 * @param int $telegram_id User's Telegram ID
 */
function startFarming($conn, $telegram_id) {
    $start_time = time(); // Current timestamp
    $end_time = $start_time + (3 * 60 * 60); // 3 hours later
    
    // Update the user's farming start and end times in the database
    $query = "UPDATE users SET farming_start = ?, farming_end = ? WHERE telegram_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $start_time, $end_time, $telegram_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Function to claim farmed tokens for a user
 * @param mysqli $conn Database connection
 * @param int $telegram_id User's Telegram ID
 * @return int Number of tokens claimed
 */
function claimTokens($conn, $telegram_id) {
    $farmed_tokens = 5400; // 0.5 tokens per second * 10800 seconds (3 hours)
    
    // Update the user's points and reset farming times in the database
    $query = "UPDATE users SET points = points + ?, farming_start = NULL, farming_end = NULL WHERE telegram_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $farmed_tokens, $telegram_id);
    $stmt->execute();
    $stmt->close();
    
    return $farmed_tokens;
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'start_farming':
            startFarming($conn, $telegram_id);
            echo json_encode(['status' => 'success']);
            break;
        
        case 'claim_tokens':
            $claimed_tokens = claimTokens($conn, $telegram_id);
            echo json_encode(['status' => 'success', 'claimed_tokens' => $claimed_tokens]);
            break;
        
        case 'get_status':
            $status = getFarmingStatus($conn, $telegram_id);
            echo json_encode($status);
            break;
    }
    
    exit; // Stop further execution after handling AJAX request
}

// Get the current farming status for the user
$farming_status = getFarmingStatus($conn, $telegram_id);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm $PAWS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="test-styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="images/logo.jpg" type="image/png">

    <style>
        .container {
            padding-left: 20px;  /* Adjust the value to bring the text from the left side */
            padding-right: 20px; /* Adjust the value to bring the text from the right side */
            
            /* Alternatively, you can use margin if you prefer */
            /* margin-left: 20px; */
            /* margin-right: 20px; */
        }
        
        .text-left {
            float: left;
            padding-left: 20px; /* Adjust as needed */
        }
        
        .text-right {
            float: right;
            padding-right: 20px; /* Adjust as needed */
        }

    </style>
</head>
<body>
    <div class="container text-center mt-5">
        <h1 class="invite-text">Start Farming<br>and get more PAWS</h1>
        <div style="height: 20px;"></div>
        <img src="images/logo.jpg" alt="Dog Icon" class="dog-icon">
        <div style="height: 30px;"></div>
        <p class="tap-text">Tap on the button to start!</p>
        
        <div style="height: 100px;"></div>

        <button class="btn btn-light btn-lg" id="farmingButton">Start Farming</button>
    </div>
    
    <nav class="navbar fixed-bottom navbar-dark bg-dark">
        <ul class="navbar-nav d-flex flex-row justify-content-around w-100">
            <li class="nav-item">
                <a class="nav-link" href="index.php?id=<?php echo $telegram_id; ?>"><i class="bi bi-house-fill"></i> Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="task.php?id=<?php echo $telegram_id; ?>"><i class="bi bi-list-check"></i> Tasks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="bi bi-bar-chart-fill"></i> Leaderboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="refer.php?id=<?php echo $telegram_id; ?>"><i class="bi bi-people-fill"></i> Friends</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="refer.php?id=<?php echo $telegram_id; ?>"><i class="bi bi-tree-fill"></i> Friends</a>
            </li>
        </ul>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="test-script.js"></script>

    <!-- Include jQuery for easier AJAX handling -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let farmingInterval;
            // Initialize farming start and end times from PHP
            let farmingStart = <?php echo $farming_status['farming_start'] ?? 'null'; ?>;
            let farmingEnd = <?php echo $farming_status['farming_end'] ?? 'null'; ?>;

            /**
             * Function to update the button status based on current farming state
             */
            function updateButtonStatus() {
                if (farmingStart && farmingEnd) {
                    let now = Math.floor(Date.now() / 1000);
                    if (now < farmingEnd) {
                        // Farming is in progress
                        let timeLeft = farmingEnd - now;
                        let tokensMined = ((now - farmingStart) * 0.5).toFixed(1);
                        let hours = Math.floor(timeLeft / 3600);
                        let minutes = Math.floor((timeLeft % 3600) / 60);
                        let seconds = timeLeft % 60;
                        $('#farmingButton').text(`${tokensMined} $PAWS mined so far, ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}s`);
                    } else {
                        // Farming is complete
                        $('#farmingButton').text('Claim $PAWS').removeClass('btn-primary').addClass('btn-success');
                        clearInterval(farmingInterval);
                    }
                } else {
                    // Not farming
                    $('#farmingButton').text('Start Farming').removeClass('btn-success').addClass('btn-primary');
                }
            }

            /**
             * Function to start the farming process
             */
            function startFarming() {
                $.post('', { action: 'start_farming' }, function(response) {
                    if (response.status === 'success') {
                        farmingStart = Math.floor(Date.now() / 1000);
                        farmingEnd = farmingStart + (3 * 60 * 60);
                        farmingInterval = setInterval(updateButtonStatus, 1000);
                    }
                }, 'json');
            }

            /**
             * Function to claim farmed tokens
             */
            function claimTokens() {
                $.post('', { action: 'claim_tokens' }, function(response) {
                    if (response.status === 'success') {
                        alert(`You've claimed ${response.claimed_tokens} $PAWS!`);
                        farmingStart = null;
                        farmingEnd = null;
                        updateButtonStatus();
                    }
                }, 'json');
            }

            // Event listener for the farming button
            $('#farmingButton').click(function() {
                if ($(this).text() === 'Start Farming') {
                    startFarming();
                } else if ($(this).text() === 'Claim $PAWS') {
                    claimTokens();
                }
            });

            // Initial update of button status
            updateButtonStatus();
            
            // If farming is already in progress, start the interval
            if (farmingStart && farmingEnd) {
                farmingInterval = setInterval(updateButtonStatus, 1000);
            }
        });
    </script>
</body>
</html>
