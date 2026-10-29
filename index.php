<?php
// /turnament-pro/index.php
require_once 'common/header.php';

$message = '';
$error = '';

// Handle joining a tournament
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_tournament'])) {
    $tournament_id = $_POST['tournament_id'];
    $entry_fee = $_POST['entry_fee'];

    // Check if user has enough balance
    $stmt_balance = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_balance->bind_param("i", $_SESSION['user_id']);
    $stmt_balance->execute();
    $user_wallet = $stmt_balance->get_result()->fetch_assoc();
    $stmt_balance->close();

    if ($user_wallet['wallet_balance'] >= $entry_fee) {
        // Check if user has already joined
        $stmt_check = $conn->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
        $stmt_check->bind_param("ii", $_SESSION['user_id'], $tournament_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error = "You have already joined this tournament.";
        } else {
            // Deduct fee and add to participants
            $conn->begin_transaction();
            try {
                // Deduct from wallet
                $stmt_debit = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt_debit->bind_param("di", $entry_fee, $_SESSION['user_id']);
                $stmt_debit->execute();

                // Record transaction
                $desc = "Entry fee for tournament #" . $tournament_id;
                $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
                $stmt_trans->bind_param("ids", $_SESSION['user_id'], $entry_fee, $desc);
                $stmt_trans->execute();

                // Add to participants
                $stmt_join = $conn->prepare("INSERT INTO participants (user_id, tournament_id) VALUES (?, ?)");
                $stmt_join->bind_param("ii", $_SESSION['user_id'], $tournament_id);
                $stmt_join->execute();

                $conn->commit();
                $message = "Successfully joined the tournament!";
                // Refresh user balance in header
                $user['wallet_balance'] -= $entry_fee;

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $error = "An error occurred. Please try again.";
            }
        }
        $stmt_check->close();
    } else {
        $error = "Insufficient wallet balance. Please add funds.";
    }
}


// Fetch upcoming tournaments
$tournaments_result = $conn->query("
    SELECT t.*, (SELECT COUNT(id) FROM participants p WHERE p.tournament_id = t.id) as participant_count
    FROM tournaments t
    WHERE t.status = 'Upcoming'
    ORDER BY t.match_time ASC
");

?>

<h2 class="text-2xl font-bold mb-4">Upcoming Tournaments</h2>

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

<div class="space-y-4">
    <?php if ($tournaments_result->num_rows > 0): ?>
        <?php while($row = $tournaments_result->fetch_assoc()): ?>
        <div class="bg-gray-800 rounded-lg shadow-lg p-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['game_name']); ?></p>
                </div>
                <span class="text-xs font-semibold bg-blue-500/50 text-blue-200 px-2 py-1 rounded-full"><?php echo htmlspecialchars($row['status']); ?></span>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4 text-center">
                <div>
                    <p class="text-xs text-gray-400">Prize Pool</p>
                    <p class="font-bold text-yellow-400"><?php echo format_currency($row['prize_pool']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Entry Fee</p>
                    <p class="font-bold text-green-400"><?php echo format_currency($row['entry_fee']); ?></p>
                </div>
                 <div>
                    <p class="text-xs text-gray-400">Match Time</p>
                    <p class="font-semibold text-sm"><?php echo date("d M Y, h:i A", strtotime($row['match_time'])); ?></p>
                </div>
                 <div>
                    <p class="text-xs text-gray-400">Participants</p>
                    <p class="font-semibold text-sm"><?php echo $row['participant_count']; ?></p>
                </div>
            </div>
            <div class="mt-4">
                <form method="POST" action="index.php">
                    <input type="hidden" name="tournament_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="entry_fee" value="<?php echo $row['entry_fee']; ?>">
                    <button type="submit" name="join_tournament" class="w-full text-center bg-transparent border-2 border-yellow-500 text-yellow-500 font-bold py-2 px-4 rounded-lg transition-all duration-300 hover:bg-yellow-500 hover:text-gray-900 active:scale-95">
                        Join Now
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-10">
            <i class="fa-solid fa-ghost fa-3x text-gray-600"></i>
            <p class="mt-4 text-gray-500">No upcoming tournaments right now.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'common/bottom.php';
?>