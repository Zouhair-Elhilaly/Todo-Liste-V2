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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes du Module</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        :root {
            --primary-color: #28a745;
            --primary-dark: #218838;
            --danger-color: #dc3545;
            --danger-dark: #c82333;
            --info-color: #17a2b8;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #343a40;
            --text-color: #212529;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-size: clamp(1.5rem, 2.5vw, 2.2rem);
            position: relative;
            padding-bottom: 0.5rem;
        }

        h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            margin: 0.5rem auto 0;
            border-radius: 2px;
        }

        .notes-list {
            margin: 1.5rem 0;
        }

        .note-item {
            background-color: #f0fff0;
            border: 1px solid #d4edda;
            border-left: 5px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: var(--transition);
        }

        @media (min-width: 768px) {
            .note-item {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem;
            }
        }

        .note-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .note-content {
            display: flex;
            align-items: flex-start;
            flex-grow: 1;
            gap: 1rem;
        }

        .note-content p {
            margin: 0;
            font-size: 1rem;
            word-break: break-word;
        }

        @media (min-width: 576px) {
            .note-content p {
                font-size: 1.1rem;
            }
        }

        .note-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .note-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .note-actions a {
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        @media (min-width: 576px) {
            .note-actions a {
                font-size: 1rem;
                padding: 0.5rem 0;
            }
        }

        .note-actions a i {
            font-size: 1rem;
        }

        /* Edit link style */
        .note-actions a[href*="edit_note.php"] {
            color: var(--primary-color);
            background-color: rgba(40, 167, 69, 0.1);
        }

        .note-actions a[href*="edit_note.php"]:hover {
            color: white;
            background-color: var(--primary-color);
        }

        /* Delete link style */
        .note-actions a.delete-link {
            color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.1);
        }

        .note-actions a.delete-link:hover {
            color: white;
            background-color: var(--danger-color);
        }

        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 2rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
            width: 100%;
            justify-content: center;
        }

        @media (min-width: 576px) {
            .back-to-dashboard {
                width: auto;
            }
        }

        .back-to-dashboard:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .back-to-dashboard i {
            font-size: 1.1rem;
        }

        .no-notes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px dashed #ccc;
            margin: 1.5rem 0;
        }

        /* Confirmation dialog for delete action */
        .delete-link {
            position: relative;
        }

        .delete-link::after {
            content: attr(data-confirm);
            position: absolute;
            background-color: var(--danger-dark);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            margin-bottom: 0.5rem;
        }

        .delete-link:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .container {
                padding: 1.5rem;
            }
            
            body {
                padding: 15px;
            }
            
            .note-actions {
                justify-content: space-between;
                width: 100%;
            }
            
            .note-actions a {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Notes du Module</h2>
        <div class="notes-list">
            <?php if (empty($notes)): ?>
                <div class="no-notes">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p>Aucune note pour ce module pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-content">
                            <i class="fas fa-sticky-note note-icon"></i>
                            <p><?= htmlspecialchars($note['content']) ?></p>
                        </div>
                        <div class="note-actions">
                            <a href="edit_note.php?id=<?= $note['id'] ?>" title="Modifier">
                                <i class="fas fa-edit"></i> <span class="action-text">Modifier</span>
                            </a>
                            <a href="delete_note.php?id=<?= $note['id'] ?>" title="Supprimer" class="delete-link" data-confirm="Êtes-vous sûr ?">
                                <i class="fas fa-trash-alt"></i> <span class="action-text">Supprimer</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="dashboard_user.php" class="back-to-dashboard">
            <i class="fas fa-arrow-circle-left"></i> Retour au Dashboard
        </a>
    </div>
</body>
</html>