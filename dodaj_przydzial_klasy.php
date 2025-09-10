<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['klasa_id']) || !is_numeric($_GET['klasa_id'])) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$id_klasy = $_GET['klasa_id'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczyciel_id = $_POST['nauczyciel_id'];
    $przedmiot_id = $_POST['przedmiot_id'];
    
    $stmt = $polaczenie->prepare("INSERT INTO nauczanie (rok_szkolny_id, klasa_id, nauczyciel_id, przedmiot_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $aktywny_rok_id, $id_klasy, $nauczyciel_id, $przedmiot_id);
    $stmt->execute();
    
    header('Location: zarzadzaj_nauczaniem.php?klasa_id=' . $id_klasy);
    exit();
}

// Pobranie danych do formularza
$nauczyciele = $polaczenie->query("SELECT id, imie, nazwisko FROM uzytkownicy WHERE rola IN ('nauczyciel', 'dyrektor', 'admin') AND aktywny = 1 ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
$przedmioty = $polaczenie->query("SELECT id, nazwa_przedmiotu FROM przedmioty WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Przydział do Klasy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            </header>
        <main id="glowny">
            <div class="karta">
                <h1>Nowy przydział w klasie</h1>
                <form action="" method="post" style="padding: 0;">
                    Przedmiot:<br>
                    <select name="przedmiot_id" required>
                        <option value="">-- Wybierz przedmiot --</option>
                        <?php foreach ($przedmioty as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nazwa_przedmiotu']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    Nauczyciel:<br>
                    <select name="nauczyciel_id" required>
                        <option value="">-- Wybierz nauczyciela --</option>
                        <?php foreach ($nauczyciele as $n): ?>
                            <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['imie'] . ' ' . $n['nazwisko']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <input type="submit" value="Dodaj przydział" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>