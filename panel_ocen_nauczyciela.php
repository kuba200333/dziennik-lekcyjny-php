<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['nauczyciel', 'dyrektor', 'admin'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['przydzial_id']) || !is_numeric($_GET['przydzial_id'])) {
    header('Location: moje_nauczanie.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
$id_nauczyciela_sesja = $_SESSION['uzytkownik_id'];
$id_przydzialu = (int)$_GET['przydzial_id'];

// Pobierz szczegóły przydziału (i upewnij się, że należy do zalogowanego nauczyciela lub admina)
$sql_przydzial = "SELECT n.przedmiot_id, p.nazwa_przedmiotu, n.klasa_id, k.nazwa_klasy, n.grupa_id, g.nazwa_grupy
                  FROM nauczanie n
                  JOIN przedmioty p ON n.przedmiot_id = p.id
                  LEFT JOIN klasy k ON n.klasa_id = k.id
                  LEFT JOIN grupy g ON n.grupa_id = g.id
                  WHERE n.id = ? AND (n.nauczyciel_id = ? OR ? = 'admin')";
$stmt = $polaczenie->prepare($sql_przydzial);
$stmt->bind_param("iis", $id_przydzialu, $id_nauczyciela_sesja, $_SESSION['rola']);
$stmt->execute();
$przydzial = $stmt->get_result()->fetch_assoc();

if (!$przydzial) { die("Błąd: Nie masz dostępu do tego przydziału."); }

$przedmiot_id = $przydzial['przedmiot_id'];
$jednostka_typ = $przydzial['klasa_id'] ? 'klasa' : 'grupa';
$jednostka_id = $przydzial['klasa_id'] ?? $przydzial['grupa_id'];
$jednostka_nazwa = $przydzial['nazwa_klasy'] ?? $przydzial['nazwa_grupy'];
$przedmiot_nazwa = $przydzial['nazwa_przedmiotu'];

// Pobierz uczniów
if ($jednostka_typ === 'klasa') {
    $uczniowie_sql = "SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN uczniowie_info ui ON u.id=ui.uzytkownik_id WHERE ui.klasa_id = ? ORDER BY u.nazwisko, u.imie";
} else {
    $uczniowie_sql = "SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN przypisania_grup pg ON u.id=pg.uczen_id WHERE pg.grupa_id = ? ORDER BY u.nazwisko, u.imie";
}
$stmt_uczniowie = $polaczenie->prepare($uczniowie_sql);
$stmt_uczniowie->bind_param("i", $jednostka_id);
$stmt_uczniowie->execute();
$uczniowie = $stmt_uczniowie->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <title>Panel Ocen</title>
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
            <h1>Arkusz Ocen</h1>
            <h2 style="text-align: center; border: none;"><?php echo htmlspecialchars($przedmiot_nazwa); ?>, <?php echo htmlspecialchars($jednostka_nazwa); ?></h2>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="wstaw_oceny.php?przydzial_id=<?php echo $id_przydzialu; ?>" class="przycisk">Wystaw Oceny Seryjnie</a>
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
                            <?php foreach(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==1 && $o['typ_oceny']=='biezaca') as $o): 
                                $tooltip = "Kategoria: " . htmlspecialchars($o['nazwa_kategorii']) . "\nData: " . $o['data_wystawienia'] . "\nNauczyciel: " . htmlspecialchars($o['nauczyciel']) . "\nWaga: " . $o['waga'] . "\nKomentarz: " . htmlspecialchars($o['komentarz']);
                            ?>
                                <a href="edytuj_ocene.php?id=<?php echo $o['id']; ?>" title="<?php echo $tooltip; ?>">
                                    <span class='ocena' style='background:<?php echo htmlspecialchars($o['kolor']); ?>'><?php echo formatujOcene($o['ocena']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </td>
                        <td><b><?php echo $srednia_1; ?></b></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==1 && $o['typ_oceny']=='proponowana')); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==1 && $o['typ_oceny']=='klasyfikacyjna')); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>
                        
                        <td style="white-space: nowrap;">
                             <?php foreach(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==2 && $o['typ_oceny']=='biezaca') as $o): 
                                $tooltip = "Kategoria: " . htmlspecialchars($o['nazwa_kategorii']) . "\nData: " . $o['data_wystawienia'] . "\nNauczyciel: " . htmlspecialchars($o['nauczyciel']) . "\nWaga: " . $o['waga'] . "\nKomentarz: " . htmlspecialchars($o['komentarz']);
                            ?>
                                <a href="edytuj_ocene.php?id=<?php echo $o['id']; ?>" title="<?php echo $tooltip; ?>">
                                    <span class='ocena' style='background:<?php echo htmlspecialchars($o['kolor']); ?>'><?php echo formatujOcene($o['ocena']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </td>
                        <td><b><?php echo $srednia_2; ?></b></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==2 && $o['typ_oceny']=='proponowana')); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['semestr']==2 && $o['typ_oceny']=='klasyfikacyjna')); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>

                        <td><b><?php echo $srednia_r; ?></b></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['typ_oceny']=='proponowana' && str_contains(strtolower($o['nazwa_kategorii']), 'roczna'))); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>
                        <td><?php $o = current(array_filter($wszystkie_oceny, fn($o)=>$o['typ_oceny']=='klasyfikacyjna' && str_contains(strtolower($o['nazwa_kategorii']), 'roczna'))); echo $o ? formatujOcene($o['ocena']) : ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>