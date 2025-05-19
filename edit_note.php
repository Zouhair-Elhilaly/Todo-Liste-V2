<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    echo "<div class='error-message'><i class='fas fa-exclamation-triangle'></i> ID de note non fourni.</div>";
    exit();
}

$note_id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT note.*, module.user_id FROM note
                        JOIN module ON note.module_id = module.id
                        WHERE note.id = ?");
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note || $note['user_id'] != $_SESSION['user_id']) {
    echo "<div class='error-message'><i class='fas fa-ban'></i> Accès refusé.</div>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("UPDATE note SET content = ? WHERE id = ?");
        $stmt->execute([$content, $note_id]);
        header('Location: view_notes.php?module_id=' . $note['module_id']);
        exit();
    } else {
        $error = "Le contenu ne peut pas être vide.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la note</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }

        h2 {
            color: #28a745;
            text-align: center;
            margin-bottom: 25px;
            font-size: 2em;
            position: relative;
        }

        h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background-color: #28a745;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        form {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        textarea {
            display: block;
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            line-height: 1.5;
            box-sizing: border-box;
            min-height: 150px;
        }

        button[type="submit"] {
            background-color: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease-in-out;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease-in-out;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error-message i {
            margin-right: 8px;
        }

        p[style='color:red;'] {
            color: #dc3545;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Modifier la note</h2>
        <form method="POST">
            <textarea name="content" required><?= htmlspecialchars($note['content']) ?></textarea>
            <button type="submit"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <a href="view_notes.php?module_id=<?= $note['module_id'] ?>"><i class="fas fa-arrow-left"></i> Annuler</a>
    </div>
</body>
</html>