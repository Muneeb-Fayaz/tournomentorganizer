<?php
// /turnament-pro/update_database.php
require_once 'common/config.php';

$messages = [];
$error = false;

try {
    // Check connection from config.php
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // 1. CREATE `deposits` table if not exists
    $sql_deposits = "
    CREATE TABLE IF NOT EXISTS `deposits` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `transaction_id` varchar(255) NOT NULL,
      `status` enum('Pending','Completed','Rejected') DEFAULT 'Pending',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if ($conn->query($sql_deposits)) {
        $messages[] = "<p class='text-green-400'>✅ `deposits` table checked/created successfully.</p>";
    } else {
        throw new Exception("Error creating `deposits` table: " . $conn->error);
    }

    // 2. CREATE `withdrawals` table if not exists
    $sql_withdrawals = "
    CREATE TABLE IF NOT EXISTS `withdrawals` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `status` enum('Pending','Completed','Rejected') DEFAULT 'Pending',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if ($conn->query($sql_withdrawals)) {
        $messages[] = "<p class='text-green-400'>✅ `withdrawals` table checked/created successfully.</p>";
    } else {
        throw new Exception("Error creating `withdrawals` table: " . $conn->error);
    }
    
    // 3. CREATE `settings` table if not exists
    $sql_settings = "
    CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
     if ($conn->query($sql_settings)) {
        $messages[] = "<p class='text-green-400'>✅ `settings` table checked/created successfully.</p>";
    } else {
        throw new Exception("Error creating `settings` table: " . $conn->error);
    }
    
    // Insert default settings if they don't exist
    $conn->query("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('admin_upi_id', 'yourupi@upi')");
    $conn->query("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('admin_qr_image', NULL)");
    $messages[] = "<p class='text-yellow-400'>ℹ️ Default settings checked/inserted.</p>";


    // 4. ALTER `users` table to add `upi_id` column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM `users` LIKE 'upi_id'");
    if ($result->num_rows == 0) {
        $sql_alter_users = "ALTER TABLE `users` ADD COLUMN `upi_id` VARCHAR(255) NULL AFTER `wallet_balance`";
        if ($conn->query($sql_alter_users)) {
            $messages[] = "<p class='text-green-400'>✅ Column `upi_id` added to `users` table.</p>";
        } else {
            throw new Exception("Error adding `upi_id` column: " . $conn->error);
        }
    } else {
        $messages[] = "<p class='text-yellow-400'>ℹ️ Column `upi_id` already exists in `users` table.</p>";
    }

    $conn->close();

} catch (Exception $e) {
    $error = true;
    $messages[] = "<p class='text-red-500'>❌ Update Error: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900 text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Turnament Pro - Database Updater</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="flex items-center justify-center h-full select-none">
    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-md mx-4">
        <h1 class="text-3xl font-bold text-center mb-6">Database Updater</h1>
        <div class="space-y-2 text-sm">
            <?php foreach ($messages as $msg) { echo $msg; } ?>
        </div>
        <?php if (!$error): ?>
        <div class="mt-6 p-4 bg-green-900/50 border border-green-700 rounded-lg text-center">
            <p class="font-bold text-green-300">✅ Database schema updated successfully!</p>
            <p class="text-sm text-gray-400 mt-2">You can now safely delete this file (`update_database.php`).</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>