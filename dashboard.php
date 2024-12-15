<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Default filter (All Contacts)
$filter = 'all';
$user_id = $_SESSION['user_id'];

// Update filter based on query parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// SQL query to fetch contacts based on filter
switch ($filter) {
    case 'sales_leads':
        $stmt = $pdo->prepare("SELECT * FROM Contacts WHERE type = 'Sales Lead' ORDER BY created_at DESC");
        break;
    case 'support':
        $stmt = $pdo->prepare("SELECT * FROM Contacts WHERE type = 'Support' ORDER BY created_at DESC");
        break;
    case 'assigned_to_me':
        $stmt = $pdo->prepare("SELECT * FROM Contacts WHERE assigned_to = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $user_id);
        break;
    default:
        $stmt = $pdo->query("SELECT * FROM Contacts ORDER BY created_at DESC");
        break;
}

// Execute query
if ($filter === 'assigned_to_me') {
    $stmt->execute();
} else {
    $stmt->execute();
}
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dolphin CRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>Dolphin CRM</h2>
        <a href="dashboard.php" class="active">Home</a>
        <a href="add_contact.php">New Contact</a>
        <a href="users.php">Users</a>
        <a href="logout.php">Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Welcome to Dolphin CRM</h1>
        <p>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>

        <!-- Filter Buttons -->
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php?filter=all" class="button">All Contacts</a>
            <a href="dashboard.php?filter=sales_leads" class="button">Sales Leads</a>
            <a href="dashboard.php?filter=support" class="button">Support</a>
            <a href="dashboard.php?filter=assigned_to_me" class="button">Assigned to Me</a>
        </div>

        <!-- Contacts Table -->
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Telephone</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($contacts): ?>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td>
                                <a href="view_contact.php?id=<?php echo $contact['id']; ?>" class="contact-link">
                                    <?php echo htmlspecialchars($contact['firstname'] . ' ' . $contact['lastname']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['telephone']); ?></td>
                            <td><?php echo htmlspecialchars($contact['company']); ?></td>
                            <td><?php echo htmlspecialchars($contact['type']); ?></td>
                            <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($contact['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No contacts found for this filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
