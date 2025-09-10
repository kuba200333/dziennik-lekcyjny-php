<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['nauczyciel', 'dyrektor', 'admin'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
$id_nauczyciela = $_SESSION['uzytkownik_id'];

$wybrana_data = $_GET['data'] ?? date('Y-m-d');
$dzien_tygodnia = date('N', strtotime($wybrana_data));

// Pobieramy plan lekcji nauczyciela na wybrany dzień, łącząc z zrealizowanymi tematami
$sql = "SELECT 
            pl.nr_lekcji,
            n.id as nauczanie_id,
            p.nazwa_przedmiotu, 
            COALESCE(k.nazwa_klasy, g.nazwa_grupy) as jednostka,
            rt.id as realizacja_id,
            rt.temat
        FROM plan_lekcji pl
        JOIN nauczanie n ON pl.nauczanie_id = n.id
        JOIN przedmioty p ON n.przedmiot_id = p.id
        LEFT JOIN klasy k ON n.klasa_id = k.id
        LEFT JOIN grupy g ON n.grupa_id = g.id
        LEFT JOIN realizacja_tematu rt ON n.id = rt.nauczanie_id AND rt.data = ? AND rt.nr_lekcji = pl.nr_lekcji
        WHERE n.nauczyciel_id = ? 
        AND pl.dzien_tygodnia = ?
        AND ? BETWEEN pl.data_od AND pl.data_do
        ORDER BY pl.nr_lekcji";

$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("siis", $wybrana_data, $id_nauczyciela, $dzien_tygodnia, $wybrana_data);
$stmt->execute();
$plan_dnia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Realizacja Programu</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Realizacja Programu Nauczania</h1>
            <form action="" method="get">
                <label for="data">Wybierz datę:</label>
                <input type="date" name="data" value="<?php echo $wybrana_data; ?>" onchange="this.form.submit()">
            </form>
            <hr>
            <table>
                <thead><tr><th>Lekcja</th><th>Przedmiot</th><th>Klasa/Grupa</th><th>Temat lekcji</th><th>Akcje</th></tr></thead>
                <tbody>
                    <?php foreach ($plan_dnia as $lekcja): ?>
                    <tr>
                        <td><?php echo $lekcja['nr_lekcji']; ?></td>
                        <td><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></td>
                        <td><?php echo htmlspecialchars($lekcja['jednostka']); ?></td>
                        <td><?php echo htmlspecialchars($lekcja['temat'] ?? '<em>Brak tematu</em>'); ?></td>
                        <td>
                            <?php if ($lekcja['realizacja_id']): ?>
                                <a href="sprawdz_obecnosc.php?realizacja_id=<?php echo $lekcja['realizacja_id']; ?>" class="przycisk">Edytuj</a>
                            <?php else: ?>
                                <a href="sprawdz_obecnosc.php?nauczanie_id=<?php echo $lekcja['nauczanie_id']; ?>&data=<?php echo $wybrana_data; ?>&nr=<?php echo $lekcja['nr_lekcji']; ?>" class="przycisk">Rozpocznij lekcję</a>
                            <?php endif; ?>
                        </td>
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