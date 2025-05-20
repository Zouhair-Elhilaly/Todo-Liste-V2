<?php
// Activation des erreurs (utile pour le développement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarrage de la session

// Inclusion du fichier de connexion à la base de données
require 'db.php'; // Assurez-vous que ce fichier configure correctement $pdo

$error = null; // Initialisation de la variable d'erreur

// Check for "remember me" cookie on page load
$remembered_username = '';
if (isset($_COOKIE['remember_username'])) {
    $remembered_username = $_COOKIE['remember_username'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember']); // Check if "remember me" checkbox is checked

    // 1. Vérification pour l'administrateur
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    // For a real application, you should use password_verify($password, $admin['password'])
    // instead of direct comparison for hashed passwords.
    if ($admin && $password === $admin['password']) {
        // L'administrateur est authentifié avec succès
        session_regenerate_id(true); // Régénère l'ID de session pour prévenir la fixation de session

        $_SESSION['admin'] = true;
        $_SESSION['user_id'] = $admin['id']; // Stocker l'ID de l'admin, si vous avez une colonne 'id'
        $_SESSION['name'] = $admin['username']; // Utiliser le nom d'utilisateur de la base de données

        // Set "remember me" cookie if checked
        if ($remember_me) {
            // Cookie valid for 30 days (60 seconds * 60 minutes * 24 hours * 30 days)
            setcookie('remember_username', $username, time() + (86400 * 30), "/");
        } else {
            // If not checked, remove the cookie if it exists
            setcookie('remember_username', '', time() - 3600, "/"); // Set expiration to past
        }
        
        header('Location: dashboard_admin.php');
        exit();
    }

    // 2. Vérification pour l'utilisateur standard (si ce n'est pas un admin)
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // For a real application, you should use password_verify($password, $user['password'])
    // instead of direct comparison for hashed passwords.
    if ($user && $password === $user['password']) {
        // L'utilisateur standard est authentifié avec succès
        session_regenerate_id(true); // Régénère l'ID de session

        $_SESSION['user'] = true; // Indicateur générique pour utilisateur connecté
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['username']; // Utiliser le nom d'utilisateur de la base de données

        // Set "remember me" cookie if checked
        if ($remember_me) {
            setcookie('remember_username', $username, time() + (86400 * 30), "/");
        } else {
            setcookie('remember_username', '', time() - 3600, "/");
        }
        
        header('Location: dashboard_user.php');
        exit();
    }
    
    // Si aucune des vérifications n'a abouti
    $error = "Nom d'utilisateur ou mot de passe invalide.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Mon Site</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary-color: #2196F3;
            --danger-color: #F44336;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(-45deg, #f5f7fa, #e4efe9, #f5f7fa);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: var(--primary-color);
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .nav-links a:hover {
            color: var(--primary-color);
            background-color: var(--primary-light);
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            text-align: center;
        }

        .login-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .error-message {
            color: var(--danger-color);
            margin-bottom: 1rem;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .forgot-password {
            font-size: 0.9rem;
            color: var(--secondary-color);
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .register-link {
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .register-link a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: var(--primary-dark);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-links a {
            color: var(--white);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: var(--primary-light);
            transform: translateY(-3px);
        }

        .copyright {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Animation for the logo */
        .logo:hover i {
            animation: bounce 0.8s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
</style>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-leaf"></i>
                <span>MonSite</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Accueil</a>
                <a href="about.php">À propos</a>
                <a href="contact.php">Contact</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <div class="login-card">
                <h1 class="login-title">
                    <i class="fas fa-sign-in-alt"></i>
                    Connexion
                </h1>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" name="username" required placeholder="Nom d'utilisateur" value="<?= htmlspecialchars($remembered_username) ?>">
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" name="password" required placeholder="Mot de passe">
                    </div>
                    
                    <div class="remember-forgot">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" <?= !empty($remembered_username) ? 'checked' : '' ?>>
                            Se souvenir de moi
                        </label>
                        </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </button>
                </form>
                
                </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <p class="copyright">
                &copy; <?= date('Y') ?> MonSite. Tous droits réservés.
            </p>
        </div>
    </footer>
</body>
</html>