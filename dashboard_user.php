<?php
session_start();
require 'db.php';
//require_once 'functions/ai_functions.php'; // Keep this if you use other AI functions elsewhere

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
            background-image: url(image/notepad.jpg);
            background-size: cover;
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
        .btn-secondary.two { /* Specific class for logout button */
            background-color: #f44336; /* Red color for logout */
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

        /* Styles pour le chatbot / quiz generator */
        .chatbot-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }

        .chatbot-btn {
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

        .chatbot-btn:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 8px 25px rgba(156, 39, 176, 0.4);
        }

        .chatbot-modal, .delete-modal {
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

        .chatbot-modal-content, .delete-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s ease-out;
            position: relative;
            display: flex; /* Flexbox for internal layout */
            flex-direction: column;
            max-height: 80vh; /* Limit height for scrollable content */
        }

        .delete-modal-content {
            max-width: 400px;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chatbot-modal h3, .delete-modal h3 {
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delete-modal h3 {
            color: var(--danger-dark);
        }

        .chatbot-modal select, .chatbot-modal button, .chatbot-modal input,
        .delete-modal button {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        /* Chatbot specific styles */
        #chatbox {
            flex-grow: 1; /* Allows chatbox to take available space */
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 15px;
            overflow-y: auto; /* Enable scrolling for chat history */
            background-color: #f9f9f9;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 15px;
            word-wrap: break-word;
        }

        .user-message {
            background-color: #e0f2f7; /* Light blue */
            align-self: flex-end;
            text-align: right;
        }

        .bot-message {
            background-color: #e8f5e9; /* Light green */
            align-self: flex-start;
            text-align: left;
        }

        #userInput {
            margin-bottom: 10px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        #sendChatBtn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        #sendChatBtn:hover {
            background-color: var(--primary-dark);
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

    <!-- start supresion des modules -->
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
<!-- strat html -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>ChatBot</title>
    <link rel="stylesheet" href="style1.css" />
  </head>
  <body>
    <div id="chatbot-icon"><i class="fa-solid fa-brain"></i></div>
    <div id="chatbot-container" class="hidden">
      <div id="chatbot-header">
        <span>Chat with ai </span>
        <button id="close-btn">&times;</button>
      </div>
      <div id="chatbot-body">
        <div id="chatbot-messages"></div>
      </div>
      <div id="chatbot-input-container">
        <input type="text" id="chatbot-input" placeholder="Type a message" autofocus />
        <button id="send-btn">Send</button>
      </div>
    </div>
    <!-- end html start js -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
  const chatbotContainer = document.getElementById("chatbot-container");
  const clostBtn = document.getElementById("close-btn");
  const sendBtn = document.getElementById("send-btn");
  const chatBotInput = document.getElementById("chatbot-input");
  const chatbotMessages = document.getElementById("chatbot-messages");
  const chatbotIcon = document.getElementById("chatbot-icon");

  chatbotIcon.addEventListener("click", () => {
    chatbotContainer.classList.remove("hidden");
    chatbotIcon.style.display = "none";
  });
  clostBtn.addEventListener("click", () => {
    chatbotContainer.classList.add("hidden");
    chatbotIcon.style.display = "flex";
  });

  sendBtn.addEventListener("click", sendMessage);

  chatBotInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
});

function sendMessage() {
  const inputField = document.getElementById("chatbot-input");
  const userMessage = inputField.value.trim();

  if (userMessage) {
    appendMessage("user", userMessage);
    inputField.value = ""; // Effacer l'input après envoi
    getBotResponse(userMessage);
  }
}


function appendMessage(sender, message) {
  const messageContainer = document.getElementById("chatbot-messages");
  const messageElement = document.createElement("div");
  messageElement.classList.add("message", sender);
  messageElement.textContent = message;
  messageContainer.appendChild(messageElement);
  messageContainer.scrollTop = messageContainer.scrollHeight;
}

async function getBotResponse(userMessage) {
  const API_KEY = "AIzaSyCw7Phve7Gu42MaJK29uHj41TgYBpkce5c";  // create API key
  const API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${API_KEY}`;

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [
          {
            parts: [{ text: userMessage }],
          },
        ],
      }),
    });

    const data = await response.json();

    if (!data.candidates || !data.candidates.length) {
      throw new Error("No response from Gemini API");
    }

    const botMessage = data.candidates[0].content.parts[0].text;
    appendMessage("bot", botMessage);
  } catch (error) {
    console.error("Error:", error);
    appendMessage(
      "bot",
      "Sorry, I'm having trouble responding. Please try again."
    );
  }
}
    </script>
  </body>
</html>
</body>
</html>

