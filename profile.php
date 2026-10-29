<?php
// /turnament-pro/profile.php
require_once 'common/header.php';

$message = '';
$error = '';

// Handle UPI ID Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_upi'])) {
    $upi_id = trim($_POST['upi_id']);
    
    $stmt = $conn->prepare("UPDATE users SET upi_id = ? WHERE id = ?");
    $stmt->bind_param("si", $upi_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $message = "UPI ID updated successfully!";
        // Update the user array to reflect the change on the page immediately
        $user['upi_id'] = $upi_id;
    } else {
        $error = "Failed to update UPI ID.";
    }
    $stmt->close();
}

// Handle Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch fresh user data including UPI ID
$stmt_user = $conn->prepare("SELECT username, email, upi_id FROM users WHERE id = ?");
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$current_user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
?>

<h2 class="text-2xl font-bold mb-4">Profile</h2>

<?php if ($message): ?>
<div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4 text-sm">
    <p><?php echo htmlspecialchars($message); ?></p>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm">
    <p><?php echo htmlspecialchars($error); ?></p>
</div>
<?php endif; ?>

<div class="bg-gray-800 rounded-lg shadow-lg p-6">
    <div class="text-center mb-6">
        <div class="w-24 h-24 rounded-full bg-gray-700 mx-auto flex items-center justify-center text-4xl font-bold text-yellow-400">
            <?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>
        </div>
        <h3 class="text-2xl font-bold mt-4"><?php echo htmlspecialchars($current_user['username']); ?></h3>
        <p class="text-gray-400"><?php echo htmlspecialchars($current_user['email']); ?></p>
    </div>

    <form method="POST" action="profile.php">
        <input type="hidden" name="update_upi" value="1">
        <div class="mb-4">
            <label for="upi_id" class="block text-gray-400 text-sm font-bold mb-2">Your UPI ID (for withdrawals)</label>
            <input type="text" name="upi_id" id="upi_id" value="<?php echo htmlspecialchars($current_user['upi_id'] ?? ''); ?>" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="yourname@upi">
        </div>
        <button type="submit" class="w-full bg-yellow-500 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-400 transition-transform transform hover:scale-105">Save UPI ID</button>
    </form>
    
    <div class="border-t border-gray-700 my-6"></div>

    <form method="POST" action="profile.php">
        <input type="hidden" name="logout" value="1">
        <button type="submit" class="w-full text-center bg-transparent border-2 border-red-500 text-red-500 font-bold py-2 px-4 rounded-lg transition-all duration-300 hover:bg-red-500 hover:text-gray-900 active:scale-95">
            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
        </button>
    </form>
</div>

<?php
$conn->close();
require_once 'common/bottom.php';
?>