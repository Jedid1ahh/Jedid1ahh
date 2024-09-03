<?php
// user_info.php

require 'conn.php';

$telegram_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Store the telegram_id in the session
$_SESSION['telegram_id'] = $telegram_id;

if ($telegram_id) {
    $user_query = $conn->prepare("
        SELECT username, points, last_reward_day, referrals, referral_bonus, tasks_bonus 
        FROM users 
        WHERE telegram_id = ?
    ");
    $user_query->bind_param('i', $telegram_id);
    $user_query->execute();
    $user_result = $user_query->get_result();

    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
        $username = htmlspecialchars($user['username']);
        $points = intval($user['points']);
        $last_reward_day = intval($user['last_reward_day']);
        $referrals = intval($user['referrals']);
        $referral_bonus = intval($user['referral_bonus']);
        $task_bonus = intval($user['tasks_bonus']);

        $current_day = ($last_reward_day + 1) % 7 + 1;
        $reward_points = getDailyReward($current_day);

        // Update user's points and last reward day
        $conn->query("
            UPDATE users 
            SET points = points + $reward_points, last_reward_day = $current_day 
            WHERE telegram_id = $telegram_id
        ");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAWPRINT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/lead.css">
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>

<body>
    <div class="confetti" id="confetti"></div>
    <div class="container text-center mt-5">
        <!-- Title with white text and sky blue shadow -->
        <h1 class="display-3 clout-title">Welcome <?php echo $username?></h1>
        <!-- Small rounded logo above Clouds text -->
        <img src="images/logo.jpg" alt="Logo" class="logo-image rounded-circle">
        <!-- Formatted Clouds Text -->
        <p class="fs-4 fs-md-3 text-white">
        <?php echo ($points + $reward_points) ?><br>
            <span class="clouds-text">PAWS</span>
        </p>
        <!-- Connect Wallet button linking to another tab -->
        <div id="ton-connect" class="btn btn-outline-light mb-3">
        </div>

        <div id="wallet-address"></div>
        <div class="card bg-dark text-white p-3 mt-3">
            <h5 class="card-title">PAW COMMUNITY</h5>
            <p class="card-text">Home for the PAW Lovers</p>
            <!-- Join button linking to Telegram -->
            <a href="https://t.me/your-telegram-channel" target="_blank" class="btn btn-light">Join</a>
        </div>
    </div>

    <div class="container mt-4">
        <h4 class="text-white">Your rewards</h4>
        <ul class="list-unstyled">
            <li class="mb-3 text-white">
                <i class="bi bi-check-circle"></i> Total Points:<?php echo ($points + $referral_bonus + $task_bonus) ?>
            </li>
            <li class="mb-3 text-white">
                <i class="bi bi-people"></i> Invited friends (<?php echo $referrals ?>)
                    
            </li>
            <li class="mb-5 text-white">
                <i class="bi bi-card-checklist"></i> Tasks: <?php echo $task_bonus; ?>
            </li>
        </ul>
    </div>

    <div style="height: 80px;"></div>

    <!-- Footer with Bootstrap icons -->
    <nav class="navbar navbar-dark bg-dark fixed-bottom">
        <div class="container-fluid justify-content-around">
            <a href="#" class="text-white text-center">
                <i class="bi bi-house"></i><br>Home
            </a>
            <a href="task.php?id=<?php echo $telegram_id; ?>" class="text-white text-center">
                <i class="bi bi-list-task"></i><br>Tasks
            </a>
            <a href="lead.php?id=<?php echo $telegram_id; ?>" class="text-white text-center">
                <i class="bi bi-bar-chart"></i><br>Leaderboard
            </a>
            <a href="refer.php?id=<?php echo $telegram_id; ?>" class="text-white text-center">
                <i class="bi bi-people"></i><br>Friends
            </a>
        </div>
    </nav>

    <?php

} else {
    echo "User not found.";
}

$user_query->close();
} else {
echo "Invalid request.";
}

$conn->close();

// Function to get daily reward based on the day
function getDailyReward($day) {
$rewards = [500, 700, 1000, 1500, 2000, 5000, 10000];
return $rewards[$day - 1];
}
    ?>
    <!-- Scroll button for smaller screens -->
    <button id="scrollBtn" class="btn btn-outline-light scroll-top-btn" onclick="scrollToTop()">
        <i class="bi bi-arrow-up-circle-fill"></i>
    </button>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/script.js"></script>
    <script src="https://unpkg.com/@tonconnect/ui@0.0.9/dist/tonconnect-ui.min.js"></script>


    <script>
        const tonConnectUI = new TON_CONNECT_UI.TonConnectUI({
            manifestUrl: 'https://b511d4b67f0c901ab4b29ac35c6152d5.serveo.net/tonconnect-manifest.json',
            buttonRootId: 'ton-connect'
        });

        tonConnectUI.uiOptions = {
            twaReturnUrl: 'https://b511d4b67f0c901ab4b29ac35c6152d5.serveo.net'
        };

        //const transaction = {
          //  messages: [
                //{
                    //address: "0:UQAv6iWD2coRO15RPxpsPOeFHUTA2wuwBBlwceyazFS2ADhl",
                    //amount: "20000000"
                //}
            //]
        //}

       // const result = await tonConnectUI.sendTransaction(transaction)

        //const walletsList = await TonConnectUI.getWallets();

       // const currentWallet = tonConnectUI.wallet;
        //const currentWalletInfo = tonConnectUI.walletInfo;
        //const currentAccount = tonConnectUI.account;
        //const currentIsConnectedStatus = tonConnectUI.connected;
        //async function connectToWallet(){
          //  const connectedWallet = await tonConnectUI.connectWallet();
            // Do somethingwith connected wallet if needed
            //console.log(connectedWallet);
        //}

        // Call the function
       // connectToWallet().catch(error => {
         //   console.error("Error connecting to wallet:", error);
        //});
    </script>
</body>
</html>