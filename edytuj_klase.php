<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Sprawdzamy, czy ID klasy jest podane
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$id_klasy = $_GET['id'];

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa_klasy = $_POST['nazwa_klasy'];
    $skrot_klasy = $_POST['skrot_klasy'];
    $wychowawca_id = $_POST['wychowawca_id'] ?: NULL;
    
    $stmt = $polaczenie->prepare("UPDATE klasy SET nazwa_klasy=?, skrot_klasy=?, wychowawca_id=? WHERE id=?");
    $stmt->bind_param("ssii", $nazwa_klasy, $skrot_klasy, $wychowawca_id, $id_klasy);
    $stmt->execute();
    
    header('Location: zarzadzaj_klasami.php');
    exit();
}

// Pobranie danych klasy do formularza
$stmt_klasa = $polaczenie->prepare("SELECT nazwa_klasy, skrot_klasy, wychowawca_id FROM klasy WHERE id = ?");
$stmt_klasa->bind_param("i", $id_klasy);
$stmt_klasa->execute();
$klasa_wynik = $stmt_klasa->get_result();
if ($klasa_wynik->num_rows === 0) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$klasa = $klasa_wynik->fetch_assoc();

// Pobranie listy nauczycieli
$nauczyciele_wynik = $polaczenie->query("SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u WHERE u.rola IN ('nauczyciel', 'dyrektor', 'admin') AND u.aktywny = 1 ORDER BY u.nazwisko");
$nauczyciele = $nauczyciele_wynik->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Klasę</title>
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
                <h1>Edytuj klasę: <?php echo htmlspecialchars($klasa['nazwa_klasy']); ?></h1>
                <form action="" method="post" style="padding: 0;">
                    Pełna nazwa klasy: <br>
                    <input type="text" name="nazwa_klasy" value="<?php echo htmlspecialchars($klasa['nazwa_klasy']); ?>" required>
                    
                    Skrót klasy: <br>
                    <input type="text" name="skrot_klasy" value="<?php echo htmlspecialchars($klasa['skrot_klasy']); ?>" required>
                    
                    Wychowawca: <br>
                    <select name="wychowawca_id">
                        <option value="">-- Brak --</option>
                        <?php foreach ($nauczyciele as $nauczyciel): ?>
                            <option value="<?php echo $nauczyciel['id']; ?>" <?php if($klasa['wychowawca_id'] == $nauczyciel['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($nauczyciel['imie'] . ' ' . $nauczyciel['nazwisko']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <input type="submit" value="Zapisz zmiany" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>