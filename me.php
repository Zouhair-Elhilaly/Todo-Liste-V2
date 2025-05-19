<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(-45deg, #f5f7fa, #e4efe9, #f5f7fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .profile-container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1.5rem;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), transparent);
            animation: underlineExpand 1s ease-out forwards;
        }

        @keyframes underlineExpand {
            from { width: 0; }
            to { width: 100%; }
        }

        .profile-icon {
            width: 60px;
            height: 60px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .profile-title {
            font-size: 1.8rem;
            color: var(--primary-dark);
        }

        .profile-info {
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: var(--light-gray);
            border-radius: 8px;
            transition: var(--transition);
            animation: fadeIn 0.5s ease-out;
            animation-fill-mode: backwards;
        }

        .info-item:nth-child(1) { animation-delay: 0.3s; }
        .info-item:nth-child(2) { animation-delay: 0.5s; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .info-item:hover {
            transform: translateX(10px);
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.1);
        }

        .info-icon {
            color: var(--primary-color);
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .info-value {
            color: var(--text-color);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            animation: fadeIn 0.8s ease-out;
        }

        .back-link:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        @media (max-width: 576px) {
            .profile-container {
                padding: 1.5rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-header::after {
                width: 80%;
                left: 10%;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-icon">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="profile-title">Mon Profil</h1>
        </div>
        
        <div class="profile-info">
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-user-tag"></i>
                </div>
                <div>
                    <div class="info-label">Nom d'utilisateur</div>
                    <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <div class="info-label">Membre depuis</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                </div>
            </div>
        </div>
        
        <a href="dashboard_user.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>
</body>
</html>