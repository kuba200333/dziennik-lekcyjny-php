<?php
session_start();
if (!isset($_SESSION['zalogowany'])) { header('Location: index.php'); exit(); }

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
$id_uzytkownika = $_SESSION['uzytkownik_id'];
$rola = $_SESSION['rola'];

// Logika nawigacji po tygodniach
$offset = $_GET['offset'] ?? 0;
$dzis = new DateTime();
if ($offset != 0) { $dzis->modify("$offset week"); }
$poczatek_tygodnia = (clone $dzis)->modify('monday this week');
$koniec_tygodnia = (clone $dzis)->modify('friday this week');

// Logika wyboru widoku (klasa, nauczyciel, uczeń)
// ... (W tej wersji skupimy się na razie na widoku dla zalogowanego użytkownika)

$plan = []; // Tablica na finalny plan lekcji [dzien][nr_lekcji]

// 1. Pobierz ramowy plan lekcji
// (Tutaj powinna być rozbudowana kwerenda w zależności od roli i wyboru)
// Na razie zrobimy prosty przykład dla nauczyciela
if ($rola === 'nauczyciel' || $rola === 'dyrektor' || $rola === 'admin') {
    $sql_plan = "SELECT pl.*, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel, COALESCE(k.skrot_klasy, g.skrot_grupy) as jednostka
                 FROM plan_lekcji pl
                 JOIN nauczanie n ON pl.nauczanie_id = n.id
                 JOIN przedmioty p ON n.przedmiot_id = p.id
                 JOIN uzytkownicy u ON n.nauczyciel_id = u.id
                 LEFT JOIN klasy k ON n.klasa_id = k.id
                 LEFT JOIN grupy g ON n.grupa_id = g.id
                 WHERE pl.rok_szkolny_id = ? AND n.nauczyciel_id = ?";
    $stmt = $polaczenie->prepare($sql_plan);
    $stmt->bind_param("ii", $aktywny_rok_id, $id_uzytkownika);
    $stmt->execute();
    $wynik_planu = $stmt->get_result();
    while ($lekcja = $wynik_planu->fetch_assoc()) {
        $plan[$lekcja['dzien_tygodnia']][$lekcja['nr_lekcji']] = $lekcja;
    }
}

// 2. Pobierz zastępstwa na ten tydzień i nadpisz nimi ramowy plan
// (Tę logikę dodamy w kolejnym kroku, aby nie komplikować)

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Plan Lekcji</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="?offset=<?php echo $offset - 1; ?>" class="przycisk">&lt;&lt; Poprzedni tydzień</a>
                <h1>Plan lekcji (<?php echo $poczatek_tygodnia->format('d.m') . ' - ' . $koniec_tygodnia->format('d.m.Y'); ?>)</h1>
                <a href="?offset=<?php echo $offset + 1; ?>" class="przycisk">Następny tydzień &gt;&gt;</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Lekcja</th><th>Poniedziałek</th><th>Wtorek</th><th>Środa</th><th>Czwartek</th><th>Piątek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($nr_lekcji = 1; $nr_lekcji <= 8; $nr_lekcji++): ?>
                    <tr>
                        <td><b><?php echo $nr_lekcji; ?></b></td>
                        <?php for ($dzien = 1; $dzien <= 5; $dzien++): ?>
                            <td>
                                <?php if (isset($plan[$dzien][$nr_lekcji])): 
                                    $lekcja = $plan[$dzien][$nr_lekcji];
                                ?>
                                    <b><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></b><br>
                                    <small><?php echo htmlspecialchars($lekcja['jednostka']); ?></small><br>
                                    <small>s. <?php echo htmlspecialchars($lekcja['sala']); ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>