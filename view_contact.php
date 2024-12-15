<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch contact ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$contact_id = $_GET['id'];
$errors = [];
$success = "";

// Fetch contact details
$stmt = $pdo->prepare("SELECT Contacts.*, Users.firstname AS assigned_fname, Users.lastname AS assigned_lname 
                       FROM Contacts 
                       LEFT JOIN Users ON Contacts.assigned_to = Users.id
                       WHERE Contacts.id = :id");
$stmt->execute(['id' => $contact_id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    echo "Contact not found.";
    exit;
}

// Fetch notes for this contact
$note_stmt = $pdo->prepare("SELECT Notes.comment, Notes.created_at, Users.firstname, Users.lastname 
                            FROM Notes 
                            JOIN Users ON Notes.created_by = Users.id 
                            WHERE Notes.contact_id = :contact_id ORDER BY Notes.created_at DESC");
$note_stmt->execute(['contact_id' => $contact_id]);
$notes = $note_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_to_me'])) {
        $stmt = $pdo->prepare("UPDATE Contacts SET assigned_to = :user_id, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['user_id' => $_SESSION['user_id'], 'id' => $contact_id]);
        $success = "Contact assigned to you successfully.";
    } elseif (isset($_POST['change_type'])) {
        $new_type = ($contact['type'] === 'Sales Lead') ? 'Support' : 'Sales Lead';
        $stmt = $pdo->prepare("UPDATE Contacts SET type = :type, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['type' => $new_type, 'id' => $contact_id]);
        $success = "Contact type updated successfully.";
    } elseif (isset($_POST['add_note'])) {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $stmt = $pdo->prepare("INSERT INTO Notes (contact_id, comment, created_by, created_at) 
                                   VALUES (:contact_id, :comment, :created_by, NOW())");
            $stmt->execute([
                'contact_id' => $contact_id,
                'comment' => $comment,
                'created_by' => $_SESSION['user_id']
            ]);
            $success = "Note added successfully.";
        } else {
            $errors[] = "Note cannot be empty.";
        }
    }

    // Refresh page to show updated data
    header("Location: view_contact.php?id=$contact_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Details - Dolphin CRM</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>Dolphin CRM</h2>
        <a href="dashboard.php">Home</a>
        <a href="add_contact.php">New Contact</a>
        <a href="users.php">Users</a>
        <a href="logout.php">Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Contact Details</h1>

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

        <!-- Contact Information -->
        <div class="contact-card">
            <h2><?php echo htmlspecialchars($contact['firstname'] . ' ' . $contact['lastname']); ?></h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?></p>
            <p><strong>Telephone:</strong> <?php echo htmlspecialchars($contact['telephone']); ?></p>
            <p><strong>Company:</strong> <?php echo htmlspecialchars($contact['company']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($contact['type']); ?></p>
            <p><strong>Assigned To:</strong> 
                <?php echo $contact['assigned_fname'] ? htmlspecialchars($contact['assigned_fname'] . ' ' . $contact['assigned_lname']) : 'Unassigned'; ?>
            </p>
            <p><strong>Date Created:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($contact['created_at']))); ?></p>
            <p><strong>Last Updated:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($contact['updated_at']))); ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="view-contact-actions">
            <form method="POST" action="" style="display: inline;">
                <button type="submit" name="assign_to_me">Assign to Me</button>
            </form>
            <form method="POST" action="" style="display: inline;">
                <button type="submit" name="change_type">
                    Switch to <?php echo $contact['type'] === 'Sales Lead' ? 'Support' : 'Sales Lead'; ?>
                </button>
            </form>
        </div>

        <!-- Notes Section -->
        <h2>Notes</h2>
        <ul class="notes-list">
            <?php if ($notes): ?>
                <?php foreach ($notes as $note): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($note['firstname'] . ' ' . $note['lastname']); ?>:</strong>
                        <?php echo htmlspecialchars($note['comment']); ?>
                        <em>(<?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($note['created_at']))); ?>)</em>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No notes have been added yet.</li>
            <?php endif; ?>
        </ul>

        <!-- Add Note Form -->
        <div class="add-note-form">
            <h3>Add a Note</h3>
            <form method="POST" action="">
                <textarea name="comment" rows="4" placeholder="Write your note here..." required></textarea><br>
                <button type="submit" name="add_note">Save Note</button>
            </form>
        </div>
    </div>
</body>
</html>
