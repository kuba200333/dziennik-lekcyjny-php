<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['nauczyciel', 'dyrektor', 'admin'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: moje_nauczanie.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$id_oceny = (int)$_GET['id'];
$id_nauczyciela_sesja = (int)$_SESSION['uzytkownik_id'];
$rola_sesja = $_SESSION['rola'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Funkcja do konwersji ocen, np. '3+' -> 3.5
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

// Funkcja do formatowania ocen (odwrotna do konwertujOcene)
function formatujOcene($ocena_num) {
    if ($ocena_num === null) return '';
    $mapowanie = [
        1.5 => '1+', 1.75 => '2-', 2.5 => '2+', 2.75 => '3-', 3.5 => '3+',
        3.75 => '4-', 4.5 => '4+', 4.75 => '5-', 5.5 => '5+', 5.75 => '6-'
    ];
    return $mapowanie[$ocena_num] ?? (string)(int)$ocena_num;
}


// Sprawdzenie, czy nauczyciel ma prawo edytować tę ocenę
$stmt_check = $polaczenie->prepare("SELECT id FROM oceny WHERE id = ? AND (nauczyciel_id = ? OR ? = 'admin')");
$stmt_check->bind_param("iis", $id_oceny, $id_nauczyciela_sesja, $rola_sesja);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows === 0) {
    die("Błąd: Nie masz uprawnień do edycji lub usunięcia tej oceny.");
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = $_POST['redirect_url'] ?? 'moje_nauczanie.php';

    if (isset($_POST['usun'])) {
        $stmt = $polaczenie->prepare("DELETE FROM oceny WHERE id = ?");
        $stmt->bind_param("i", $id_oceny);
        $stmt->execute();
    } elseif (isset($_POST['zapisz'])) {
        $nowa_ocena_num = konwertujOcene($_POST['ocena']);
        $nowa_kategoria_id = $_POST['kategoria_id'];
        $nowy_komentarz = $_POST['komentarz'];
        $nowa_data = $_POST['data_wystawienia'];

        // Automatyczne wykrywanie semestru na podstawie nowej daty
        $stmt_semestr = $polaczenie->prepare("SELECT numer_semestru FROM semestry WHERE rok_szkolny_id = ? AND ? BETWEEN data_od AND data_do");
        $stmt_semestr->bind_param("is", $aktywny_rok_id, $nowa_data);
        $stmt_semestr->execute();
        $wynik_semestr = $stmt_semestr->get_result();
        if ($wynik_semestr->num_rows === 0) {
            die("Błąd: Wybrana data nie pasuje do żadnego semestru. Sprawdź Ustawienia Globalne.");
        }
        $nowy_semestr = $wynik_semestr->fetch_assoc()['numer_semestru'];

        $stmt = $polaczenie->prepare("UPDATE oceny SET ocena = ?, kategoria_id = ?, komentarz = ?, data_wystawienia = ?, semestr = ? WHERE id = ?");
        $stmt->bind_param("dissii", $nowa_ocena_num, $nowa_kategoria_id, $nowy_komentarz, $nowa_data, $nowy_semestr, $id_oceny);
        $stmt->execute();
    }
    header('Location: ' . $redirect_url);
    exit();
}

// Pobieranie danych do wyświetlenia
$sql = "SELECT o.ocena, o.komentarz, o.data_wystawienia, o.kategoria_id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) as uczen
        FROM oceny o
        JOIN uzytkownicy u ON o.uczen_id = u.id
        JOIN przedmioty p ON o.przedmiot_id = p.id
        WHERE o.id = ?";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("i", $id_oceny);
$stmt->execute();
$ocena_dane = $stmt->get_result()->fetch_assoc();

// Pobierz kategorie ocen
$kategorie_sql = "SELECT id, nazwa_kategorii FROM kategorie_ocen WHERE (nauczyciel_id IS NULL OR nauczyciel_id = ?) AND typ_oceny = 'biezaca' ORDER BY nazwa_kategorii";
$stmt_kategorie = $polaczenie->prepare($kategorie_sql);
$stmt_kategorie->bind_param("i", $id_nauczyciela_sesja);
$stmt_kategorie->execute();
$kategorie = $stmt_kategorie->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Ocenę</title>
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
            <h1>Edycja Oceny</h1>
            <p><strong>Uczeń:</strong> <?php echo htmlspecialchars($ocena_dane['uczen']); ?></p>
            <p><strong>Przedmiot:</strong> <?php echo htmlspecialchars($ocena_dane['nazwa_przedmiotu']); ?></p>

            <form action="" method="post" style="padding: 0;">
                Ocena:<br>
                <input type="text" name="ocena" value="<?php echo formatujOcene($ocena_dane['ocena']); ?>" list="lista_ocen" required>
                
                Kategoria:<br>
                <select name="kategoria_id" required>
                    <?php foreach($kategorie as $k): ?>
                        <option value="<?php echo $k['id']; ?>" <?php if($k['id'] == $ocena_dane['kategoria_id']) echo 'selected';?>>
                            <?php echo htmlspecialchars($k['nazwa_kategorii']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                Data wystawienia:<br>
                <input type="date" name="data_wystawienia" value="<?php echo htmlspecialchars($ocena_dane['data_wystawienia']); ?>" required>

                Komentarz:<br>
                <textarea name="komentarz"><?php echo htmlspecialchars($ocena_dane['komentarz']); ?></textarea>

                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'moje_nauczanie.php'); ?>">
                <br><br>
                <div style="display:flex; justify-content: space-between;">
                    <button type="submit" name="zapisz" class="przycisk">Zapisz zmiany</button>
                    <a href="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'moje_nauczanie.php'); ?>" class="przycisk" style="background-color: #7f8c8d;">Anuluj</a>
                    <button type="submit" name="usun" class="przycisk przycisk-usun" onclick="return confirm('Czy na pewno chcesz usunąć tę ocenę?');">Usuń Ocenę</button>
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
</body>
</html>