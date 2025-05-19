<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$note_id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT note.*, module.user_id FROM note 
                       JOIN module ON note.module_id = module.id 
                       WHERE note.id = ?");
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note || $note['user_id'] != $_SESSION['user_id']) {
    echo "Accès refusé.";
    exit();
}

$stmt = $pdo->prepare("DELETE FROM note WHERE id = ?");
$stmt->execute([$note_id]);

header('Location: view_notes.php?module_id=' . $note['module_id']);
exit();