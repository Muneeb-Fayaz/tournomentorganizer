<?php
// /turnament-pro/my_tournaments.php
require_once 'common/header.php';

// Fetch user's joined tournaments
$user_id = $_SESSION['user_id'];
$tournaments_result = $conn->prepare("
    SELECT t.*
    FROM tournaments t
    JOIN participants p ON t.id = p.tournament_id
    WHERE p.user_id = ?
    ORDER BY t.match_time DESC
");
$tournaments_result->bind_param("i", $user_id);
$tournaments_result->execute();
$result = $tournaments_result->get_result();
?>

<h2 class="text-2xl font-bold mb-4">My Tournaments</h2>

<div class="space-y-4">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="bg-gray-800 rounded-lg shadow-lg p-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['game_name']); ?></p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full
                    <?php if ($row['status'] == 'Upcoming') echo 'bg-blue-500/50 text-blue-200'; ?>
                    <?php if ($row['status'] == 'Running') echo 'bg-yellow-500/50 text-yellow-200'; ?>
                    <?php if ($row['status'] == 'Completed') echo 'bg-green-500/50 text-green-200'; ?>
                ">
                    <?php echo htmlspecialchars($row['status']); ?>
                </span>
            </div>
            
            <div class="border-t border-gray-700 my-3"></div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-semibold text-gray-400">Match Time</p>
                    <p><?php echo date("d M Y, h:i A", strtotime($row['match_time'])); ?></p>
                </div>
                <div>
                    <p class="font-semibold text-gray-400">Entry Fee</p>
                    <p><?php echo format_currency($row['entry_fee']); ?></p>
                </div>
                <div>
                    <p class="font-semibold text-gray-400">Prize Pool</p>
                    <p class="text-yellow-400 font-bold"><?php echo format_currency($row['prize_pool']); ?></p>
                </div>
                <?php if ($row['status'] == 'Completed' && $row['winner_id'] == $user_id): ?>
                <div>
                     <p class="font-semibold text-gray-400">Result</p>
                     <p class="text-green-400 font-bold">🎉 You Won!</p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($row['room_id'])): ?>
            <div class="mt-4 bg-gray-700/50 p-3 rounded-lg">
                <h4 class="font-bold text-md text-center">Room Details</h4>
                <div class="grid grid-cols-2 gap-2 text-center mt-2">
                    <div>
                        <p class="text-xs text-gray-400">Room ID</p>
                        <p class="font-mono bg-gray-900 py-1 rounded"><?php echo htmlspecialchars($row['room_id']); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Password</p>
                        <p class="font-mono bg-gray-900 py-1 rounded"><?php echo htmlspecialchars($row['room_password']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-10">
            <i class="fa-solid fa-trophy fa-3x text-gray-600"></i>
            <p class="mt-4 text-gray-500">You haven't joined any tournaments yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$tournaments_result->close();
$conn->close();
require_once 'common/bottom.php';
?>