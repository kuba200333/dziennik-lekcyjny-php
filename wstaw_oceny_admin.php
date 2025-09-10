<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}
// Sprawdzamy, czy przekazano kluczowe parametry w URL
if (!isset($_GET['jednostka']) || !isset($_GET['przedmiot_id'])) {
    header('Location: podglad_ocen.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
$admin_id = $_SESSION['uzytkownik_id'];

// Rozbijamy parametr 'jednostka' na typ i ID
list($jednostka_typ, $jednostka_id) = explode('-', $_GET['jednostka']);
$jednostka_id = (int)$jednostka_id;
$przedmiot_id = (int)$_GET['przedmiot_id'];

// Funkcja do konwersji ocen
function konwertujOcene($ocena_tekst) {
    $mapowanie = ['1+'=>1.5, '2-'=>1.75, '2+'=>2.5, '3-'=>2.75, '3+'=>3.5, '4-'=>3.75, '4+'=>4.5, '5-'=>4.75, '5+'=>5.5, '6-'=>5.75];
    return $mapowanie[trim($ocena_tekst)] ?? (float)trim($ocena_tekst);
}

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oceny_dane = $_POST['oceny'];
    $kategoria_id = $_POST['kategoria_id'];
    $data_wystawienia = $_POST['data_wystawienia'];
    $nauczyciel_wystawiajacy_id = $_POST['nauczyciel_id']; // Nauczyciel wybrany z listy

    // Automatyczne wykrywanie semestru
    $stmt_semestr = $polaczenie->prepare("SELECT numer_semestru FROM semestry WHERE rok_szkolny_id = ? AND ? BETWEEN data_od AND data_do");
    $stmt_semestr->bind_param("is", $aktywny_rok_id, $data_wystawienia);
    $stmt_semestr->execute();
    $wynik_semestr = $stmt_semestr->get_result();
    if ($wynik_semestr->num_rows === 0) { die("Błąd: Data nie pasuje do żadnego semestru."); }
    $semestr = $wynik_semestr->fetch_assoc()['numer_semestru'];

    $stmt_insert = $polaczenie->prepare("INSERT INTO oceny (rok_szkolny_id, uczen_id, przedmiot_id, nauczyciel_id, kategoria_id, ocena, data_wystawienia, semestr, komentarz) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($oceny_dane as $uczen_id => $dane) {
        if (!empty($dane['ocena'])) {
            $ocena_numeryczna = konwertujOcene($dane['ocena']);
            $komentarz = $dane['komentarz'];
            $stmt_insert->bind_param("iiiiidiss", $aktywny_rok_id, $uczen_id, $przedmiot_id, $nauczyciel_wystawiajacy_id, $kategoria_id, $ocena_numeryczna, $data_wystawienia, $semestr, $komentarz);
            $stmt_insert->execute();
        }
    }
    header('Location: podglad_ocen.php?jednostka=' . urlencode($_GET['jednostka']) . '&przedmiot_id=' . $przedmiot_id);
    exit();
}

// Pobieranie danych do wyświetlenia formularza
$jednostka_nazwa = ($jednostka_typ === 'klasa') 
    ? $polaczenie->query("SELECT nazwa_klasy FROM klasy WHERE id = $jednostka_id")->fetch_assoc()['nazwa_klasy'] 
    : $polaczenie->query("SELECT nazwa_grupy FROM grupy WHERE id = $jednostka_id")->fetch_assoc()['nazwa_grupy'];
$przedmiot_nazwa = $polaczenie->query("SELECT nazwa_przedmiotu FROM przedmioty WHERE id = $przedmiot_id")->fetch_assoc()['nazwa_przedmiotu'];

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

// Pobierz kategorie ocen (wszystkie systemowe i własne admina)
$kategorie = $polaczenie->query("SELECT id, nazwa_kategorii FROM kategorie_ocen WHERE nauczyciel_id IS NULL OR nauczyciel_id = $admin_id ORDER BY nazwa_kategorii")->fetch_all(MYSQLI_ASSOC);
// Pobierz wszystkich nauczycieli
$nauczyciele = $polaczenie->query("SELECT id, imie, nazwisko FROM uzytkownicy WHERE rola IN ('nauczyciel', 'dyrektor', 'admin') AND aktywny = 1 ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wstaw Oceny Seryjnie - Panel Admina</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
         </header>
    <main id="glowny">
        <div class="karta">
            <h1>Wstawianie ocen (Panel Administratora)</h1>
            <h2 style="text-align: center; border: none;"><?php echo htmlspecialchars($przedmiot_nazwa); ?>, <?php echo htmlspecialchars($jednostka_nazwa); ?></h2>
            
            <form action="" method="post">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; align-items: end; margin-bottom: 20px; background-color: var(--kolor-tlo); padding: 15px; border-radius: 8px;">
                    <div>
                        <label for="nauczyciel_id">Wystaw jako nauczyciel:</label><br>
                        <select name="nauczyciel_id" id="nauczyciel_id" required>
                            <option value="">-- Wybierz --</option>
                            <?php foreach ($nauczyciele as $n): ?>
                            <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['nazwisko'] . ' ' . $n['imie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                            <th>Lp.</th><th style="text-align: left;">Uczeń</th>
                            <th style="width: 15%;">Ocena</th><th style="width: 40%;">Komentarz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uczniowie as $index => $uczen): ?>
                        <tr>
                            <td><?php echo $index + 1; ?>.</td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?></td>
                            <td><input type="text" name="oceny[<?php echo $uczen['id']; ?>][ocena]" list="lista_ocen"></td>
                            <td><input type="text" name="oceny[<?php echo $uczen['id']; ?>][komentarz]" class="komentarz-ucznia"></td>
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
        wszystkieKomentarze.forEach(input => { input.value = globalnyKomentarz; });
    }
</script>
</body>
</html>