<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['nauczyciel', 'dyrektor', 'admin'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_kategoriami.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$id_kategorii = (int)$_GET['id'];
$id_uzytkownika = (int)$_SESSION['uzytkownik_id'];
$rola = $_SESSION['rola'];

// Pobranie danych kategorii w celu weryfikacji uprawnień
$stmt_check = $polaczenie->prepare("SELECT * FROM kategorie_ocen WHERE id = ?");
$stmt_check->bind_param("i", $id_kategorii);
$stmt_check->execute();
$kategoria = $stmt_check->get_result()->fetch_assoc();

if (!$kategoria) { die("Kategoria nie istnieje."); }

// Sprawdzenie uprawnień: admin może edytować wszystko, nauczyciel tylko swoje.
if ($rola !== 'admin' && $kategoria['nauczyciel_id'] != $id_uzytkownika) {
    die("Błąd: Nie masz uprawnień do edycji tej kategorii.");
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nowa_nazwa = $_POST['nazwa_kategorii'];
    $nowa_waga = $_POST['waga'];
    $nowy_kolor = $_POST['kolor'];
    
    $stmt = $polaczenie->prepare("UPDATE kategorie_ocen SET nazwa_kategorii = ?, waga = ?, kolor = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $nowa_nazwa, $nowa_waga, $nowy_kolor, $id_kategorii);
    
    if ($stmt->execute()) {
        header('Location: zarzadzaj_kategoriami.php');
        exit();
    } else {
        $komunikat_bledu = "Błąd podczas aktualizacji: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Kategorię Oceny</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Edytuj Kategorię Oceny</h1>
            <?php if (isset($komunikat_bledu)) echo '<p class="error-text">'.$komunikat_bledu.'</p>'; ?>
            <form action="" method="post" style="padding: 0;">
                Nazwa kategorii:<br>
                <input type="text" name="nazwa_kategorii" value="<?php echo htmlspecialchars($kategoria['nazwa_kategorii']); ?>" required>
                
                Waga oceny (np. 1.00, 2.50):<br>
                <input type="number" step="0.01" name="waga" value="<?php echo htmlspecialchars($kategoria['waga']); ?>" required>
                
                Kolor:<br>
                <input type="color" name="kolor" value="<?php echo htmlspecialchars($kategoria['kolor']); ?>">
                <br><br>
                <input type="submit" value="Zapisz zmiany" class="przycisk">
            </form>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>