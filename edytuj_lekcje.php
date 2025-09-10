<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php'); exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_planem.php'); exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$id_lekcji = (int)$_GET['id'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

$stmt_lekcja = $polaczenie->prepare("SELECT pl.*, n.klasa_id, n.grupa_id FROM plan_lekcji pl JOIN nauczanie n ON pl.nauczanie_id = n.id WHERE pl.id = ?");
$stmt_lekcja->bind_param("i", $id_lekcji);
$stmt_lekcja->execute();
$lekcja = $stmt_lekcja->get_result()->fetch_assoc();
if (!$lekcja) { die("Nie znaleziono lekcji."); }

$jednostka_id = $lekcja['klasa_id'] ?? $lekcja['grupa_id'];
$typ_jednostki = $lekcja['klasa_id'] ? 'klasa' : 'grupa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczanie_id = $_POST['nauczanie_id'];
    $sala = $_POST['sala'];
    $data_od = $_POST['data_od'];
    $data_do = $_POST['data_do'];
    
    $stmt_update = $polaczenie->prepare("UPDATE plan_lekcji SET nauczanie_id = ?, sala = ?, data_od = ?, data_do = ? WHERE id = ?");
    $stmt_update->bind_param("isssi", $nauczanie_id, $sala, $data_od, $data_do, $id_lekcji);
    $stmt_update->execute();
    
    header('Location: zarzadzaj_planem.php?jednostka=' . $typ_jednostki . '-' . $jednostka_id);
    exit();
}

$warunek_sql = $lekcja['klasa_id'] ? "n.klasa_id = ?" : "n.grupa_id = ?";
$sql_przydzialy = "SELECT n.id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id = p.id JOIN uzytkownicy u ON n.nauczyciel_id = u.id WHERE $warunek_sql AND n.rok_szkolny_id = ?";
$stmt_przydzialy = $polaczenie->prepare($sql_przydzialy);
$stmt_przydzialy->bind_param("ii", $jednostka_id, $aktywny_rok_id);
$stmt_przydzialy->execute();
$przydzialy = $stmt_przydzialy->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl"><head><meta charset="UTF-8"><title>Edytuj Lekcję</title><link rel="stylesheet" href="style.css"></head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek"></header>
    <main id="glowny">
        <div class="karta">
            <h1>Edytuj lekcję w planie</h1>
            <form action="" method="post" style="padding: 0;">
                <label>Wybierz przydział:</label>
                <select name="nauczanie_id" required>
                    <?php foreach($przydzialy as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if($p['id'] == $lekcja['nauczanie_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['nazwa_przedmiotu'] . ' - ' . $p['nauczyciel']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <label>Sala:</label><input type="text" name="sala" value="<?php echo htmlspecialchars($lekcja['sala']); ?>" required>
                <label>Obowiązuje od:</label><input type="date" name="data_od" value="<?php echo htmlspecialchars($lekcja['data_od']); ?>" required>
                <label>Obowiązuje do:</label><input type="date" name="data_do" value="<?php echo htmlspecialchars($lekcja['data_do']); ?>" required>
                <br><br><input type="submit" value="Zapisz zmiany" class="przycisk">
            </form>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body></html>