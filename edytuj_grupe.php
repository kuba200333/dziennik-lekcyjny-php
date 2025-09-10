<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_grupami.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$id_grupy = (int)$_GET['id'];

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nowa_nazwa = $_POST['nazwa_grupy'];
    $nowy_skrot = $_POST['skrot_grupy'];
    $nowy_opis = $_POST['opis'];
    
    $stmt = $polaczenie->prepare("UPDATE grupy SET nazwa_grupy = ?, skrot_grupy = ?, opis = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nowa_nazwa, $nowy_skrot, $nowy_opis, $id_grupy);
    
    if ($stmt->execute()) {
        header('Location: zarzadzaj_grupami.php');
        exit();
    } else {
        $komunikat_bledu = "Błąd podczas aktualizacji: " . $stmt->error;
    }
}

// Pobranie aktualnych danych grupy do wyświetlenia w formularzu
$stmt = $polaczenie->prepare("SELECT * FROM grupy WHERE id = ?");
$stmt->bind_param("i", $id_grupy);
$stmt->execute();
$grupa = $stmt->get_result()->fetch_assoc();

if (!$grupa) {
    header('Location: zarzadzaj_grupami.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Grupę</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            <div id="info-uzytkownika">
                <span><?php echo htmlspecialchars($_SESSION['imie'] . ' ' . $_SESSION['nazwisko']); ?></span>
                <div class="rola"><?php echo htmlspecialchars(ucfirst($_SESSION['rola'])); ?></div>
            </div>
            <a href="logout.php" id="wyloguj-link">Wyloguj</a>
        </header>
        <main id="glowny">
            <div class="karta">
                <h1>Edytuj grupę: <?php echo htmlspecialchars($grupa['nazwa_grupy']); ?></h1>
                <?php if (isset($komunikat_bledu)) echo '<p class="error-text">' . $komunikat_bledu . '</p>'; ?>
                <form action="" method="post" style="padding: 0;">
                    Nazwa grupy:<br>
                    <input type="text" name="nazwa_grupy" value="<?php echo htmlspecialchars($grupa['nazwa_grupy']); ?>" required>
                    
                    Skrót grupy:<br>
                    <input type="text" name="skrot_grupy" value="<?php echo htmlspecialchars($grupa['skrot_grupy']); ?>" required>
                    
                    Opis (opcjonalnie):<br>
                    <textarea name="opis"><?php echo htmlspecialchars($grupa['opis']); ?></textarea>
                    <br><br>
                    <input type="submit" value="Zapisz zmiany" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>