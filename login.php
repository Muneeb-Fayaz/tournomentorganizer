<?php
// /turnament-pro/login.php
require_once 'common/config.php';

$error = '';
$success = '';

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Invalid credentials.";
    }
    $stmt->close();
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt_insert->execute()) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login - Turnament Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { -webkit-font-smoothing: antialiased; }</style>
</head>
<body class="flex items-center justify-center h-full select-none">
    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-sm mx-4" x-data="{ isRegistering: false }">
        <h1 class="text-3xl font-bold text-center text-white mb-6">Turnament Pro</h1>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" x-show="!isRegistering" x-transition>
            <input type="hidden" name="login" value="1">
            <div class="mb-4">
                <label for="email" class="block text-gray-400 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-400 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-yellow-500" required>
            </div>
            <button type="submit" class="w-full bg-yellow-500 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-400 transition-transform transform hover:scale-105">Login</button>
        </form>

        <!-- Registration Form -->
        <form method="POST" action="login.php" x-show="isRegistering" x-transition style="display: none;">
            <input type="hidden" name="register" value="1">
            <div class="mb-4">
                <label for="reg_username" class="block text-gray-400 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" id="reg_username" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label for="reg_email" class="block text-gray-400 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" id="reg_email" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="reg_password" class="block text-gray-400 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" id="reg_password" class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-400 transition-transform transform hover:scale-105">Register</button>
        </form>
        
        <div class="text-center mt-6">
            <button @click="isRegistering = !isRegistering" class="text-gray-400 hover:text-white text-sm">
                <span x-show="!isRegistering">Don't have an account? Register</span>
                <span x-show="isRegistering" style="display: none;">Already have an account? Login</span>
            </button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>