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

// Logika wyboru widoku
$widok_klasy_id = $_GET['klasa_id'] ?? null;
$tytul_planu = "Mój plan lekcji";

// --- Budowanie zapytania SQL w zależności od roli i wyboru ---
$sql_plan = "SELECT pl.dzien_tygodnia, pl.nr_lekcji, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel, COALESCE(k.skrot_klasy, g.skrot_grupy) as jednostka, pl.sala
             FROM plan_lekcji pl
             JOIN nauczanie n ON pl.nauczanie_id = n.id
             JOIN przedmioty p ON n.przedmiot_id = p.id
             JOIN uzytkownicy u ON n.nauczyciel_id = u.id
             LEFT JOIN klasy k ON n.klasa_id = k.id
             LEFT JOIN grupy g ON n.grupa_id = g.id
             WHERE pl.rok_szkolny_id = ?";

$params = [$aktywny_rok_id];
$types = 'i';

if ($rola === 'uczen') {
    $info_ucznia = $polaczenie->query("SELECT klasa_id FROM uczniowie_info WHERE uzytkownik_id = $id_uzytkownika")->fetch_assoc();
    $klasa_ucznia_id = $info_ucznia['klasa_id'] ?? 0;
    
    $grupy_ucznia_wynik = $polaczenie->query("SELECT grupa_id FROM przypisania_grup WHERE uczen_id = $id_uzytkownika");
    $grupy_ucznia_ids = array_column($grupy_ucznia_wynik->fetch_all(MYSQLI_ASSOC), 'grupa_id');
    
    if (!empty($grupy_ucznia_ids)) {
        $grupy_ucznia_ids_string = implode(',', $grupy_ucznia_ids);
        $sql_plan .= " AND (n.klasa_id = ? OR n.grupa_id IN ($grupy_ucznia_ids_string))";
    } else {
        $sql_plan .= " AND n.klasa_id = ?";
    }
    
    $params[] = $klasa_ucznia_id;
    $types .= 'i';

} else { 
    if ($widok_klasy_id) {
        $sql_plan .= " AND n.klasa_id = ?";
        $params[] = $widok_klasy_id;
        $types .= 'i';
        $nazwa_klasy = $polaczenie->query("SELECT nazwa_klasy FROM klasy WHERE id = $widok_klasy_id")->fetch_assoc()['nazwa_klasy'];
        $tytul_planu = "Plan dla klasy: " . htmlspecialchars($nazwa_klasy);
    } else {
        $sql_plan .= " AND n.nauczyciel_id = ?";
        $params[] = $id_uzytkownika;
        $types .= 'i';
    }
}

$stmt = $polaczenie->prepare($sql_plan);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $wynik_planu = $stmt->get_result();
    $plan = [];
    while ($lekcja = $wynik_planu->fetch_assoc()) {
        $plan[$lekcja['dzien_tygodnia']][$lekcja['nr_lekcji']] = $lekcja;
    }
} else {
    die("Błąd w zapytaniu SQL: " . $polaczenie->error);
}

// Pobierz dni wolne w wyświetlanym tygodniu
$sql_wolne = "SELECT data, nazwa FROM dni_wolne WHERE rok_szkolny_id = ? AND data BETWEEN ? AND ?";
$stmt_wolne = $polaczenie->prepare($sql_wolne);
$data_start_str = $poczatek_tygodnia->format('Y-m-d');
$data_koniec_str = $koniec_tygodnia->format('Y-m-d');
$stmt_wolne->bind_param("iss", $aktywny_rok_id, $data_start_str, $data_koniec_str);
$stmt_wolne->execute();
$wynik_wolne = $stmt_wolne->get_result()->fetch_all(MYSQLI_ASSOC);
$dni_wolne_mapa = array_column($wynik_wolne, 'nazwa', 'data');

// Pobieranie listy klas do filtra
$klasy_do_wyboru = [];
if (in_array($rola, ['admin', 'dyrektor', 'nauczyciel'])) {
    $klasy_do_wyboru = $polaczenie->query("SELECT id, nazwa_klasy FROM klasy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_klasy")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Plan Lekcji</title><link rel="stylesheet" href="style.css">
    <style> .dzien-wolny { background-color: #f8f9fa; color: #6c757d; text-align: center; font-style: italic; } .dzien-wolny span { font-weight: bold; display: block; margin-top: 20px;} </style>
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
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <a href="?offset=<?php echo $offset - 1; ?>&klasa_id=<?php echo $widok_klasy_id; ?>" class="przycisk">&lt;&lt; Poprzedni tydzień</a>
                <h1><?php echo $tytul_planu; ?> <br><small style="font-size: 1rem; color: var(--kolor-tekst-drugi);"><?php echo $poczatek_tygodnia->format('d.m') . ' - ' . $koniec_tygodnia->format('d.m.Y'); ?></small></h1>
                <a href="?offset=<?php echo $offset + 1; ?>&klasa_id=<?php echo $widok_klasy_id; ?>" class="przycisk">Następny tydzień &gt;&gt;</a>
            </div>
            
            <?php if (!empty($klasy_do_wyboru)): ?>
            <div style="text-align: center; margin: 15px 0;">
                <form action="" method="get">
                    <label>Pokaż plan dla klasy:</label>
                    <select name="klasa_id" onchange="this.form.submit()">
                        <option value="">-- Mój plan --</option>
                        <?php foreach ($klasy_do_wyboru as $klasa): ?>
                            <option value="<?php echo $klasa['id']; ?>" <?php if($widok_klasy_id == $klasa['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($klasa['nazwa_klasy']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Lekcja</th>
                        <?php 
                        $temp_date = clone $poczatek_tygodnia;
                        for ($i=0; $i<5; $i++) {
                            echo '<th>' . ['Poniedziałek','Wtorek','Środa','Czwartek','Piątek'][$i] . '<br><small>' . $temp_date->format('d.m') . '</small></th>';
                            $temp_date->modify('+1 day');
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($nr_lekcji = 1; $nr_lekcji <= 8; $nr_lekcji++): ?>
                    <tr>
                        <td><b><?php echo $nr_lekcji; ?></b></td>
                        <?php 
                        $temp_date = clone $poczatek_tygodnia;
                        for ($dzien = 1; $dzien <= 5; $dzien++): 
                            $aktualna_data_str = $temp_date->format('Y-m-d');
                            $jest_wolne = isset($dni_wolne_mapa[$aktualna_data_str]);
                        ?>
                            <td class="<?php if($jest_wolne) echo 'dzien-wolny'; ?>">
                                <?php if ($jest_wolne): ?>
                                    <?php if ($nr_lekcji === 1): // Wyświetl info tylko w pierwszym wierszu, rozciągnięte na całą kolumnę ?>
                                        <div style="height: 100%; display: flex; align-items: center; justify-content: center;">
                                            <span><?php echo htmlspecialchars($dni_wolne_mapa[$aktualna_data_str]); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (isset($plan[$dzien][$nr_lekcji])): 
                                    $lekcja = $plan[$dzien][$nr_lekcji];
                                ?>
                                    <b><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></b><br>
                                    <small><?php echo htmlspecialchars($lekcja['jednostka']); ?></small><br>
                                    <?php if ($rola !== 'uczen' && $widok_klasy_id): // Pokaż nauczyciela tylko w widoku klasy ?>
                                        <small><?php echo htmlspecialchars($lekcja['nauczyciel']); ?></small><br>
                                    <?php endif; ?>
                                    <small>s. <?php echo htmlspecialchars($lekcja['sala']); ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php 
                            $temp_date->modify('+1 day');
                        endfor; ?>
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