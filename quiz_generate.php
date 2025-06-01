<?php
session_start();
require 'db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Configuration de l'API Gemini
$GEMINI_API_KEY = ''; // Remplacez par votre clé API
$GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

// Récupérer les modules
$stmt = $pdo->prepare("SELECT * FROM module ORDER BY name");
$stmt->execute();
$modules = $stmt->fetchAll();

$message = '';
$error = '';

// Fonction pour appeler l'API Gemini
function generateQuizWithGemini($topic, $num_questions, $difficulty, $question_types, $api_key, $api_url) {
    $types_text = implode(', ', $question_types);
    
    $prompt = "Génère un quiz de {$num_questions} questions sur le sujet : {$topic}. 
    Niveau de difficulté : {$difficulty}.
    Types de questions : {$types_text}.
    
    Retourne UNIQUEMENT un JSON valide avec cette structure exacte :
    {
        \"questions\": [
            {
                \"question\": \"Question ici\",
                \"type\": \"qcm\",
                \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
                \"correct_answer\": \"Option correcte\",
                \"explanation\": \"Explication de la réponse\"
            },
            {
                \"question\": \"Question vrai/faux ici\",
                \"type\": \"vrai_faux\",
                \"correct_answer\": \"true\",
                \"explanation\": \"Explication\"
            },
            {
                \"question\": \"Question ouverte ici\",
                \"type\": \"texte_libre\",
                \"correct_answer\": \"Réponse suggérée\",
                \"explanation\": \"Critères d'évaluation\"
            }
        ]
    }
    
    Règles importantes :
    - Pas de texte avant ou après le JSON
    - Questions claires et précises
    - Options de QCM pertinentes avec une seule bonne réponse
    - Explications détaillées
    - Respecter les types de questions demandés";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '?key=' . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("Erreur API Gemini: HTTP $http_code");
    }

    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Réponse invalide de l'API Gemini");
    }

    $quiz_text = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // Nettoyer le texte pour extraire le JSON
    $quiz_text = trim($quiz_text);
    $quiz_text = preg_replace('/```json\s*/', '', $quiz_text);
    $quiz_text = preg_replace('/```\s*$/', '', $quiz_text);
    
    $quiz_data = json_decode($quiz_text, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON invalide reçu de l'API: " . json_last_error_msg());
    }

    return $quiz_data;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_quiz'])) {
    try {
        $module_id = (int)$_POST['module_id'];
        $quiz_title = trim($_POST['quiz_title']);
        $topic = trim($_POST['topic']);
        $num_questions = (int)$_POST['num_questions'];
        $difficulty = $_POST['difficulty'];
        $question_types = $_POST['question_types'] ?? [];

        // Validations
        if (empty($quiz_title)) {
            throw new Exception("Le titre du quiz est requis");
        }
        if (empty($topic)) {
            throw new Exception("Le sujet du quiz est requis");
        }
        if ($num_questions < 1 || $num_questions > 20) {
            throw new Exception("Le nombre de questions doit être entre 1 et 20");
        }
        if (empty($question_types)) {
            throw new Exception("Veuillez sélectionner au moins un type de question");
        }
        if ($module_id <= 0) {
            throw new Exception("Veuillez sélectionner un module valide");
        }

        // Générer le quiz avec Gemini
        $quiz_data = generateQuizWithGemini($topic, $num_questions, $difficulty, $question_types, $GEMINI_API_KEY, $GEMINI_API_URL);
        
        if (!isset($quiz_data['questions']) || empty($quiz_data['questions'])) {
            throw new Exception("Aucune question générée par l'API");
        }

        // Créer le quiz dans la base de données
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO quizzes (title, module_id, user_id, generated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$quiz_title, $module_id, $_SESSION['user_id']]);
        $quiz_id = $pdo->lastInsertId();

        // Insérer les questions
        $question_stmt = $pdo->prepare("
            INSERT INTO quiz_questions (quiz_id, question, question_type, options, correct_answer, explanation) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($quiz_data['questions'] as $q) {
            $options = null;
            if ($q['type'] === 'qcm' && isset($q['options'])) {
                $options = json_encode($q['options']);
            }

            $question_stmt->execute([
                $quiz_id,
                $q['question'],
                $q['type'],
                $options,
                $q['correct_answer'],
                $q['explanation'] ?? ''
            ]);
        }

        $pdo->commit();
        $message = "Quiz généré avec succès ! " . count($quiz_data['questions']) . " questions créées.";
        
        // Rediriger vers le quiz généré après 2 secondes
        echo "<script>
            setTimeout(function() {
                window.location.href = 'view_quiz.php?id={$quiz_id}';
            }, 2000);
        </script>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Erreur lors de la génération : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générer un Quiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary-color: #2196F3;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .form-container {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .checkbox-item:hover {
            border-color: var(--primary-light);
            background-color: #f8f9fa;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-item.checked {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }

        .range-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .range-input {
            flex: 1;
        }

        .range-value {
            background: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .api-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .api-info h3 {
            color: #1976d2;
            margin-bottom: 0.5rem;
        }

        .api-info p {
            color: #424242;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .checkbox-group {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .range-container {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-robot"></i> Générateur de Quiz IA</h1>
            <p>Créez des quiz personnalisés avec l'intelligence artificielle Gemini</p>
        </div>

        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="api-info">
                <h3><i class="fas fa-info-circle"></i> Configuration API Gemini</h3>
                <p>Assurez-vous d'avoir configuré votre clé API Gemini dans le fichier PHP. 
                   Vous pouvez obtenir une clé gratuite sur <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.</p>
            </div>

            <form method="POST" id="quizForm">
                <div class="form-group">
                    <label for="module_id">
                        <i class="fas fa-folder"></i> Module
                    </label>
                    <select name="module_id" id="module_id" required>
                        <option value="">Sélectionnez un module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quiz_title">
                        <i class="fas fa-heading"></i> Titre du Quiz
                    </label>
                    <input type="text" id="quiz_title" name="quiz_title" required 
                           placeholder="Ex: Quiz sur les bases de JavaScript">
                </div>

                <div class="form-group">
                    <label for="topic">
                        <i class="fas fa-lightbulb"></i> Sujet du Quiz
                    </label>
                    <textarea id="topic" name="topic" rows="3" required 
                              placeholder="Décrivez précisément le sujet sur lequel vous voulez créer le quiz..."></textarea>
                </div>

                <div class="form-group">
                    <label for="num_questions">
                        <i class="fas fa-list-ol"></i> Nombre de Questions
                    </label>
                    <div class="range-container">
                        <input type="range" id="num_questions" name="num_questions" 
                               min="1" max="20" value="5" class="range-input">
                        <div class="range-value" id="questionCount">5</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="difficulty">
                        <i class="fas fa-chart-line"></i> Niveau de Difficulté
                    </label>
                    <select name="difficulty" id="difficulty" required>
                        <option value="facile">Facile</option>
                        <option value="moyen" selected>Moyen</option>
                        <option value="difficile">Difficile</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-question-circle"></i> Types de Questions
                    </label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="qcm" name="question_types[]" value="qcm" checked>
                            <label for="qcm">Questions à Choix Multiple (QCM)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="vrai_faux" name="question_types[]" value="vrai_faux">
                            <label for="vrai_faux">Questions Vrai/Faux</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="texte_libre" name="question_types[]" value="texte_libre">
                            <label for="texte_libre">Questions Ouvertes</label>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="generate_quiz" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Générer le Quiz
                    </button>
                    <a href="dashboard_user.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au Tableau de Bord
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mise à jour du compteur de questions
        const questionRange = document.getElementById('num_questions');
        const questionCount = document.getElementById('questionCount');
        
        questionRange.addEventListener('input', function() {
            questionCount.textContent = this.value;
        });

        // Gestion des cases à cocher
        document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const item = this.closest('.checkbox-item');
                if (this.checked) {
                    item.classList.add('checked');
                } else {
                    item.classList.remove('checked');
                }
            });
        });

        // Initialiser les cases cochées
        document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked').forEach(checkbox => {
            checkbox.closest('.checkbox-item').classList.add('checked');
        });

        // Afficher le loading lors de la soumission
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération en cours...';
            submitBtn.disabled = true;
        });

        // Auto-génération du titre basé sur le sujet
        document.getElementById('topic').addEventListener('blur', function() {
            const titleField = document.getElementById('quiz_title');
            if (!titleField.value && this.value) {
                const topic = this.value.substring(0, 50);
                titleField.value = `Quiz sur ${topic}${this.value.length > 50 ? '...' : ''}`;
            }
        });
    </script>
</body>
</html>