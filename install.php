<?php
// /turnament-pro/install.php

// --- Configuration ---
$db_host = 'sql201.infinityfree.com';
$db_user = 'if0_40088444';
$db_pass = 'Muneeb221106';
$db_name = 'if0_40088444_tournament';
$admin_user = 'admin';
$admin_pass = 'admin123';

// --- Installation Logic ---
$message = '';
$error = false;

try {
    // 1. Connect to MySQL Server (without selecting a database yet)
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // 2. Create Database if it doesn't exist
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql_create_db)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    $message .= "<p class='text-green-400'>✅ Database '$db_name' created or already exists.</p>";

    // 3. Select the new database
    $conn->select_db($db_name);

    // 4. SQL Schema for all tables (Idempotent: CREATE TABLE IF NOT EXISTS)
    $sql_schema = "
    CREATE TABLE IF NOT EXISTS `admin` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(100) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password` varchar(255) NOT NULL,
      `wallet_balance` decimal(10,2) DEFAULT 0.00,
      `upi_id` varchar(255) DEFAULT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `tournaments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `game_name` varchar(255) NOT NULL,
      `entry_fee` decimal(10,2) NOT NULL,
      `prize_pool` decimal(10,2) NOT NULL,
      `match_time` datetime NOT NULL,
      `room_id` varchar(255) DEFAULT NULL,
      `room_password` varchar(255) DEFAULT NULL,
      `commission_percentage` int(11) DEFAULT 0,
      `status` enum('Upcoming','Running','Completed') DEFAULT 'Upcoming',
      `winner_id` int(11) DEFAULT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `participants` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `tournament_id` int(11) NOT NULL,
      `joined_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_tournament` (`user_id`,`tournament_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `transactions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `type` enum('credit','debit') NOT NULL,
      `description` varchar(255) NOT NULL,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    CREATE TABLE IF NOT EXISTS `deposits` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `transaction_id` varchar(255) NOT NULL,
      `status` enum('Pending','Completed','Rejected') DEFAULT 'Pending',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `withdrawals` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `status` enum('Pending','Completed','Rejected') DEFAULT 'Pending',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (!$conn->multi_query($sql_schema)) {
        throw new Exception("Error creating tables: " . $conn->error);
    }
    // Clear results of multi_query
    while ($conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
    $message .= "<p class='text-green-400'>✅ All tables created successfully or already exist.</p>";

    // 5. Insert Default Admin if not exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt_insert = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt_insert->bind_param("ss", $admin_user, $hashed_password);
        if ($stmt_insert->execute()) {
            $message .= "<p class='text-green-400'>✅ Default admin user created.</p>";
        } else {
            throw new Exception("Error creating admin user: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    } else {
        $message .= "<p class='text-yellow-400'>ℹ️ Admin user already exists. Skipped creation.</p>";
    }
    $stmt->close();
    
    // 6. Insert default settings
    $conn->query("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('admin_upi_id', 'yourupi@upi')");
    $conn->query("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('admin_qr_image', NULL)");
    $message .= "<p class='text-green-400'>✅ Default settings inserted.</p>";


    $conn->close();

    // 7. Redirect on success
    header("Refresh: 5; url=admin/login.php");
    $message .= "<p class='text-blue-400 mt-4'>Installation successful! Redirecting to admin login in 5 seconds...</p>";

} catch (Exception $e) {
    $error = true;
    $message = "<p class='text-red-500'>❌ Installation Error: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Turnament Pro - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="flex items-center justify-center h-full select-none">
    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-md mx-4">
        <h1 class="text-3xl font-bold text-center mb-6">Turnament Pro Installer</h1>
        <div class="space-y-2 text-sm">
            <?php echo $message; ?>
        </div>
        <?php if ($error): ?>
        <div class="mt-4 text-center">
            <p>Please check your database credentials in `install.php` and try again.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>