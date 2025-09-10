<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';

if (isset($_POST['id_lekcji']) && is_numeric($_POST['id_lekcji'])) {
    $id_do_usuniecia = $_POST['id_lekcji'];
    
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    
    $stmt = $polaczenie->prepare("DELETE FROM plan_lekcji WHERE id = ?");
    $stmt->bind_param("i", $id_do_usuniecia);
    $stmt->execute();
    
    $stmt->close();
    $polaczenie->close();
}

$redirect_jednostka = $_POST['redirect_jednostka'] ?? null;
header('Location: zarzadzaj_planem.php' . ($redirect_jednostka ? '?jednostka=' . $redirect_jednostka : ''));
exit();
?>