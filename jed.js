const TelegramBot = require('node-telegram-bot-api');
const mysql = require('mysql');
const token = '7320246514:AAHJ4qiwFsYcVkDN5iV7Xhic7gR1-snIMEk';  // Replace with your actual bot token
const bot = new TelegramBot(token, { polling: true });

// Database connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'telegram'
});

db.connect(err => {
    if (err) throw err;
    console.log('Connected to database');
});

// Helper function to update user points
function updateUserPoints(chatId, points, callback) {
    db.query('UPDATE users SET points = points + ? WHERE telegram_id = ?', [points, chatId], callback);
}

// Helper function to get daily reward based on day
function getDailyReward(day) {
    const rewards = [500, 700, 1000, 1500, 2000, 5000, 10000];
    return rewards[(day - 1) % 7];
}

// Helper function to check if a user has already claimed the reward for the day
function hasClaimedDailyReward(lastRewardTimestamp) {
    const currentDate = new Date();
    const lastRewardDate = new Date(lastRewardTimestamp);

    return currentDate.toDateString() === lastRewardDate.toDateString();
}

// Handle the /start command
bot.onText(/\/start(?: (.+))?/, (msg, match) => {
    const chatId = msg.chat.id;
    const username = msg.from.username || 'Unknown'; // Handle possible null username
    const referredBy = match[1] ? match[1] : null; // Referral code, if provided

    // Check if the user already exists
    db.query('SELECT * FROM users WHERE telegram_id = ?', [chatId], (err, results) => {
        if (err) throw err;

        if (results.length === 0) {
            // New user, register them
            const referralCode = chatId; // Use chatId as a unique referral code
            const referralLink = `https://t.me/Avilala_bot?start=${referralCode}`;
            const newUser = {
                telegram_id: chatId,
                username: username,
                referral_link: referralLink,
                referred_by: referredBy,
                points: 2000,  // Initial points for new user
                last_reward_day: 0, // Day tracking for daily rewards
                last_reward_timestamp: null // Track last reward timestamp
            };

            db.query('INSERT INTO users SET ?', newUser, (err, result) => {
                if (err) throw err;

                if (referredBy) {
                    // Save the referral relationship
                    db.query('INSERT INTO referrals (user_id, referred_user_id) VALUES (?, ?)', [referredBy, chatId], (err) => {
                        if (err) throw err;

                        // Notify the referrer and update their points
                        updateUserPoints(referredBy, 10000, (err) => {
                            if (err) throw err;
                            bot.sendMessage(referredBy, `🎉 ${username} has registered using your referral link! You've earned 10,000 points.`);
                        });
                    });
                }

                // Send welcome message and menu after inserting the user into the database
                const message = `
Hey @${username}! Welcome to Blum! 🌟
Your go-to app for crypto trading - all the cool coins and tokens, right in your pocket! 📱

Now we're rolling out our Telegram mini app! Start farming points now, and who knows what cool stuff you'll snag with them soon! 🚀

Got friends? Bring 'em in! The more, the merrier! 🌱

Remember: Blum is where growth thrives and endless opportunities bloom! 🌼
Your referral link: ${referralLink}
`;

                const options = {
                    reply_markup: {
                        inline_keyboard: [
                            [{ text: "🚀 Play 👊", web_app: { url: `https://yourdomain.serveo.net/Clout/index.php?id=${chatId}` } }],
                            [{ text: "📢 Join community 🔥", url: "https://t.me/joinchat/community-link" }],
                            [{ text: "🐦 Twitter", url: "https://twitter.com/your_twitter_link" }],
                            [{ text: "🤔 How it works", web_app: { url: "https://yourdomain.serveo.net/how-it-works" } }]
                        ]
                    }
                };

                bot.sendMessage(chatId, message, options);
            });
        } else {
            // User already exists, just send the welcome message and menu
            const existingUser = results[0];
            const message = `
Hey @${username}! Welcome back to Blum! 🌟

Your referral link: ${existingUser.referral_link}
`;

            const options = {
                reply_markup: {
                    inline_keyboard: [
                        [{ text: "🚀 Play 👊", web_app: { url: `https://yourdomain.serveo.net/Clout/index.php?id=${chatId}` } }],
                        [{ text: "📢 Join community 🔥", url: "https://t.me/joinchat/community-link" }],
                        [{ text: "🐦 Twitter", url: "https://twitter.com/your_twitter_link" }],
                        [{ text: "🤔 How it works", web_app: { url: "https://yourdomain.serveo.net/how-it-works" } }]
                    ]
                }
            };

            bot.sendMessage(chatId, message, options);
        }
    });
});

// Handle "Play" button
bot.on('callback_query', (query) => {
    const chatId = query.message.chat.id;

    db.query('SELECT points, last_reward_day, last_reward_timestamp FROM users WHERE telegram_id = ?', [chatId], (err, results) => {
        if (err) throw err;

        const user = results[0];
        const lastRewardDay = user.last_reward_day;
        const lastRewardTimestamp = user.last_reward_timestamp;

        // Check if the user has already claimed today's reward
        if (!hasClaimedDailyReward(lastRewardTimestamp)) {
            const currentDay = (lastRewardDay + 1) % 7 + 1; // Calculate the next reward day in the cycle
            const rewardPoints = getDailyReward(currentDay);

            // Update points, reward day, and last reward timestamp
            db.query('UPDATE users SET points = points + ?, last_reward_day = ?, last_reward_timestamp = NOW() WHERE telegram_id = ?', 
                [rewardPoints, currentDay, chatId], (err) => {
                if (err) throw err;

                bot.sendMessage(chatId, `🎁 You've received your daily reward of ${rewardPoints} points!`);
            });
        } else {
            bot.sendMessage(chatId, "⏳ You've already claimed today's reward. Come back tomorrow for more points!");
        }
    });
});

// Start the bot
console.log('Bot is running...');

require('events').EventEmitter.defaultMaxListeners = 25;
