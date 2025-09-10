<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

$klasy = $polaczenie->query("SELECT id, nazwa_klasy FROM klasy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_klasy")->fetch_all(MYSQLI_ASSOC);
$wybrana_klasa_id = $_GET['klasa_id'] ?? null;
$plan_lekcji = [];

if ($wybrana_klasa_id) {
    // Pobieramy cały plan dla wybranej klasy i grupujemy go w czytelnej tablicy
    $sql = "SELECT pl.id, pl.dzien_tygodnia, pl.nr_lekcji, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel, pl.sala
            FROM plan_lekcji pl
            JOIN nauczanie n ON pl.nauczanie_id = n.id
            JOIN przedmioty p ON n.przedmiot_id = p.id
            JOIN uzytkownicy u ON n.nauczyciel_id = u.id
            WHERE pl.rok_szkolny_id = ? AND n.klasa_id = ?
            ORDER BY pl.nr_lekcji, pl.dzien_tygodnia";
    $stmt = $polaczenie->prepare($sql);
    $stmt->bind_param("ii", $aktywny_rok_id, $wybrana_klasa_id);
    $stmt->execute();
    $wynik = $stmt->get_result();
    while($lekcja = $wynik->fetch_assoc()) {
        $plan_lekcji[$lekcja['dzien_tygodnia']][$lekcja['nr_lekcji']] = $lekcja;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Zarządzanie Planem Lekcji</title><link rel="stylesheet" href="style.css">
    <style> .plan-komorka { vertical-align: top; height: 100px; } .plan-akcje a { display: block; margin-top: 5px; } </style>
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Zarządzanie Planem Lekcji</h1>
            <form action="" method="get">
                <label for="klasa_id">Wybierz klasę, aby edytować jej plan:</label>
                <select name="klasa_id" id="klasa_id" onchange="this.form.submit()">
                    <option value="">-- Wybierz --</option>
                    <?php foreach ($klasy as $klasa): ?>
                        <option value="<?php echo $klasa['id']; ?>" <?php if($wybrana_klasa_id == $klasa['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($klasa['nazwa_klasy']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($wybrana_klasa_id): ?>
            <hr style="margin: 20px 0;">
            <div style="overflow-x: auto;">
            <table>
                <thead><tr><th>Lekcja</th><th>Poniedziałek</th><th>Wtorek</th><th>Środa</th><th>Czwartek</th><th>Piątek</th></tr></thead>
                <tbody>
                    <?php for ($nr_lekcji = 1; $nr_lekcji <= 8; $nr_lekcji++): ?>
                    <tr>
                        <td><b><?php echo $nr_lekcji; ?></b></td>
                        <?php for ($dzien = 1; $dzien <= 5; $dzien++): ?>
                            <td class="plan-komorka">
                                <?php if (isset($plan_lekcji[$dzien][$nr_lekcji])): 
                                    $lekcja = $plan_lekcji[$dzien][$nr_lekcji];
                                ?>
                                    <b><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></b><br>
                                    <small><?php echo htmlspecialchars($lekcja['nauczyciel']); ?></small><br>
                                    <small>s. <?php echo htmlspecialchars($lekcja['sala']); ?></small>
                                    <div class="plan-akcje">
                                        <a href="#" class="przycisk">Edytuj</a>
                                        <a href="#" class="przycisk przycisk-usun">Usuń</a>
                                    </div>
                                <?php else: ?>
                                    <a href="dodaj_lekcje.php?klasa_id=<?php echo $wybrana_klasa_id; ?>&dzien=<?php echo $dzien; ?>&nr=<?php echo $nr_lekcji; ?>" class="przycisk">+ Dodaj</a>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>