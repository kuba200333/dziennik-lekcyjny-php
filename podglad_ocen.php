<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// --- POBIERANIE DANYCH DO FILTRÓW ---
$klasy_i_grupy = [];
$klasy = $polaczenie->query("SELECT id, nazwa_klasy FROM klasy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_klasy")->fetch_all(MYSQLI_ASSOC);
foreach ($klasy as $klasa) {
    $klasy_i_grupy['klasa-' . $klasa['id']] = $klasa['nazwa_klasy'];
}
$grupy = $polaczenie->query("SELECT id, nazwa_grupy FROM grupy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
foreach ($grupy as $grupa) {
    $klasy_i_grupy['grupa-' . $grupa['id']] = 'Grupa: ' . $grupa['nazwa_grupy'];
}

$przydzialy = [];
$jednostka_id = null;
$jednostka_typ = null;
$jednostka_query = $_GET['jednostka'] ?? '';
$przedmiot_query = $_GET['przedmiot_id'] ?? '';


if ($jednostka_query) {
    list($jednostka_typ, $jednostka_id) = explode('-', $jednostka_query);
    $jednostka_id = (int)$jednostka_id;

    $sql_przydzialy = ($jednostka_typ === 'klasa')
        ? "SELECT p.id, p.nazwa_przedmiotu FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id = p.id WHERE n.klasa_id = ? AND n.rok_szkolny_id = ? GROUP BY p.id ORDER BY p.nazwa_przedmiotu"
        : "SELECT p.id, p.nazwa_przedmiotu FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id = p.id WHERE n.grupa_id = ? AND n.rok_szkolny_id = ? GROUP BY p.id ORDER BY p.nazwa_przedmiotu";
    
    $stmt = $polaczenie->prepare($sql_przydzialy);
    $stmt->bind_param("ii", $jednostka_id, $aktywny_rok_id);
    $stmt->execute();
    $przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Funkcja pomocnicza do formatowania ocen
function formatujOcene($ocena) {
    if ($ocena === null) return '';
    $ocena_float = (float)$ocena;
    if ($ocena_float == 1.5) return '1+';
    if ($ocena_float == 1.75) return '2-';
    if ($ocena_float == 2.5) return '2+';
    if ($ocena_float == 2.75) return '3-';
    if ($ocena_float == 3.5) return '3+';
    if ($ocena_float == 3.75) return '4-';
    if ($ocena_float == 4.5) return '4+';
    if ($ocena_float == 4.75) return '5-';
    if ($ocena_float == 5.5) return '5+';
    if ($ocena_float == 5.75) return '6-';
    return (string)(int)$ocena_float;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Podgląd i Zarządzanie Ocenami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        td a[title] { text-decoration: none; }
    </style>
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
            <h1>Podgląd i Zarządzanie Ocenami</h1>
            <form action="" method="get">
                <select name="jednostka" onchange="this.form.submit()">
                    <option value="">-- Wybierz klasę lub grupę --</option>
                    <?php foreach ($klasy_i_grupy as $key => $nazwa): ?>
                        <option value="<?php echo $key; ?>" <?php if($jednostka_query == $key) echo 'selected'; ?>><?php echo htmlspecialchars($nazwa); ?></option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($przydzialy)): ?>
                    <select name="przedmiot_id" onchange="this.form.submit()">
                        <option value="">-- Wybierz przedmiot --</option>
                        <?php foreach ($przydzialy as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php if($przedmiot_query == $p['id']) echo 'selected'; ?>><?php echo htmlspecialchars($p['nazwa_przedmiotu']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </form>

            <?php if ($przedmiot_query): 
                $przedmiot_id = (int)$przedmiot_query;

                // Pobierz uczniów dla wybranej jednostki
                if ($jednostka_typ === 'klasa') {
                    $uczniowie_sql = "SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN uczniowie_info ui ON u.id=ui.uzytkownik_id WHERE ui.klasa_id = ? ORDER BY u.nazwisko, u.imie";
                } else {
                    $uczniowie_sql = "SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN przypisania_grup pg ON u.id=pg.uczen_id WHERE pg.grupa_id = ? ORDER BY u.nazwisko, u.imie";
                }
                $stmt_uczniowie = $polaczenie->prepare($uczniowie_sql);
                $stmt_uczniowie->bind_param("i", $jednostka_id);
                $stmt_uczniowie->execute();
                $uczniowie = $stmt_uczniowie->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            <div style="text-align: center; margin: 20px 0;">
                <a href="wstaw_oceny_admin.php?jednostka=<?php echo urlencode($jednostka_query); ?>&przedmiot_id=<?php echo $przedmiot_id; ?>" class="przycisk">Wystaw Oceny Seryjnie</a>
            </div>
            <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Lp.</th><th rowspan="2">Uczeń</th>
                        <th colspan="4">Okres 1</th><th colspan="4">Okres 2</th><th colspan="3">Roczna</th>
                    </tr>
                    <tr>
                        <th>Oceny bieżące</th><th>Śr.I</th><th>(I)</th><th>I</th>
                        <th>Oceny bieżące</th><th>Śr.II</th><th>(II)</th><th>II</th>
                        <th>Śr.R</th><th>(R)</th><th>R</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uczniowie as $index => $uczen): 
                        $oceny_sql = "SELECT o.id, o.ocena, o.semestr, o.data_wystawienia, o.komentarz, k.waga, k.typ_oceny, k.kolor, k.nazwa_kategorii, CONCAT(n.imie, ' ', n.nazwisko) as nauczyciel
                                      FROM oceny o 
                                      JOIN kategorie_ocen k ON o.kategoria_id = k.id 
                                      JOIN uzytkownicy n ON o.nauczyciel_id = n.id
                                      WHERE o.uczen_id = ? AND o.przedmiot_id = ? AND o.rok_szkolny_id = ?";
                        $stmt_oceny = $polaczenie->prepare($oceny_sql);
                        $stmt_oceny->bind_param("iii", $uczen['id'], $przedmiot_id, $aktywny_rok_id);
                        $stmt_oceny->execute();
                        $wszystkie_oceny = $stmt_oceny->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        // Obliczenia średnich
                        $oceny_sem1_biezace = array_filter($wszystkie_oceny, fn($o) => $o['semestr'] == 1 && $o['typ_oceny'] == 'biezaca' && $o['waga'] > 0);
                        $suma_wag_1 = array_sum(array_column($oceny_sem1_biezace, 'waga'));
                        $suma_ocen_1 = array_reduce($oceny_sem1_biezace, fn($c, $i) => $c + $i['ocena'] * $i['waga'], 0);
                        $srednia_1 = $suma_wag_1 > 0 ? number_format($suma_ocen_1 / $suma_wag_1, 2) : '-';

                        $oceny_sem2_biezace = array_filter($wszystkie_oceny, fn($o) => $o['semestr'] == 2 && $o['typ_oceny'] == 'biezaca' && $o['waga'] > 0);
                        $suma_wag_2 = array_sum(array_column($oceny_sem2_biezace, 'waga'));
                        $suma_ocen_2 = array_reduce($oceny_sem2_biezace, fn($c, $i) => $c + $i['ocena'] * $i['waga'], 0);
                        $srednia_2 = $suma_wag_2 > 0 ? number_format($suma_ocen_2 / $suma_wag_2, 2) : '-';

                        $srednia_r = ($suma_wag_1 + $suma_wag_2) > 0 ? number_format(($suma_ocen_1 + $suma_ocen_2) / ($suma_wag_1 + $suma_wag_2), 2) : '-';
                    ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?></td>
                        
                        <td style="white-space: nowrap;">
                            <?php foreach(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==1) as $o): 
                                $tooltip = "Kategoria: " . htmlspecialchars($o['nazwa_kategorii']) . "\nData: " . $o['data_wystawienia'] . "\nNauczyciel: " . htmlspecialchars($o['nauczyciel']) . "\nWaga: " . $o['waga'] . "\nKomentarz: " . htmlspecialchars($o['komentarz']);
                            ?>
                                <a href="edytuj_ocene.php?id=<?php echo $o['id']; ?>" title="<?php echo $tooltip; ?>">
                                    <span class='ocena' style='background:<?php echo htmlspecialchars($o['kolor']); ?>'><?php echo formatujOcene($o['ocena']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </td>
                        <td><b><?php echo $srednia_1; ?></b></td>
                        <td></td><td></td>
                        
                        <td style="white-space: nowrap;">
                             <?php foreach(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==2) as $o): 
                                $tooltip = "Kategoria: " . htmlspecialchars($o['nazwa_kategorii']) . "\nData: " . $o['data_wystawienia'] . "\nNauczyciel: " . htmlspecialchars($o['nauczyciel']) . "\nWaga: " . $o['waga'] . "\nKomentarz: " . htmlspecialchars($o['komentarz']);
                            ?>
                                <a href="edytuj_ocene.php?id=<?php echo $o['id']; ?>" title="<?php echo $tooltip; ?>">
                                    <span class='ocena' style='background:<?php echo htmlspecialchars($o['kolor']); ?>'><?php echo formatujOcene($o['ocena']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </td>
                        <td><b><?php echo $srednia_2; ?></b></td>
                        <td></td><td></td>

                        <td><b><?php echo $srednia_r; ?></b></td>
                        <td></td><td></td>
                    </tr>
                    <?php endforeach; ?>
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