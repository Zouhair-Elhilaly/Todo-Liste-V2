<?php
session_start();
require 'db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer l'ID du quiz depuis l'URL
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($quiz_id <= 0) {
    header('Location: dashboard_user.php');
    exit();
}

// Récupérer les informations du quiz
$stmt = $pdo->prepare("
    SELECT q.*, m.name as module_name 
    FROM quizzes q
    JOIN module m ON q.module_id = m.id
    WHERE q.id = ? AND q.user_id = ?
");
$stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $stmt->fetch();

// Vérifier que le quiz existe et appartient à l'utilisateur
if (!$quiz) {
    header('Location: dashboard_user.php');
    exit();
}

// Récupérer les questions du quiz
$stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Traitement des réponses du quiz
$results = [];
$score = 0;
$total_questions = count($questions);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    foreach ($questions as $question) {
        $user_answer = $_POST['answer_' . $question['id']] ?? '';
        $is_correct = false;
        
        // Vérifier la réponse selon le type de question
        switch ($question['question_type']) {
            case 'qcm':
                $is_correct = (strtolower(trim($user_answer)) === strtolower(trim($question['correct_answer'])));
                break;
                
            case 'vrai_faux':
                $is_correct = ($user_answer === $question['correct_answer']);
                break;
                
            case 'texte_libre':
                // Pour les questions ouvertes, on considère que c'est correct si une réponse a été donnée
                $is_correct = !empty(trim($user_answer));
                break;
        }
        
        if ($is_correct) {
            $score++;
        }
        
        $results[$question['id']] = [
            'user_answer' => $user_answer,
            'is_correct' => $is_correct
        ];
    }
    
    // Calculer le score en pourcentage
    $score_percent = round(($score / $total_questions) * 100);
    
    // Enregistrer le résultat (optionnel)
    $stmt = $pdo->prepare("INSERT INTO quiz_results (quiz_id, user_id, score) VALUES (?, ?, ?)");
    $stmt->execute([$quiz_id, $_SESSION['user_id'], $score_percent]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?= htmlspecialchars($quiz['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary-color: #2196F3;
            --error-color: #f44336;
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
        }

        .quiz-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-light);
        }

        .quiz-header h1 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .quiz-header p {
            color: #666;
        }

        .quiz-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: var(--light-gray);
            border-radius: 5px;
        }

        .quiz-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .question {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: var(--white);
            border: 1px solid #eee;
            border-radius: 8px;
            position: relative;
        }

        .question-number {
            position: absolute;
            top: -12px;
            left: 20px;
            background-color: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .options {
            margin-left: 1.5rem;
        }

        .option {
            margin-bottom: 0.8rem;
        }

        .option input[type="radio"] {
            margin-right: 10px;
        }

        .option label {
            cursor: pointer;
        }

        .text-answer {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 0.5rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
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
        }

        .results {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--light-gray);
            border-radius: 8px;
            text-align: center;
        }

        .score {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .feedback {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 5px;
        }

        .correct {
            background-color: #E8F5E9;
            border-left: 4px solid var(--primary-color);
        }

        .incorrect {
            background-color: #FFEBEE;
            border-left: 4px solid var(--error-color);
        }

        .explanation {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .quiz-container {
                padding: 1rem;
                margin: 1rem;
            }
            
            .quiz-info {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <div class="quiz-header">
            <h1><?= htmlspecialchars($quiz['title']) ?></h1>
            <p>Module: <?= htmlspecialchars($quiz['module_name']) ?></p>
        </div>
        
        <div class="quiz-info">
            <div class="quiz-info-item">
                <i class="fas fa-question-circle"></i>
                <span>Questions: <?= count($questions) ?></span>
            </div>
            <div class="quiz-info-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Créé le: <?= date('d/m/Y', strtotime($quiz['generated_at'])) ?></span>
            </div>
        </div>
        
        <?php if (!empty($results)): ?>
            <div class="results">
                <div class="score">Votre score: <?= $score_percent ?>%</div>
                <p>Vous avez répondu correctement à <?= $score ?> sur <?= $total_questions ?> questions.</p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question">
                    <div class="question-number"><?= $index + 1 ?></div>
                    <div class="question-text"><?= htmlspecialchars($question['question']) ?></div>
                    
                    <?php if (!empty($results)): ?>
                        <div class="feedback <?= $results[$question['id']]['is_correct'] ? 'correct' : 'incorrect' ?>">
                            Votre réponse: <?= htmlspecialchars($results[$question['id']]['user_answer']) ?>
                            <?php if ($question['explanation']): ?>
                                <div class="explanation"><?= htmlspecialchars($question['explanation']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="options">
                        <?php if ($question['question_type'] === 'qcm'): ?>
                            <?php $options = json_decode($question['options'], true); ?>
                            <?php foreach ($options as $option): ?>
                                <div class="option">
                                    <input type="radio" 
                                           id="answer_<?= $question['id'] ?>_<?= md5($option) ?>" 
                                           name="answer_<?= $question['id'] ?>" 
                                           value="<?= htmlspecialchars($option) ?>"
                                           <?= (!empty($results) && $results[$question['id']]['user_answer'] === $option) ? 'checked' : '' ?>
                                           <?= !empty($results) ? 'disabled' : '' ?>>
                                    <label for="answer_<?= $question['id'] ?>_<?= md5($option) ?>">
                                        <?= htmlspecialchars($option) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                        <?php elseif ($question['question_type'] === 'vrai_faux'): ?>
                            <div class="option">
                                <input type="radio" 
                                       id="answer_<?= $question['id'] ?>_true" 
                                       name="answer_<?= $question['id'] ?>" 
                                       value="Vrai"
                                       <?= (!empty($results) && $results[$question['id']]['user_answer'] === 'Vrai') ? 'checked' : '' ?>
                                       <?= !empty($results) ? 'disabled' : '' ?>>
                                <label for="answer_<?= $question['id'] ?>_true">Vrai</label>
                            </div>
                            <div class="option">
                                <input type="radio" 
                                       id="answer_<?= $question['id'] ?>_false" 
                                       name="answer_<?= $question['id'] ?>" 
                                       value="Faux"
                                       <?= (!empty($results) && $results[$question['id']]['user_answer'] === 'Faux') ? 'checked' : '' ?>
                                       <?= !empty($results) ? 'disabled' : '' ?>>
                                <label for="answer_<?= $question['id'] ?>_false">Faux</label>
                            </div>
                            
                        <?php elseif ($question['question_type'] === 'texte_libre'): ?>
                            <textarea class="text-answer" 
                                      name="answer_<?= $question['id'] ?>"
                                      <?= !empty($results) ? 'readonly' : '' ?>
                                      placeholder="Votre réponse..."><?= !empty($results) ? htmlspecialchars($results[$question['id']]['user_answer']) : '' ?></textarea>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="actions">
                <a href="dashboard_user.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                
                <?php if (empty($results)): ?>
                    <button type="submit" name="submit_quiz" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Soumettre le quiz
                    </button>
                <?php else: ?>
                    <a href="view_quiz.php?id=<?= $quiz_id ?>" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Recommencer
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</body>
</html>