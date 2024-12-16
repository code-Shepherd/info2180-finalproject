<?php
session_start();
require 'config.php';

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Input validation
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($role)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!preg_match('/(?=.*[a-zA-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password must contain at least one letter and one number.";
    } elseif ($role !== 'admin' && $role !== 'member') {
        $errors[] = "Invalid role selected.";
    } else {
        // Check for existing email
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "A user with this email already exists.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Users (firstname, lastname, email, password, role, created_at) 
                                   VALUES (:firstname, :lastname, :email, :password, :role, NOW())");
            $stmt->execute([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'password' => $hashed_password,
                'role' => $role
            ]);
            $success = "User added successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>Dolphin CRM</h2>
        <a href="dashboard.php">Home</a>
        <a href="add_contact.php">New Contact</a>
        <a href="users.php" class="active">Users</a>
        <a href="logout.php">Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Add New User</h1>
        <p>Only admins can add new users.</p>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="add_user.php">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname" required>

            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="member">Member</option>
                <option value="admin">Admin</option>
            </select>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <small>Password must contain at least one letter and one number.</small>

            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html>
