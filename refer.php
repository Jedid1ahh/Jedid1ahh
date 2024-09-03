<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Friends</title>
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
        <h1 class="invite-text">Invite friends<br>and get more PAWS</h1>
        <img src="images/logo.jpg" alt="Dog Icon" class="dog-icon">
        <p class="tap-text">Tap on the button to invite your friends</p>
        <button class="btn btn-light btn-lg" id="invite-btn">Invite friends</button>
    </div>

    <div class="friends-section mt-5">
                <h2 class="container text-start">Your Friends</h2>
                <?php
                // Enable error reporting for debugging
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
                error_reporting(E_ALL);

                // Start the session
                session_start();

                // Check if telegram_id is passed via URL
                if (isset($_GET['id'])) {
                    // Store the telegram_id in session
                    $_SESSION['telegram_id'] = $_GET['id'];
                }

                // Use the telegram_id from session
                if (isset($_SESSION['telegram_id'])) {
                    $telegram_id = $_SESSION['telegram_id'];

                    // Prepare the SQL statement to get referrals for the specific telegram_id
                    require 'conn.php';
                    $stmt = $conn->prepare("SELECT username, referral_link, points FROM users WHERE referred_by = ?");
                    $stmt->bind_param("s", $telegram_id);

                    // Execute the query
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    // Handle the error when telegram_id is not found
                    echo "Error: No Telegram ID found in session.";
                    exit;
                }

                $referralLink = isset($telegram_id) ? "https://t.me/jedidiahbot?start=" . $telegram_id : "https://t.me/jedidiahbot";

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="container friend d-flex align-items-center mb-3">';
                        echo '<div class="avatar bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3">' . substr(htmlspecialchars($row['username']), 0, 2) . '</div>';
                        echo '<span class="friend-name flex-grow-1">' . htmlspecialchars($row['username']) . '</span>';
                        echo '<span class="clouds">' . htmlspecialchars($row['points']) . ' PAWS</span>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Sorry! You Have No Referrals.</p>';
                }

                // Close the statement and connection
                $stmt->close();
                $conn->close();
                ?>
            </div>

    <!-- Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="inviteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteModalLabel">Invite friends</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <button class="btn btn-light btn-lg mb-3" id="copy-link">Copy invite link</button>
                    <p>
                    <button class="btn btn-light btn-lg" id="share-link">Share invite link</button>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-dark bg-dark fixed-bottom">
        <div class="container-fluid justify-content-around">
            <a href="index.php?id=<?php echo htmlspecialchars($telegram_id); ?>" class="text-white text-center">
                <i class="bi bi-house"></i><br>Home
            </a>
            <a href="task.php?id=<?php echo htmlspecialchars($telegram_id); ?>" class="text-white text-center">
                <i class="bi bi-list-task"></i><br>Tasks
            </a>
            <a href="lead.php?id=<?php echo htmlspecialchars($telegram_id); ?>" class="text-white text-center">
                <i class="bi bi-bar-chart"></i><br>Leaderboard
            </a>
            <a href="referrals_list.php?id=<?php echo htmlspecialchars($telegram_id); ?>" class="text-white text-center">
                <i class="bi bi-people"></i><br>Friends
            </a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="test-script.js"></script>

    <script>
        document.getElementById('copy-link').addEventListener('click', function () {
        let inviteLink = "<?php echo $referralLink; ?>";
        navigator.clipboard.writeText(inviteLink).then(function () {
            alert('Invite link copied to clipboard!');
        });
        });

        document.getElementById('share-link').addEventListener('click', function () {
        let inviteLink = "<?php echo $referralLink; ?>";
        let telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(inviteLink)}`;
        window.open(telegramUrl, '_blank');
        });
    </script>
</body>
</html>
