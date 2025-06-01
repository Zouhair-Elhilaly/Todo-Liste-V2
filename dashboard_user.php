<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}


// start generation quiz 


// session_start();
require 'db.php';
require 'functions/ai_functions.php'; // Ajoutez cette ligne

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Traitement de la génération de quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_quiz'])) {
    $module_id = $_POST['module_id'];
    $user_id = $_SESSION['user_id'];
    $quiz_type = $_POST['quiz_type'];
    $question_count = isset($_POST['question_count']) ? (int)$_POST['question_count'] : 5;

    try {
        // Vérification du module
        $stmt = $pdo->prepare("SELECT id, name FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        $module = $stmt->fetch();
        
        if (!$module) {
            throw new Exception("Module non trouvé ou non autorisé");
        }

        // Récupération des notes
        $stmt = $pdo->prepare("SELECT content FROM note WHERE module_id = ?");
        $stmt->execute([$module_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($notes)) {
            throw new Exception("Aucune note trouvée pour ce module");
        }
        
        $module_content = implode("\n", $notes);
        
        // Génération du quiz
        // $quiz_data = generate_quiz_with_ai($module_content, $question_count, $quiz_type);
        
        // Enregistrement en base de données
        $pdo->beginTransaction();
        
        // Insertion du quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (module_id, user_id, title, quiz_type, generated_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        $title = "Quiz {$quiz_type} sur " . $module['name'];
        $stmt->execute([$module_id, $user_id, $title, $quiz_type]);
        $quiz_id = $pdo->lastInsertId();
        
        // Insertion des questions
        $stmt = $pdo->prepare("INSERT INTO quiz_questions 
                              (quiz_id, question, correct_answer, options) 
                              VALUES (?, ?, ?, ?)");
        
        foreach ($quiz_data['questions'] as $question) {
            $options = ($quiz_type !== 'texte_libre') ? json_encode($question['options']) : null;
            $stmt->execute([
                $quiz_id,
                $question['question'],
                $question['correct_answer'],
                $options
            ]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Quiz généré avec succès!";
        header("Location: view_quiz.php?id=" . $quiz_id);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: dashboard_user.php");
        exit();
    }
}

// Le reste de votre code reste inchangé...


// ********************* end generation quiz *********************
// Traitement de la génération de quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_quiz'])) {
    $module_id = $_POST['module_id'];
    $user_id = $_SESSION['user_id'];
    $quiz_type = $_POST['quiz_type']; // Récupère le type de quiz
    $question_count = isset($_POST['question_count']) ? (int)$_POST['question_count'] : 5; // Récupère le nombre de questions
    
    try {
        // Vérifier que le module appartient bien à l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Module non trouvé ou non autorisé");
        }

        // Récupérer les notes du module
        $stmt = $pdo->prepare("SELECT content FROM note WHERE module_id = ?");
        $stmt->execute([$module_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($notes)) {
            throw new Exception("Aucune note trouvée pour ce module");
        }
        
        $module_content = implode("\n", $notes);
        
        // Appel à l'API IA avec les paramètres
        $quiz_data = generate_quiz_with_ai($module_content, $question_count, $quiz_type);
        
        if (!$quiz_data) {
            throw new Exception("Échec de la génération du quiz");
        }

        // Le reste du code reste inchangé...
    } catch (Exception $e) {
        
    }
}
// Traitement de la suppression du module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_module'])) {
    $module_id = $_POST['module_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Vérifier que le module appartient bien à l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Module non trouvé ou non autorisé");
        }

        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Supprimer les notes liées au module
        $stmt = $pdo->prepare("DELETE FROM note WHERE module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer les questions des quiz liés au module
        $stmt = $pdo->prepare("DELETE qq FROM quiz_questions qq 
                              INNER JOIN quizzes q ON qq.quiz_id = q.id 
                              WHERE q.module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer les quiz liés au module
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer le module
        $stmt = $pdo->prepare("DELETE FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        
        // Valider la transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Module supprimé avec succès!";
        header("Location: dashboard_user.php");
        exit();
        
    } catch (Exception $e) {
        // Annuler en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: dashboard_user.php");
        exit();
    }
}

// Récupération des modules
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
        /* Votre CSS existant reste inchangé */
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary-color: #2196F3;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --white: #ffffff;
            --danger-color: #f44336;
            --danger-dark: #d32f2f;
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
        .btn-secondary .two {
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
            flex-wrap: wrap;
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

        .action-link.delete {
            background-color: #FFEBEE;
            color: var(--danger-color);
        }

        .action-link.delete:hover {
            background-color: var(--danger-color);
            color: var(--white);
        }

        /* Styles pour le quiz generator */
        .quiz-generator {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }

        .quiz-btn {
            background-color: #9c27b0;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(156, 39, 176, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .quiz-btn:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 8px 25px rgba(156, 39, 176, 0.4);
        }

        .quiz-modal, .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .quiz-modal-content, .delete-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s ease-out;
            position: relative;
        }

        .delete-modal-content {
            max-width: 400px;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .quiz-modal h3, .delete-modal h3 {
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delete-modal h3 {
            color: var(--danger-dark);
        }

        .quiz-modal select, .quiz-modal button, .quiz-modal input,
        .delete-modal button {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .quiz-modal button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .delete-modal button.confirm-delete {
            background-color: var(--danger-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: bold;
        }

        .delete-modal button.confirm-delete:hover {
            background-color: var(--danger-dark);
        }

        .delete-modal button.cancel-delete {
            background-color: #e0e0e0;
            color: #333;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .delete-modal button.cancel-delete:hover {
            background-color: #ccc;
        }

        .quiz-modal button:hover {
            background-color: var(--primary-dark);
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .warning-text {
            color: var(--danger-color);
            margin-bottom: 1rem;
            font-weight: 500;
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
                <a href="me.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Mon profil
                </a>
                <a href="logout.php" class="btn btn-secondary two">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: #ffebee; color: #f44336; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #e8f5e9; color: #4CAF50; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
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
                                <a href="#" class="action-link delete" onclick="openDeleteModal(<?= $module['id'] ?>, '<?= htmlspecialchars($module['name']) ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bouton flottant pour générer un quiz -->
    <div class="quiz-generator">
        <button class="quiz-btn" id="quizGeneratorBtn">
            <i class="fas fa-robot"></i>
        </button>
    </div>

    <?php
// session_start();
require 'db.php';
require_once 'functions/ai_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Traitement de la suppression du module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_module'])) {
    $module_id = $_POST['module_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Vérifier que le module appartient bien à l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Module non trouvé ou non autorisé");
        }

        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Supprimer les notes liées au module
        $stmt = $pdo->prepare("DELETE FROM note WHERE module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer les questions des quiz liés au module
        $stmt = $pdo->prepare("DELETE qq FROM quiz_questions qq 
                              INNER JOIN quizzes q ON qq.quiz_id = q.id 
                              WHERE q.module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer les quiz liés au module
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE module_id = ?");
        $stmt->execute([$module_id]);
        
        // Supprimer le module
        $stmt = $pdo->prepare("DELETE FROM module WHERE id = ? AND user_id = ?");
        $stmt->execute([$module_id, $user_id]);
        
        // Valider la transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Module supprimé avec succès!";
        header("Location: dashboard_user.php");
        exit();
        
    } catch (Exception $e) {
        // Annuler en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: dashboard_user.php");
        exit();
    }
}

// Récupération des modules
$stmt = $pdo->prepare("SELECT m.id, m.name FROM module m WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- [VOTRE CODE HTML EXISTANT] -->
</head>
<body>
    <!-- [VOTRE CODE HTML EXISTANT] -->

    <script>
        // [VOTRE CODE JAVASCRIPT EXISTANT]
    </script>
</body>
</html>

    <!-- Modal pour la génération de quiz -->
    <div class="quiz-modal" id="quizModal">
        <div class="quiz-modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h3><i class="fas fa-magic"></i> Générer un Quiz IA</h3>
            
            <form method="POST" id="quizForm">
                <div class="form-group">
                    <label for="moduleSelect">Sélectionnez un module</label>
                    <select name="module_id" id="moduleSelect" required>
                        <option value="">-- Choisir un module --</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="module_name" id="moduleName">
                </div>
                
                <div class="form-group">
                    <label for="quizType">Type de quiz</label>
                    <select name="quiz_type" id="quizType">
                        <option value="qcm">QCM</option>
                        <option value="vrai_faux">Vrai/Faux</option>
                        <option value="texte_libre">Réponse libre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="questionCount">Nombre de questions</label>
                    <input type="number" name="question_count" id="questionCount"  value="5">
                </div>
                
                <button type="submit" name="generate_quiz" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> Générer le Quiz
                </button>
            </form>
        </div>
    </div>

    <!-- *************       Modal pour la confirmation de suppression ************************* -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <span class="close-modal" id="closeDeleteModal">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Supprimer le module</h3>
            
            <p class="warning-text">Êtes-vous sûr de vouloir supprimer le module "<span id="moduleToDelete"></span>" ?</p>
            <p>Cette action supprimera toutes les notes et les quiz associés à ce module et ne peut pas être annulée.</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="module_id" id="deleteModuleId">
                <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="cancel-delete" id="cancelDelete">Annuler</button>
                    <button type="submit" name="delete_module" class="confirm-delete">Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        
        // Gestion du modal quiz
        const quizBtn = document.getElementById('quizGeneratorBtn');
        const quizModal = document.getElementById('quizModal');
        const closeQuizBtn = document.getElementById('closeModal');
        const moduleSelect = document.getElementById('moduleSelect');
        const moduleNameInput = document.getElementById('moduleName');
        
        quizBtn.addEventListener('click', () => {
            quizModal.style.display = 'flex';
        });
        
        closeQuizBtn.addEventListener('click', () => {
            quizModal.style.display = 'none';
        });
        
        // Gestion du modal de suppression
        const deleteModal = document.getElementById('deleteModal');
        const closeDeleteBtn = document.getElementById('closeDeleteModal');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const moduleToDeleteSpan = document.getElementById('moduleToDelete');
        const deleteModuleIdInput = document.getElementById('deleteModuleId');
        
        function openDeleteModal(moduleId, moduleName) {
            moduleToDeleteSpan.textContent = moduleName;
            deleteModuleIdInput.value = moduleId;
            deleteModal.style.display = 'flex';
        }
        
        closeDeleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });
        
        cancelDeleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === quizModal) {
                quizModal.style.display = 'none';
            }
            if (e.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
        });
        
        // Mettre à jour le nom du module caché quand la sélection change
        moduleSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            moduleNameInput.value = selectedOption.text;
        });
        
        // Animation du bouton
        quizBtn.addEventListener('mouseenter', () => {
            quizBtn.style.transform = 'scale(1.1) rotate(10deg)';
        });
        
        quizBtn.addEventListener('mouseleave', () => {
            quizBtn.style.transform = '';
        });
    </script>
</body>
</html>