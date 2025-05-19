<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['module_id'])) {
    header('Location: index.php');
    exit();
}
$module_id = $_GET['module_id'];
$stmt = $pdo->prepare("SELECT * FROM note WHERE module_id = ?");
$stmt->execute([$module_id]);
$notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes du Module</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to the top */
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            box-sizing: border-box;
        }

        h2 {
            color: #28a745; /* Primary green */
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
            position: relative;
        }

        h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background-color: #28a745;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .notes-list {
            margin-top: 20px;
        }

        .note-item {
            background-color: #e6ffe6; /* Light green background for notes */
            border: 1px solid #a8e0a8; /* Slightly darker green border */
            border-left: 5px solid #4CAF50; /* Stronger green left border */
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .note-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .note-content {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }

        .note-content p {
            margin: 0;
            font-size: 1.1em;
            color: #333;
        }

        .note-icon {
            color: #4CAF50; /* Green icon */
            font-size: 1.5em;
            margin-right: 15px;
        }

        .note-actions {
            display: flex;
            gap: 15px;
        }

        .note-actions a {
            text-decoration: none;
            color: #007bff; /* Default blue for links, will be overridden */
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s ease-in-out;
        }

        .note-actions a i {
            font-size: 1em;
        }

        .note-actions a:hover {
            text-decoration: underline;
        }

        /* Edit link style */
        .note-actions a[href*="edit_note.php"] {
            color: #28a745; /* Green for edit */
        }

        .note-actions a[href*="edit_note.php"]:hover {
            color: #218838; /* Darker green on hover */
        }

        /* Delete link style with animation */
        .note-actions a.delete-link {
            color: #dc3545; /* Red for delete */
            animation: pulseRed 2s infinite; /* Apply pulse animation */
        }

        .note-actions a.delete-link:hover {
            color: #c82333; /* Darker red on hover */
            animation: none; /* Stop animation on hover */
        }

        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #28a745; /* Primary green */
            color: #fff;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 30px;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .back-to-dashboard:hover {
            background-color: #218838; /* Darker green on hover */
            transform: translateY(-2px);
        }

        .back-to-dashboard i {
            font-size: 1.1em;
        }

        .no-notes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px dashed #ccc;
            margin-top: 20px;
        }

        /* Keyframes Animation */
        @keyframes pulseRed {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Notes du Module</h2>
        <div class="notes-list">
            <?php if (empty($notes)): ?>
                <p class="no-notes">Aucune note pour ce module pour le moment.</p>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-content">
                            <i class="fas fa-sticky-note note-icon"></i>
                            <p><?= htmlspecialchars($note['content']) ?></p>
                        </div>
                        <div class="note-actions">
                            <a href="edit_note.php?id=<?= $note['id'] ?>" title="Modifier"><i class="fas fa-edit"></i> Modifier</a>
                            <a href="delete_note.php?id=<?= $note['id'] ?>" title="Supprimer" class="delete-link"><i class="fas fa-trash-alt"></i> Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="dashboard_user.php" class="back-to-dashboard"><i class="fas fa-arrow-circle-left"></i> Retour au Dashboard</a>
    </div>
</body>
</html>