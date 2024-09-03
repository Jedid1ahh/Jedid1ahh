<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conn.php';

// Start the session
session_start();
$telegram_id = $_SESSION['telegram_id'];

// Check if telegram_id is passed via URL
if (isset($_GET['id'])) {
    // Store the telegram_id in session
    $_SESSION['telegram_id'] = $_GET['id'];
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/lead.css">
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>
<body>
    <?php
        // Query to select the user with the most points
        $top_referrer_query = $conn->query("
            SELECT username, points, referral_bonus, referrals 
            FROM users 
            ORDER BY (points + referral_bonus) DESC 
            LIMIT 1
        ");
        if ($top_referrer_query->num_rows > 0) {
            $user = $top_referrer_query->fetch_assoc();
            $total = intval($user['points']) + intval($user['referral_bonus']);
    ?>
    <div id="content">
        <div class="container mt-4">
            <h2 class="text-center text-white">PAWS Leaderboard</h2>
            <div class="user-card mt-3 p-3">
                <div class="d-flex align-items-center">
                    <?php
                        if ($telegram_id) {
                            $user_query = $conn->prepare("
                                SELECT username, points, referrals, referral_bonus 
                                FROM users 
                                WHERE telegram_id = ?
                            ");
                            $user_query->bind_param('i', $telegram_id);
                            $user_query->execute();
                            $user_result = $user_query->get_result();
                        
                            if ($user_result->num_rows > 0) {
                                $userb = $user_result->fetch_assoc();
                                $usernameb = htmlspecialchars($userb['username']);
                                $points = intval($userb['points']);
                                $referrals = intval($userb['referrals']);
                                $referral_bonus = intval($user['referral_bonus']);
                                $totalb = intval($userb['points']) + intval($userb['referral_bonus']);
                    ?>
                    <div class="avatar"><?php echo substr(htmlspecialchars($userb['username']), 0, 2); ?></div>
                    <div class="ml-3">
                        <h5 class="mb-0"><?php echo $usernameb;?></h5>
                        <p class="mb-0"><?php echo $total; ?> PAWS</p>
                    </div>
                    <?php
                        }else{
                            echo 'User Not Found!';
                        }
                        }
                    ?>
                    <div class="ml-auto">
                        <?php

                            if (isset($_SESSION['telegram_id'])) {
                            $telegram_id = $_SESSION['telegram_id'];

                            // For MySQL 8.0 or later
                            $query = "
                                WITH RankedUsers AS (
                                SELECT telegram_id, (points + referral_bonus) AS total,
                                ROW_NUMBER() OVER (ORDER BY (points + referral_bonus) DESC) AS position
                                FROM users
                                )
                                SELECT position
                                FROM RankedUsers
                                WHERE telegram_id = ?;
                            ";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param('i', $telegram_id); // Assuming telegram_id is an integer
                            $stmt->execute();
                            $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                $ow = $result->fetch_assoc();
                        ?>
                        <span class="user-rank">#<?php echo intval($ow['position']);?></span>
                    </div>
                    <?php
                        } else {
                            echo "<p>User not found or an error occurred.</p>";
                        }
                        } else {
                        echo "<p>No user logged in.</p>";
                        }
                    ?>
                </div>
            </div>
            <?php
                $total_users_query = $conn->query("SELECT COUNT(*) AS total_users FROM users");
                if ($total_users_query->num_rows > 0) {
                    $row = $total_users_query->fetch_assoc();
            ?>
            <p class="text-center text-white mt-4"><?php  echo htmlspecialchars($row['total_users']);?> Community Members</p>
            <?php
                } else {
                    echo "<p>No users found.</p>";
                }
            ?>
            <ul class="list-group">
                <li class="list-group-item d-flex align-items-center">
                    <div class="badge-circle bg-primary">
                        <?php
                            echo substr(htmlspecialchars($user['username']), 0, 2);
                        ?>
                    </div>
                    <div class="ml-3 flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($user['username']);?></h6>
                        <?php 
                            }else{
                            echo '<p>No Users Found! </p>';
                            }
                        ?>
                        <p class="mb-0"><?php echo htmlspecialchars($total) ?> PAWS</p>
                    </div>
                    <div class="ml-auto text-right">
                        <i class="bi bi-trophy-fill text-warning"></i>
                    </div>
                </li>
                <?php
                    $next_highest_referrals_query = $conn->query("
                        SELECT username, points, referral_bonus, referrals
                        FROM users
                        WHERE (points + referral_bonus) < (SELECT MAX(points + referral_bonus) FROM users)
                        ORDER BY (points + referral_bonus) DESC
                    LIMIT 1
                    ");
                ?>
                <?php
                    if ($next_highest_referrals_query->num_rows > 0) {
                        $usern = $next_highest_referrals_query->fetch_assoc();
                        $totaln = intval($usern['points']) + intval($usern['referral_bonus']);
                ?>
                <li class="list-group-item d-flex align-items-center">
                    <div class="badge-circle bg-success">
                        <?php
                            echo substr(htmlspecialchars($usern['username']), 0, 2);
                        ?>
                    </div>
                    <div class="ml-3 flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($usern['username']);?></h6>
                        <p class="mb-0"><?php echo htmlspecialchars($totaln) ?> PAWS</p>
                    </div>
                    <?php
                        }else{
                            echo "None";
                        }
                    ?>
                    <div class="ml-auto text-right">
                        <i class="bi bi-trophy-fill text-secondary"></i>
                    </div>
                </li>
                <?php
                    // For MySQL 8.0 or later
                    $query = "
                        WITH RankedUsers AS (
                        SELECT username, points, referral_bonus, 
                        (points + referral_bonus) AS total,
                        ROW_NUMBER() OVER (ORDER BY (points + referral_bonus) DESC) AS position
                        FROM users
                        )
                        SELECT username, points, referral_bonus, total, position
                        FROM RankedUsers
                        WHERE position BETWEEN 3 AND 20
                        ORDER BY position
                    ";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                    while ($check = $result->fetch_assoc()) {
                    $checkn = intval($check['points']) + intval($check['referral_bonus']);
                ?>
                <li class="list-group-item d-flex align-items-center">
                    <div class="badge-circle bg-success"><?php echo substr(htmlspecialchars($check['username']), 0, 2);?></div>
                    <div class="ml-3 flex-grow-1">
                        <h6 class="mb-0"><?php echo htmlspecialchars($check['username']);?></h6>
                        <p class="mb-0"><?php echo htmlspecialchars($checkn) ?> PAWS</p>
                    </div>
                    <div class="ml-auto text-right">
                        <span class="user-rank">#<?php echo intval($check['position']); }?></span>
                    </div>
                    <?php 
                        }else{
                            echo "No User Found";
                        }
                    ?>
                </li>
            </ul>
            <div style="height: 100px;"></div>
            <!-- Footer Icons-->
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
        </div>
    </div>
   <!-- Scroll button for smaller screens -->
    <button id="scrollBtn" class="btn btn-outline-light scroll-top-btn" onclick="scrollToTop()">
        <i class="bi bi-arrow-up-circle-fill"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/lead.js"></script>
</body>
</html>