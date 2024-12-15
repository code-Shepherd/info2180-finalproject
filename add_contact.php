<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

// Fetch all users for the dropdown
$user_stmt = $pdo->query("SELECT id, firstname, lastname FROM Users");
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $company = trim($_POST['company']);
    $type = trim($_POST['type']);
    $assigned_to = $_POST['assigned_to'] ?? null;
    $created_by = $_SESSION['user_id'];

    // Input validation
    if (empty($firstname) || empty($lastname) || empty($email)) {
        $errors[] = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Insert new contact
        $stmt = $pdo->prepare("INSERT INTO Contacts (firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at) 
                               VALUES (:firstname, :lastname, :email, :telephone, :company, :type, :assigned_to, :created_by, NOW())");
        $stmt->execute([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'telephone' => $telephone,
            'company' => $company,
            'type' => $type,
            'assigned_to' => $assigned_to,
            'created_by' => $created_by
        ]);
        $success = "Contact added successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Contact</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>Dolphin CRM</h2>
        <a href="dashboard.php">Home</a>
        <a href="add_contact.php" class="active">New Contact</a>
        <a href="users.php">Users</a>
        <a href="logout.php">Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Add New Contact</h1>

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

        <form method="POST" action="">
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname" required>

            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="telephone">Telephone:</label>
            <input type="text" id="telephone" name="telephone">

            <label for="company">Company:</label>
            <input type="text" id="company" name="company">

            <label for="type">Type:</label>
            <select id="type" name="type" required>
                <option value="Sales Lead">Sales Lead</option>
                <option value="Support">Support</option>
            </select>

            <label for="assigned_to">Assign to User:</label>
            <select id="assigned_to" name="assigned_to">
                <option value="">Unassigned</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>">
                        <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Save Contact</button>
        </form>
    </div>
</body>
</html>
