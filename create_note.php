<?php
session_start();
require 'db.php'; // Assurez-vous que ce fichier configure $pdo

// Vérification de la session et du module_id
// Cela doit être exécuté avant tout output HTML
if (!isset($_SESSION['user_id']) || !isset($_GET['module_id'])) {
    header('Location: index.php');
    exit();
}

$module_id = $_GET['module_id'];

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['content']) && !empty(trim($_POST['content']))) {
        $content = trim($_POST['content']);
        try {
            $stmt = $pdo->prepare("INSERT INTO note (content, module_id) VALUES (?, ?)");
            $stmt->execute([$content, $module_id]);
            // Optionnel: Rediriger ou afficher un message de succès
            // header('Location: view_notes.php?module_id=' . $module_id . '&status=success');
            // exit();
            $success_message = "Note ajoutée avec succès !";
        } catch (PDOException $e) {
            // Gérer l'erreur, par exemple :
            $error_message = "Erreur lors de l'ajout de la note : " . $e->getMessage();
        }
    } else {
        $form_error = "Le contenu de la note ne peut pas être vide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Note au Module <?= htmlspecialchars($module_id) ?></title>
    
</head>
<body>
<style>
        /* Styles CSS Globaux */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #e8f5e9; /* Vert très clair */
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            animation: fadeInPage 1s ease-in-out;
        }

        @keyframes fadeInPage {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            animation: slideInContainer 0.7s ease-out forwards;
            opacity: 0; /* Start hidden for animation */
            transform: translateY(20px); /* Start slightly lower for animation */
        }

        @keyframes slideInContainer {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            color: #2e7d32; /* Vert foncé */
            margin-bottom: 20px;
            font-weight: 700;
        }

        /* Style pour l'image générée par IA */
        .ai-image-container {
            margin-bottom: 30px;
            width: 100%;
            max-height: 250px; /* Ajustez selon vos besoins */
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .ai-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Assure que l'image couvre le conteneur sans se déformer */
            display: block;
        }

        /* Styles du Formulaire */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        textarea[name="content"] {
            width: calc(100% - 22px); /* Prend en compte padding et bordure */
            min-height: 120px;
            padding: 10px;
            border: 1px solid #a5d6a7; /* Vert moyen clair */
            border-radius: 5px;
            font-size: 1rem;
            resize: vertical;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        textarea[name="content"]:focus {
            border-color: #4caf50; /* Vert principal */
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
            outline: none;
        }

        button[type="submit"] {
            background-color: #4caf50; /* Vert principal */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button[type="submit"] .icon-submit {
            width: 18px;
            height: 18px;
        }

        button[type="submit"]:hover {
            background-color: #388e3c; /* Vert plus foncé pour survol */
            transform: translateY(-2px); /* Léger soulèvement au survol */
        }

        /* Keyframes pour le bouton (exemple plus subtil) */
        @keyframes pulseButton {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        button[type="submit"]:active {
             animation: pulseButton 0.3s ease-out;
        }


        /* Style du lien "Voir les notes" */
        .view-notes-link {
            display: inline-flex; /* Pour aligner l'icône et le texte */
            align-items: center; /* Centrage vertical de l'icône et du texte */
            gap: 8px; /* Espace entre l'icône et le texte */
            background-color: #66bb6a; /* Vert un peu plus clair */
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        
        .view-notes-link .icon-link {
            width: 16px;
            height: 16px;
        }

        .view-notes-link:hover {
            background-color: #57a05a; /* Vert plus foncé pour survol */
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        /* Messages de succès ou d'erreur */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .success {
            background-color: #d4edda; /* Vert clair pour succès */
            color: #155724; /* Vert foncé pour texte succès */
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da; /* Rouge clair pour erreur */
            color: #721c24; /* Rouge foncé pour texte erreur */
            border: 1px solid #f5c6cb;
        }

    </style>
    <div class="container">
        <h1>Ajouter une Note</h1>
        <p>Module ID: <strong><?= htmlspecialchars($module_id) ?></strong></p>

        <!-- <div class="ai-image-container">
            <img src="https://via.placeholder.com/600x200.png/a5d6a7/2e7d32?text=Image+Générée+par+IA+(Concept)" alt="Illustration conceptuelle pour la prise de notes">
        </div> -->

        <?php if (isset($success_message)): ?>
            <p class="message success"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="message error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if (isset($form_error)): ?>
            <p class="message error"><?= htmlspecialchars($form_error) ?></p>
        <?php endif; ?>

        <form method="POST" action="?module_id=<?= htmlspecialchars($module_id) ?>">
            <textarea name="content" required placeholder="Écrivez votre note ici..."></textarea>
            <button type="submit">
                <svg class="icon-submit" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Ajouter la Note
            </button>
        </form>

        <a href="view_notes.php?module_id=<?= htmlspecialchars($module_id) ?>" class="view-notes-link">
            <svg class="icon-link" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
            Voir les notes de ce module
        </a>
    </div>

</body>
</html>