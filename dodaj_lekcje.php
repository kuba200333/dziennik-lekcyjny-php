<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php'); exit();
}
require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Pobieranie parametrów z URL
$jednostka_query = $_GET['jednostka'];
list($typ, $id_jednostki) = explode('-', $jednostka_query);
$dzien_tygodnia = (int)$_GET['dzien'];
$nr_lekcji = (int)$_GET['nr'];

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczanie_id = $_POST['nauczanie_id'];
    $sala = $_POST['sala'];
    $data_od = $_POST['data_od'];
    $data_do = $_POST['data_do'];
    
    $stmt = $polaczenie->prepare("INSERT INTO plan_lekcji (rok_szkolny_id, dzien_tygodnia, nr_lekcji, nauczanie_id, data_od, data_do, sala) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissss", $aktywny_rok_id, $dzien_tygodnia, $nr_lekcji, $nauczanie_id, $data_od, $data_do, $sala);
    $stmt->execute();
    
    header('Location: zarzadzaj_planem.php?jednostka=' . $jednostka_query);
    exit();
}

// Pobranie listy przydziałów (nauczania) wraz z domyślnymi salami
$warunek_sql = ($typ === 'klasa') ? "n.klasa_id = ?" : "n.grupa_id = ?";
$sql = "SELECT 
            n.id, 
            p.nazwa_przedmiotu, 
            n.nauczyciel_id,
            CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel,
            (SELECT sala FROM plan_lekcji pl WHERE pl.nauczanie_id = n.id GROUP BY sala ORDER BY COUNT(*) DESC LIMIT 1) AS domyslna_sala_przedmiot,
            (SELECT sala FROM plan_lekcji pl JOIN nauczanie n2 ON pl.nauczanie_id = n2.id WHERE n2.nauczyciel_id = n.nauczyciel_id GROUP BY sala ORDER BY COUNT(*) DESC LIMIT 1) AS domyslna_sala_ogolna
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        WHERE $warunek_sql AND n.rok_szkolny_id = ?";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $id_jednostki, $aktywny_rok_id);
$stmt->execute();
$przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- NOWY, INTELIGENTNY KOD POBIERANIA DAT ---
$dzisiaj = date('Y-m-d');
$data_start = $dzisiaj;
$data_koniec = $dzisiaj;

// Szukamy semestru, w którym mieści się dzisiejsza data
$stmt_semestr = $polaczenie->prepare("SELECT data_od, data_do FROM semestry WHERE rok_szkolny_id = ? AND ? BETWEEN data_od AND data_do");
$stmt_semestr->bind_param("is", $aktywny_rok_id, $dzisiaj);
$stmt_semestr->execute();
$wynik = $stmt_semestr->get_result();

if ($wynik->num_rows > 0) {
    // Znaleziono bieżący semestr, używamy jego dat
    $semestr = $wynik->fetch_assoc();
    $data_start = $semestr['data_od'];
    $data_koniec = $semestr['data_do'];
} else {
    // Jeśli jest przerwa (np. wakacje), pobierz daty całego roku szkolnego jako domyślne
    $semestry_wynik = $polaczenie->query("SELECT MIN(data_od) as start_roku, MAX(data_do) as koniec_roku FROM semestry WHERE rok_szkolny_id = $aktywny_rok_id");
    if ($semestry_wynik->num_rows > 0) {
        $rok = $semestry_wynik->fetch_assoc();
        $data_start = $rok['start_roku'] ?? $dzisiaj;
        $data_koniec = $rok['koniec_roku'] ?? $dzisiaj;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Lekcję do Planu</title>
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
            <h1>Dodaj lekcję do planu</h1>
            <p>Dzień: <strong><?php echo ['Poniedziałek','Wtorek','Środa','Czwartek','Piątek'][$dzien_tygodnia-1]; ?></strong>, Lekcja: <strong><?php echo $nr_lekcji; ?></strong></p>
            <form action="" method="post" style="padding: 0;">
                <label>Wybierz przydział (Przedmiot - Nauczyciel):</label>
                <select name="nauczanie_id" id="nauczanie_id" required>
                    <option value="" data-sala-przedmiot="" data-sala-ogolna="">-- Wybierz --</option>
                    <?php foreach($przydzialy as $p): ?>
                    <option 
                        value="<?php echo $p['id']; ?>"
                        data-sala-przedmiot="<?php echo htmlspecialchars($p['domyslna_sala_przedmiot']); ?>"
                        data-sala-ogolna="<?php echo htmlspecialchars($p['domyslna_sala_ogolna']); ?>"
                    >
                        <?php echo htmlspecialchars($p['nazwa_przedmiotu'] . ' - ' . $p['nauczyciel']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label>Sala lekcyjna:</label>
                <input type="text" name="sala" id="sala" required>

                <label>Lekcja obowiązuje od:</label>
                <input type="date" name="data_od" value="<?php echo htmlspecialchars($data_start); ?>" required>

                <label>Lekcja obowiązuje do:</label>
                <input type="date" name="data_do" value="<?php echo htmlspecialchars($data_koniec); ?>" required>
                
                <br><br>
                <input type="submit" value="Zapisz lekcję" class="przycisk">
            </form>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
<script>
document.getElementById('nauczanie_id').addEventListener('change', function() {
    const salaInput = document.getElementById('sala');
    const selectedOption = this.options[this.selectedIndex];
    const salaPrzedmiot = selectedOption.getAttribute('data-sala-przedmiot');
    const salaOgolna = selectedOption.getAttribute('data-sala-ogolna');
    
    if (salaPrzedmiot) {
        salaInput.value = salaPrzedmiot;
    } else if (salaOgolna) {
        salaInput.value = salaOgolna;
    } else {
        salaInput.value = '';
    }
});
</script>
</body>
</html>