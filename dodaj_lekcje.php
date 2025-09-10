<?php
session_start();
// Zabezpieczenia...
require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Pobieranie parametrów z URL
$klasa_id = (int)$_GET['klasa_id'];
$dzien_tygodnia = (int)$_GET['dzien'];
$nr_lekcji = (int)$_GET['nr'];

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczanie_id = $_POST['nauczanie_id'];
    $sala = $_POST['sala'];
    $data_od = $_POST['data_od'];
    $data_do = $_POST['data_do'];
    
    $stmt = $polaczenie->prepare("INSERT INTO plan_lekcji (rok_szkolny_id, dzien_tygodnia, nr_lekcji, nauczanie_id, data_od, data_do, sala) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissss", $aktywny_rok_id, $dzien_tygodnia, $nr_lekcji, $nauczanie_id, $data_od, $data_do, $sala);
    $stmt->execute();
    
    header('Location: zarzadzaj_planem.php?klasa_id=' . $klasa_id);
    exit();
}

// Pobranie listy przydziałów (nauczania) dla tej klasy
$sql = "SELECT n.id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel 
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        WHERE n.klasa_id = ? AND n.rok_szkolny_id = ?";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $klasa_id, $aktywny_rok_id);
$stmt->execute();
$przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Dodaj Lekcję do Planu</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <main id="glowny">
            <div class="karta">
                <h1>Dodaj lekcję do planu</h1>
                <p>Dzień: <strong><?php echo ['Poniedziałek','Wtorek','Środa','Czwartek','Piątek'][$dzien_tygodnia-1]; ?></strong>, Lekcja: <strong><?php echo $nr_lekcji; ?></strong></p>
                <form action="" method="post" style="padding: 0;">
                    <label>Wybierz przydział (Przedmiot - Nauczyciel):</label>
                    <select name="nauczanie_id" required>
                        <option value="">-- Wybierz --</option>
                        <?php foreach($przydzialy as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['nazwa_przedmiotu'] . ' - ' . $p['nauczyciel']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Sala lekcyjna:</label>
                    <input type="text" name="sala" required>

                    <label>Lekcja obowiązuje od:</label>
                    <input type="date" name="data_od" required>

                    <label>Lekcja obowiązuje do:</label>
                    <input type="date" name="data_do" required>
                    <br><br>
                    <input type="submit" value="Zapisz lekcję" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>