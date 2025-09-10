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
$id_nauczyciela = $_SESSION['uzytkownik_id'];
$id_przydzialu = (int)$_GET['przydzial_id'];

// Funkcja do konwersji ocen typu '3+' na wartość liczbową
function konwertujOcene($ocena_tekst) {
    $mapowanie = [
        '1+' => 1.5, '2-' => 1.75, '2+' => 2.5, '3-' => 2.75, '3+' => 3.5,
        '4-' => 3.75, '4+' => 4.5, '5-' => 4.75, '5+' => 5.5, '6-' => 5.75
    ];
    if (array_key_exists(trim($ocena_tekst), $mapowanie)) {
        return $mapowanie[trim($ocena_tekst)];
    }
    return (float)trim($ocena_tekst);
}

// Obsługa wysłanego formularza z ocenami
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oceny_dane = $_POST['oceny'];
    $kategoria_id = $_POST['kategoria_id'];
    $data_wystawienia = $_POST['data_wystawienia']; // Poprawna nazwa zmiennej
    $przedmiot_id_z_formularza = $_POST['przedmiot_id'];

    // AUTOMATYCZNE WYKRYWANIE SEMESTRU
    $stmt_semestr = $polaczenie->prepare("SELECT numer_semestru FROM semestry WHERE rok_szkolny_id = ? AND ? BETWEEN data_od AND data_do");
    $stmt_semestr->bind_param("is", $aktywny_rok_id, $data_wystawienia);
    $stmt_semestr->execute();
    $wynik_semestr = $stmt_semestr->get_result();
    if ($wynik_semestr->num_rows === 0) {
        die("Błąd: Data " . htmlspecialchars($data_wystawienia) . " nie pasuje do żadnego zdefiniowanego semestru w tym roku szkolnym. Sprawdź Ustawienia Globalne.");
    }
    $semestr = $wynik_semestr->fetch_assoc()['numer_semestru'];

    $stmt = $polaczenie->prepare("INSERT INTO oceny (rok_szkolny_id, uczen_id, przedmiot_id, nauczyciel_id, kategoria_id, ocena, data_wystawienia, semestr, komentarz) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($oceny_dane as $uczen_id => $dane) {
        if (!empty($dane['ocena'])) {
            $ocena_numeryczna = konwertujOcene($dane['ocena']);
            $komentarz = $dane['komentarz'];

            // POPRAWIONA LINIA
            $stmt->bind_param("iiiiidsss", $aktywny_rok_id, $uczen_id, $przedmiot_id_z_formularza, $id_nauczyciela, $kategoria_id, $ocena_numeryczna, $data_wystawienia, $semestr, $komentarz);
            $stmt->execute();
        }
    }
    header('Location: panel_ocen_nauczyciela.php?przydzial_id=' . $id_przydzialu);
    exit();
}

// Pobierz szczegóły przydziału
$sql_przydzial = "SELECT n.przedmiot_id, p.nazwa_przedmiotu, n.klasa_id, k.nazwa_klasy, n.grupa_id, g.nazwa_grupy FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id = p.id LEFT JOIN klasy k ON n.klasa_id = k.id LEFT JOIN grupy g ON n.grupa_id = g.id WHERE n.id = ? AND (n.nauczyciel_id = ? OR ? = 'admin')";
$stmt = $polaczenie->prepare($sql_przydzial);
$stmt->bind_param("iis", $id_przydzialu, $id_nauczyciela, $_SESSION['rola']);
$stmt->execute();
$przydzial = $stmt->get_result()->fetch_assoc();

if (!$przydzial) { die("Błąd: Nie masz dostępu do tego przydziału."); }

$przedmiot_id = $przydzial['przedmiot_id'];
$jednostka_typ = $przydzial['klasa_id'] ? 'klasa' : 'grupa';
$jednostka_id = $przydzial['klasa_id'] ?? $przydzial['grupa_id'];
$jednostka_nazwa = $przydzial['nazwa_klasy'] ?? $przydzial['nazwa_grupy'];
$przedmiot_nazwa = $przydzial['nazwa_przedmiotu'];

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

// Pobierz kategorie ocen
$kategorie_sql = "SELECT id, nazwa_kategorii FROM kategorie_ocen WHERE (nauczyciel_id IS NULL OR nauczyciel_id = ?) AND typ_oceny = 'biezaca' ORDER BY nazwa_kategorii";
$stmt_kategorie = $polaczenie->prepare($kategorie_sql);
$stmt_kategorie->bind_param("i", $id_nauczyciela);
$stmt_kategorie->execute();
$kategorie = $stmt_kategorie->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wstaw Oceny Seryjnie</title>
    <link rel="stylesheet" href="style.css">
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
            <h1>Wstawianie ocen seryjnie</h1>
            <h2 style="text-align: center; border: none;"><?php echo htmlspecialchars($przedmiot_nazwa); ?>, <?php echo htmlspecialchars($jednostka_nazwa); ?></h2>
            
            <form action="" method="post">
                <input type="hidden" name="przedmiot_id" value="<?php echo $przedmiot_id; ?>">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: end; margin-bottom: 20px; background-color: var(--kolor-tlo); padding: 15px; border-radius: 8px;">
                    <div>
                        <label for="kategoria_id">Kategoria oceny:</label><br>
                        <select name="kategoria_id" id="kategoria_id" required>
                            <option value="">-- Wybierz --</option>
                            <?php foreach ($kategorie as $k): ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['nazwa_kategorii']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="data_wystawienia">Data wystawienia:</label><br>
                        <input type="date" name="data_wystawienia" id="data_wystawienia" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label for="globalny_komentarz">Komentarz dla wszystkich:</label><br>
                        <input type="text" id="globalny_komentarz" onkeyup="kopiujKomentarz()">
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Lp.</th>
                            <th style="text-align: left;">Uczeń</th>
                            <th style="width: 15%;">Ocena</th>
                            <th style="width: 40%;">Komentarz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uczniowie as $index => $uczen): ?>
                        <tr>
                            <td><?php echo $index + 1; ?>.</td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?></td>
                            <td>
                                <input type="text" name="oceny[<?php echo $uczen['id']; ?>][ocena]" list="lista_ocen">
                            </td>
                            <td>
                                <input type="text" name="oceny[<?php echo $uczen['id']; ?>][komentarz]" class="komentarz-ucznia">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 20px;">
                    <input type="submit" value="Zapisz Oceny" class="przycisk">
                </div>
            </form>

            <datalist id="lista_ocen">
                <option value="1"><option value="1+"><option value="2-"><option value="2"><option value="2+">
                <option value="3-"><option value="3"><option value="3+"><option value="4-"><option value="4">
                <option value="4+"><option value="5-"><option value="5"><option value="5+"><option value="6-"><option value="6">
            </datalist>

        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
<script>
    function kopiujKomentarz() {
        const globalnyKomentarz = document.getElementById('globalny_komentarz').value;
        const wszystkieKomentarze = document.querySelectorAll('.komentarz-ucznia');
        wszystkieKomentarze.forEach(input => {
            input.value = globalnyKomentarz;
        });
    }
</script>
</body>
</html>