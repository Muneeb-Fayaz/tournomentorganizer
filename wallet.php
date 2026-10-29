<?php
// /turnament-pro/wallet.php
require_once 'common/header.php';

$message = '';
$error = '';

$admin_upi_id = get_setting($conn, 'admin_upi_id');
$admin_qr_image = get_setting($conn, 'admin_qr_image');


// Handle Deposit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_deposit'])) {
    $amount = $_POST['amount'];
    $transaction_id = $_POST['transaction_id'];

    if (!empty($amount) && !empty($transaction_id) && is_numeric($amount) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, transaction_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $_SESSION['user_id'], $amount, $transaction_id);
        if ($stmt->execute()) {
            $message = "Deposit request submitted successfully. It will be reviewed by an admin shortly.";
        } else {
            $error = "Failed to submit request. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Please provide a valid amount and transaction ID.";
    }
}

// Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = $_POST['amount'];

    if (!empty($amount) && is_numeric($amount) && $amount > 0) {
        if ($user['wallet_balance'] >= $amount) {
             if (!empty($user['upi_id'])) {
                $stmt = $conn->prepare("INSERT INTO withdrawals (user_id, amount) VALUES (?, ?)");
                $stmt->bind_param("id", $_SESSION['user_id'], $amount);
                if ($stmt->execute()) {
                    $message = "Withdrawal request submitted. It will be processed soon.";
                } else {
                    $error = "Failed to submit request. Please try again.";
                }
                $stmt->close();
             } else {
                 $error = "Please update your UPI ID in your profile before withdrawing.";
             }
        } else {
            $error = "Insufficient balance for this withdrawal.";
        }
    } else {
        $error = "Please provide a valid amount.";
    }
}

// Fetch user's transaction history
$stmt_trans = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt_trans->bind_param("i", $_SESSION['user_id']);
$stmt_trans->execute();
$transactions = $stmt_trans->get_result();
$stmt_trans->close();
?>

<div x-data="{ depositModal: false, withdrawModal: false }">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6 text-center">
        <p class="text-gray-400 text-sm">Current Balance</p>
        <p class="text-4xl font-bold mt-2"><?php echo format_currency($user['wallet_balance']); ?></p>
        
        <div class="grid grid-cols-2 gap-4 mt-6">
            <button @click="depositModal = true" class="w-full text-center bg-transparent border-2 border-green-500 text-green-500 font-bold py-3 px-4 rounded-lg transition-all duration-300 hover:bg-green-500 hover:text-gray-900 active:scale-95">
                <i class="fa-solid fa-plus mr-2"></i> Add Money
            </button>
            <button @click="withdrawModal = true" class="w-full text-center bg-transparent border-2 border-red-500 text-red-500 font-bold py-3 px-4 rounded-lg transition-all duration-300 hover:bg-red-500 hover:text-gray-900 active:scale-95">
                <i class="fa-solid fa-arrow-down mr-2"></i> Withdraw
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg my-4 text-sm">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg my-4 text-sm">
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
    <?php endif; ?>

    <!-- Recent Transactions -->
    <h3 class="text-xl font-bold mt-6 mb-4">Recent Transactions</h3>
    <div class="space-y-3">
        <?php if($transactions->num_rows > 0): ?>
            <?php while($t = $transactions->fetch_assoc()): ?>
            <div class="bg-gray-800 rounded-lg p-3 flex justify-between items-center">
                <div>
                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($t['description']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date("d M, h:i A", strtotime($t['created_at'])); ?></p>
                </div>
                <span class="font-bold text-lg <?php echo ($t['type'] == 'credit') ? 'text-green-400' : 'text-red-400'; ?>">
                    <?php echo ($t['type'] == 'credit') ? '+' : '-'; ?><?php echo format_currency($t['amount']); ?>
                </span>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-gray-500 py-4">No transactions yet.</p>
        <?php endif; ?>
    </div>

    <!-- Deposit Modal -->
    <div x-show="depositModal" @keydown.escape.window="depositModal = false" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50" style="display: none;">
        <div @click.away="depositModal = false" class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-xl font-bold mb-4 text-center">Add Money</h3>
            <div class="text-center mb-4">
                <p class="text-sm text-gray-400">Scan QR or use UPI ID</p>
                <?php if (!empty($admin_qr_image)): ?>
                <img src="/turnament-pro/admin/<?php echo htmlspecialchars($admin_qr_image); ?>" alt="Admin UPI QR" class="w-48 h-48 mx-auto my-4 rounded-lg bg-white p-2">
                <?php else: ?>
                <div class="w-48 h-48 mx-auto my-4 rounded-lg bg-gray-700 flex items-center justify-center"><p class="text-xs text-gray-400">QR not set by admin</p></div>
                <?php endif; ?>
                <p class="font-mono bg-gray-700 p-2 rounded-lg text-yellow-300"><?php echo htmlspecialchars($admin_upi_id); ?></p>
            </div>
            <form method="POST" action="wallet.php">
                <input type="hidden" name="request_deposit" value="1">
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Amount Paid (₹)</label>
                    <input type="number" step="0.01" name="amount" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">UPI Transaction ID</label>
                    <input type="text" name="transaction_id" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="button" @click="depositModal = false" class="text-gray-400 hover:text-white">Cancel</button>
                    <button type="submit" class="bg-yellow-500 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-400">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Withdraw Modal -->
    <div x-show="withdrawModal" @keydown.escape.window="withdrawModal = false" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50" style="display: none;">
        <div @click.away="withdrawModal = false" class="bg-gray-800 rounded-lg shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-xl font-bold mb-4 text-center">Request Withdrawal</h3>
            <?php if (!empty($user['upi_id'])): ?>
            <p class="text-center text-sm mb-4 text-gray-400">Amount will be sent to: <br><strong class="text-white"><?php echo htmlspecialchars($user['upi_id']); ?></strong></p>
            <form method="POST" action="wallet.php">
                <input type="hidden" name="request_withdrawal" value="1">
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Amount to Withdraw (₹)</label>
                    <input type="number" step="0.01" name="amount" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none" max="<?php echo $user['wallet_balance']; ?>" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="button" @click="withdrawModal = false" class="text-gray-400 hover:text-white">Cancel</button>
                    <button type="submit" class="bg-yellow-500 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-400">Submit Request</button>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center">
                 <p class="text-yellow-400 mb-4">Please add your UPI ID on your profile page before you can request a withdrawal.</p>
                 <a href="profile.php" class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-400">Go to Profile</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?php
$conn->close();
require_once 'common/bottom.php';
?>