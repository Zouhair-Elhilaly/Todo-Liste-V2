<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    // $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);
}

// Suppression d'utilisateur
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: manage_users.php');
    exit();
}

// Modification d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    
    $stmt = $pdo->prepare("UPDATE user SET username = ? WHERE id = ?");
    $stmt->execute([$username, $id]);
    header('Location: manage_users.php');
    exit();
}

$users = $pdo->query("SELECT * FROM user")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --danger-color: #F44336;
            --warning-color: #FFC107;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --white: #ffffff;
            --border-color: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        
        /* Sidebar */
        .sidebar {
            width: 100%;
            background-color: var(--primary-dark);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 15px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .sidebar-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .sidebar-menu {
            list-style: none;
            display: flex;
            overflow-x: auto;
            padding: 0 10px;
        }
        
        .sidebar-menu li {
            flex-shrink: 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: rgba(255,255,255,0.1);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .header {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.3rem;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.1rem;
            color: var(--primary-dark);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--text-color);
        }
        
        .btn-warning:hover {
            background-color: #ffb300;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            min-width: 600px;
        }
        
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        
        th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: var(--light-gray);
        }
        
        tr:hover {
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .edit-form {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .edit-input {
            flex: 1;
            min-width: 150px;
            padding: 5px 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .logout-link {
            color: var(--danger-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .logout-link:hover {
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (min-width: 768px) {
            .admin-container {
                flex-direction: row;
            }
            
            .sidebar {
                width: 250px;
                height: 100vh;
                position: sticky;
                top: 0;
                padding: 20px 0;
            }
            
            .sidebar-menu {
                display: block;
                padding: 0;
            }
            
            .sidebar-menu li {
                margin-bottom: 5px;
            }
            
            .sidebar-menu li a {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .form-row {
                flex-direction: row;
            }
            
            .card {
                padding: 10px;
            }
        }

        @media (min-width: 992px) {
            .main-content {
                padding: 30px;
            }
            
            .card {
                padding: 25px;
            }
            
            .form-control {
                padding: 10px 15px;
                font-size: 1rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 1rem;
            }
            
            th, td {
                padding: 12px 15px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>
                <div class="user-actions">
                    <span>Bienvenue <?php echo htmlspecialchars($_SESSION['name']); ?></span> 
                </div>
            </div>

            <!-- Add User Card -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-user-plus"></i> Ajouter un utilisateur</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <input type="text" class="form-control" name="username" required placeholder="Nom d'utilisateur">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <input type="password" class="form-control" name="password" required placeholder="Mot de passe">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card">
                <h3 class="card-title"><i class="fas fa-users"></i> Liste des utilisateurs</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td class="action-buttons">
                                    <form method="POST" class="edit-form">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <input type="text" class="edit-input" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                        <button type="submit" name="edit_user" class="btn btn-warning btn-sm">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                    <a href="?delete=<?= htmlspecialchars($user['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>