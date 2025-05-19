<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT m.id, m.name FROM module m WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utilisateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary-color: #2196F3;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --white: #ffffff;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-light);
        }

        .dashboard-header h1 {
            font-size: 2rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h1 i {
            color: var(--primary-color);
        }

        .user-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }
        .btn-secondary .two{
            background-color: red;
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modules-section {
            margin-top: 2rem;
        }

        .modules-section h2 {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modules-section h2 i {
            color: var(--primary-color);
        }

        .modules-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .module-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .module-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .module-name i {
            color: var(--primary-color);
        }

        .module-actions {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .action-link {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-link.add {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }

        .action-link.add:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .action-link.view {
            background-color: #E3F2FD;
            color: var(--secondary-color);
        }

        .action-link.view:hover {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-actions {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .modules-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard Utilisateur</h1>
            <div class="user-actions">
                <a href="create_module.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer un module
                </a>
                <a href="me.php" class="btn btn-secondary ">
                    <i class="fas fa-user"></i> Mon profil
                </a>
                <a href="index.php" class="btn btn-secondary two ">
                <i class="fas fa-sign-out-alt"></i>Log out
                </a>
            </div>
        </div>
        
        <div class="modules-section">
            <h2><i class="fas fa-book-open"></i> Mes Modules</h2>
            
            <?php if (empty($modules)): ?>
                <p>Aucun module créé pour le moment.</p>
            <?php else: ?>
                <div class="modules-list">
                    <?php foreach ($modules as $module): ?>
                        <div class="module-card">
                            <div class="module-name">
                                <i class="fas fa-folder"></i>
                                <?= htmlspecialchars($module['name']) ?>
                            </div>
                            <div class="module-actions">
                                <a href="create_note.php?module_id=<?= $module['id'] ?>" class="action-link add">
                                    <i class="fas fa-plus"></i> Ajouter une note
                                </a>
                                <a href="view_notes.php?module_id=<?= $module['id'] ?>" class="action-link view">
                                    <i class="fas fa-eye"></i> Voir les notes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>