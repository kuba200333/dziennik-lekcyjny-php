<?php
session_start();
// Zabezpieczenie: tylko admin i tylko metodą POST
if (!isset($_SESSION['zalogowany']) || $_SESSION['rola'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';

if (isset($_POST['id_uzytkownika']) && is_numeric($_POST['id_uzytkownika'])) {
    $id_do_usuniecia = $_POST['id_uzytkownika'];
    
    // Nie można usunąć samego siebie
    if ($id_do_usuniecia == $_SESSION['uzytkownik_id']) {
        // Można ustawić komunikat błędu w sesji, jeśli chcesz
        header('Location: zarzadzaj_uzytkownikami.php');
        exit();
    }

    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    
    // Używamy zapytania przygotowanego, aby uniknąć SQL Injection
    $stmt = $polaczenie->prepare("DELETE FROM uzytkownicy WHERE id = ?");
    $stmt->bind_param("i", $id_do_usuniecia);
    $stmt->execute();
    
    $stmt->close();
    $polaczenie->close();
}

// Przekieruj z powrotem na stronę zarządzania
header('Location: zarzadzaj_uzytkownikami.php');
exit();

?>