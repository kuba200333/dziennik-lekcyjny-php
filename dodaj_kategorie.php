<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor', 'nauczyciel'])) {
    header('Location: dziennik.php');
    exit();
}

$id_nauczyciela = $_SESSION['uzytkownik_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connect.php';
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    
    $nazwa = $_POST['nazwa_kategorii'];
    $waga = $_POST['waga'];
    $kolor = $_POST['kolor'];
    
    // Zapisujemy kategorię z ID zalogowanego nauczyciela
    $stmt = $polaczenie->prepare("INSERT INTO kategorie_ocen (nazwa_kategorii, waga, kolor, nauczyciel_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdsi", $nazwa, $waga, $kolor, $id_nauczyciela);
    
    if ($stmt->execute()) {
        header('Location: zarzadzaj_kategoriami.php');
        exit();
    } else {
        $komunikat_bledu = "Błąd: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Własną Kategorię</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            </header>
        <main id="glowny">
            <div class="karta">
                <h1>Dodaj Własną Kategorię Oceny</h1>
                <?php if (isset($komunikat_bledu)) echo '<p class="error-text">'.$komunikat_bledu.'</p>'; ?>
                <form action="" method="post" style="padding: 0;">
                    Nazwa kategorii:<br>
                    <input type="text" name="nazwa_kategorii" required>
                    
                    Waga oceny (np. 1.00, 2.50):<br>
                    <input type="number" step="0.01" name="waga" required>
                    
                    Kolor (opcjonalnie):<br>
                    <input type="color" name="kolor" value="#3498db">
                    <br><br>
                    <input type="submit" value="Dodaj kategorię" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>