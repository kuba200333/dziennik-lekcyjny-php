<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

$wybrana_data = $_GET['data'] ?? date('Y-m-d');
$dzien_tygodnia = date('N', strtotime($wybrana_data));

// Pobierz plan na wybrany dzień
$sql = "SELECT pl.id, pl.nr_lekcji, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel, COALESCE(k.skrot_klasy, g.skrot_grupy) as jednostka
        FROM plan_lekcji pl
        JOIN nauczanie n ON pl.nauczanie_id = n.id
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        LEFT JOIN klasy k ON n.klasa_id = k.id
        LEFT JOIN grupy g ON n.grupa_id = g.id
        WHERE pl.rok_szkolny_id = ? AND pl.dzien_tygodnia = ?
        ORDER BY pl.nr_lekcji";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $aktywny_rok_id, $dzien_tygodnia);
$stmt->execute();
$plan_dnia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Zarządzanie Zastępstwami</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
         </header>
    <main id="glowny">
        <div class="karta">
            <h1>Zarządzanie Zastępstwami</h1>
            <form action="" method="get">
                <label for="data">Wybierz datę:</label>
                <input type="date" name="data" value="<?php echo $wybrana_data; ?>" onchange="this.form.submit()">
            </form>
            <hr>
            <h3>Plan na dzień: <?php echo $wybrana_data; ?></h3>
            <table>
                <thead><tr><th>Lekcja</th><th>Przedmiot</th><th>Klasa/Grupa</th><th>Nauczyciel</th><th>Akcja</th></tr></thead>
                <tbody>
                    <?php foreach ($plan_dnia as $lekcja): ?>
                    <tr>
                        <td><?php echo $lekcja['nr_lekcji']; ?></td>
                        <td><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></td>
                        <td><?php echo htmlspecialchars($lekcja['jednostka']); ?></td>
                        <td><?php echo htmlspecialchars($lekcja['nauczyciel']); ?></td>
                        <td><a href="#" class="przycisk">Zarządzaj</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>