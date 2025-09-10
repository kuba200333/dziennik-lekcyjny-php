<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php'); exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczyciel_id = $_POST['nauczyciel_id'];
    $data_od = $_POST['data_od'];
    $data_do = $_POST['data_do'];
    $lekcja_od = $_POST['lekcja_od'] ?: NULL;
    $lekcja_do = $_POST['lekcja_do'] ?: NULL;
    $powod = $_POST['powod'];
    
    $stmt = $polaczenie->prepare("INSERT INTO nieobecnosci_nauczycieli (rok_szkolny_id, nauczyciel_id, data_od, data_do, lekcja_od, lekcja_do, powod) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssis", $aktywny_rok_id, $nauczyciel_id, $data_od, $data_do, $lekcja_od, $lekcja_do, $powod);
    $stmt->execute();
    
    header('Location: zarzadzaj_zastepstwami.php');
    exit();
}

$nauczyciele = $polaczenie->query("SELECT id, imie, nazwisko FROM uzytkownicy WHERE rola IN ('nauczyciel', 'dyrektor', 'admin') AND aktywny = 1 ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Dodaj Nieobecność Nauczyciela</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            </header>
        <main id="glowny">
            <div class="karta">
                <h1>Zgłoś Nieobecność Nauczyciela</h1>
                <form action="" method="post" style="padding: 0;">
                    <label>Nauczyciel:</label>
                    <select name="nauczyciel_id" required>
                        <option value="">-- Wybierz nauczyciela --</option>
                        <?php foreach($nauczyciele as $n): ?>
                        <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['nazwisko'] . ' ' . $n['imie']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label>Data od:</label><input type="date" name="data_od" required></div>
                        <div><label>Data do:</label><input type="date" name="data_do" required></div>
                        <div><label>Od lekcji (opcjonalnie):</label><input type="number" name="lekcja_od"></div>
                        <div><label>Do lekcji (opcjonalnie):</label><input type="number" name="lekcja_do"></div>
                    </div>

                    <label>Powód (opcjonalnie):</label>
                    <input type="text" name="powod">
                    <br><br>
                    <input type="submit" value="Zapisz nieobecność" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>