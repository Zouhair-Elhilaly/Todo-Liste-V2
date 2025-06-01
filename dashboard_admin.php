<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
$count = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
            --primary-light: #dcfce7;
            --text-color: #1e293b;
            --light-gray: #f8fafc;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Header */
        header {
            background-color: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-left h1 i {
            color: var(--primary-color);
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .logout-btn:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
        }
        
        .dashboard-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .dashboard-card h2 {
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .stats-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .stat-icon {
            background-color: var(--primary-light);
            color: var(--primary-color);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        /* Footer */
        footer {
            background-color: var(--white);
            color: #64748b;
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
            font-size: 0.875rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-left h1 {
                font-size: 1.25rem;
            }
            
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .dashboard-card {
                padding: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .dashboard-card h2 {
                font-size: 1.1rem;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 0.25rem;
            }
        }

        @media (min-width: 1600px) {
            .container {
                max-width: 1400px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-left">
            <h1><i class="fas fa-shield-alt"></i> Tableau de Bord Admin</h1>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </header>

    <div class="container">
        <div class="dashboard-card">
            <h2><i class="fas fa-chart-line"></i> Statistiques</h2>
            <div class="stats-container">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div> 
                <div>
                    <div class="stat-value"><?= htmlspecialchars($count['count']) ?></div>
                    <div class="stat-label">Utilisateurs inscrits</div>
                </div>
            </div>
            <a href="manage_users.php" class="btn">
                <i class="fas fa-user-cog"></i>
                Gérer les utilisateurs
            </a>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <i class="far fa-copyright"></i>
            <p><?= date('Y') ?> Administration - Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>