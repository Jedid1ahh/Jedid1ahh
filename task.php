<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conn.php';

// Start the session
session_start();
$telegram_id = $_SESSION['telegram_id'] ?? null;

if (isset($_GET['id'])) {
    $_SESSION['telegram_id'] = $_GET['id'];
    $telegram_id = $_GET['id'];
}

if (!$telegram_id) {
    die('Telegram ID is missing.');
}

// Fetch pending tasks for the user
$pending_tasks_query = "
    SELECT t.* 
    FROM tasks t 
    LEFT JOIN user_tasks ut ON t.id = ut.task_id AND ut.user_id = ?
    WHERE ut.task_id IS NULL";
$stmt = $conn->prepare($pending_tasks_query);
$stmt->bind_param("i", $telegram_id);
$stmt->execute();
$pending_tasks_result = $stmt->get_result();

// Fetch completed tasks for the user
$completed_tasks_query = "
    SELECT t.* 
    FROM tasks t 
    JOIN user_tasks ut ON t.id = ut.task_id 
    WHERE ut.user_id = ? AND ut.status = 'done'";
$stmt = $conn->prepare($completed_tasks_query);
$stmt->bind_param("i", $telegram_id);
$stmt->execute();
$completed_tasks_result = $stmt->get_result();

// Define logo paths
$platform_logos = [
    'Twitter' => 'images/x.png',
    'Facebook' => 'images/facebook.png',
    'Instagram' => 'images/instagram.jpg',
    'Telegram' => 'images/telegram.png'
];


// Fetch user's total points
$points_query = "SELECT IFNULL(tasks_bonus, 0) + IFNULL(referral_bonus, 0) + IFNULL(points, 0) as total_points FROM users WHERE telegram_id = ?";
$stmt = $conn->prepare($points_query);
$stmt->bind_param("i", $telegram_id);
$stmt->execute();
$points_result = $stmt->get_result();
$user_points = $points_result->fetch_assoc()['total_points'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PAW Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/task-styles.css">
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>
<body>
    <div id="main-content" class="container text-center mt-5">
        <div class="container mt-5">
            
            <?php while ($task = $pending_tasks_result->fetch_assoc()) : ?>
                <h2>Pending Tasks</h2>
            <ul class="list-group task-list">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="task-details">
                        <?php if (isset($platform_logos[$task['platform']])): ?>
                        <img src="<?= $platform_logos[$task['platform']] ?>" alt="<?= htmlspecialchars($task['platform']) ?>" class="task-icon">
                        <?php endif; ?>
                        <div>
                            <p class="task-title"><?= htmlspecialchars($task['title']); ?></p>
                            <p class="task-subtitle"> + <?= htmlspecialchars($task['points']); ?> PAWS</p>
                        </div>
                    </div>
                    <button onclick="startTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['link'], ENT_QUOTES); ?>')" id="start_<?= $task['id'] ?>" class="btn btn-secondary btn-sm">Start</button>
                </li>
                <p id="status_<?= $task['id'] ?>"></p>
            </ul>
            <?php endwhile; ?>
            
            <?php while ($task = $completed_tasks_result->fetch_assoc()) : ?>
            <ul class="list-group task-list">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="task-details">
                        <?php if (isset($platform_logos[$task['platform']])): ?>
                        <img src="<?= $platform_logos[$task['platform']] ?>" alt="<?= htmlspecialchars($task['platform']) ?>" class="task-icon">
                        <?php endif; ?>
                        <div>
                            <p class="task-title"><?= htmlspecialchars($task['title']); ?></p>
                            <p class="task-subtitle"> + <?= htmlspecialchars($task['points']); ?> PAWS</p>
                        </div>
                    </div>
                    <button disabled id="start_<?= $task['id'] ?>" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Success
                    </button>
                </li>
            </ul>
            <?php endwhile; ?>
        </div>
    </div>

    <div style="height: 100px;"></div>

    <!-- Footer with Bootstrap icons -->
    <nav class="navbar navbar-dark bg-dark fixed-bottom">
        <div class="container-fluid justify-content-around">
            <a href="index.php?id=<?php echo $telegram_id; ?>" class="text-white text-center">
                <i class="bi bi-house"></i><br>Home
            </a>
            <a href="#" class="text-white text-center">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function startTask(taskId, taskLink) {
        // Open the task link
        window.open(taskLink, '_blank');
        
        // Start the verification countdown
        let countdown = 5; // seconds
        const statusElement = document.getElementById(`status_${taskId}`);
        const startButton = document.getElementById(`start_${taskId}`);
        
        statusElement.innerHTML = `Verifying Task... ${countdown}s remaining`;
        startButton.disabled = true;
        
        const intervalId = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(intervalId);
                verifyTask(taskId);
            } else {
                statusElement.innerHTML = `Verifying Task... ${countdown}s remaining`;
            }
        }, 1000); // Update every second
    }

    function verifyTask(taskId) {
        const statusElement = document.getElementById(`status_${taskId}`);
        const startButton = document.getElementById(`start_${taskId}`);
        
        // Send verification request to the server with CSRF token
        fetch(`verify_task.php?task_id=${taskId}&user_id=<?php echo $telegram_id; ?>&csrf_token=<?php echo $csrf_token; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.innerHTML = "<span class='text-success'>Task completed successfully!</span>";
                    startButton.innerHTML = "<i class='bi bi-check-circle'></i> Success";
                    startButton.classList.remove('btn-secondary');
                    startButton.classList.add('btn-success');
                    startButton.disabled = true;
                    
                    // Move task to Completed Tasks section
                    const taskElement = startButton.closest('li');
                    document.querySelector('.container:nth-child(2) .task-list').appendChild(taskElement);

                    // Update total points
                    const totalPointsElement = document.querySelector('h2');
                    const currentPoints = parseInt(totalPointsElement.textContent.match(/\d+/)[0]);
                    totalPointsElement.textContent = `Your Total Points: ${currentPoints + data.points} PAWS`;
                } else {
                    statusElement.innerHTML = `<span class='text-danger'>${data.message}</span>`;
                    startButton.innerText = "Start Again";
                    startButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error verifying task:', error);
                statusElement.innerHTML = "<span class='text-danger'>Verification failed. Please try again.</span>";
                startButton.innerText = "Start Again";
                startButton.disabled = false;
            });
    }
    </script>
</body>
</html>