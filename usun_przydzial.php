<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';

if (isset($_POST['id_przydzialu']) && is_numeric($_POST['id_przydzialu'])) {
    $id_do_usuniecia = $_POST['id_przydzialu'];
    
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    
    $stmt = $polaczenie->prepare("DELETE FROM nauczanie WHERE id = ?");
    $stmt->bind_param("i", $id_do_usuniecia);
    $stmt->execute();
    
    $stmt->close();
    $polaczenie->close();
}

// Wróć na poprzednią stronę
$redirect_url = $_POST['redirect_url'] ?? 'dziennik.php';
header('Location: ' . $redirect_url);
exit();
?>