<?php
session_start();

if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header('Location: index.php');
    exit();
}

$imie = $_SESSION['imie'];
$nazwisko = $_SESSION['nazwisko'];
$rola = $_SESSION['rola'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dziennik Elektroniczny - Strona Główna</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div id="kontener">
        
        <?php require_once 'szablony/menu.php'; ?>

        <header id="naglowek">
            <div id="info-uzytkownika">
                <span><?php echo htmlspecialchars($imie . ' ' . $nazwisko); ?></span>
                <div class="rola"><?php echo htmlspecialchars(ucfirst($rola)); ?></div>
            </div>
            <a href="logout.php" id="wyloguj-link">Wyloguj</a>
        </header>

        <main id="glowny">
            <div class="karta">
                <h1>Panel Główny</h1>
                <p>Witaj w nowym Dzienniku Elektronicznym! Wybierz jedną z opcji w menu po lewej stronie, aby rozpocząć.</p>
            </div>
        </main>
        
        <footer id="stopka">
            &copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny
        </footer>

    </div>
</body>
</html>