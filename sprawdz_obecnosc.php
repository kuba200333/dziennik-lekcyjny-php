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

// Pobieranie danych o lekcji z URL
$nauczanie_id = $_GET['nauczanie_id'] ?? null;
$data = $_GET['data'] ?? null;
$nr_lekcji = $_GET['nr'] ?? null;
$realizacja_id = $_GET['realizacja_id'] ?? null;

if (!$nauczanie_id && !$realizacja_id) {
    die("Błąd: Brak identyfikatora lekcji.");
}

// Obsługa zapisu formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $temat = $_POST['temat'];
    $frekwencja_dane = $_POST['frekwencja'];
    $posted_nauczanie_id = $_POST['nauczanie_id'];

    $polaczenie->begin_transaction();
    try {
        // 1. Zapisz temat w tabeli 'realizacja_tematu'
        $stmt_temat = $polaczenie->prepare("INSERT INTO realizacja_tematu (rok_szkolny_id, data, nr_lekcji, nauczanie_id, temat) VALUES (?, ?, ?, ?, ?)");
        $stmt_temat->bind_param("isiis", $aktywny_rok_id, $data, $nr_lekcji, $posted_nauczanie_id, $temat);
        $stmt_temat->execute();
        $nowa_realizacja_id = $stmt_temat->insert_id;

        // 2. Zapisz frekwencję dla każdego ucznia
        $stmt_frekwencja = $polaczenie->prepare("INSERT INTO frekwencja (rok_szkolny_id, uczen_id, data_zajec, nr_lekcji, przedmiot_id, nauczyciel_id, status, realizacja_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $przedmiot_id_z_nauczania = $polaczenie->query("SELECT przedmiot_id FROM nauczanie WHERE id = $posted_nauczanie_id")->fetch_assoc()['przedmiot_id'];

        foreach ($frekwencja_dane as $uczen_id => $status) {
            $stmt_frekwencja->bind_param("iisiisis", $aktywny_rok_id, $uczen_id, $data, $nr_lekcji, $przedmiot_id_z_nauczania, $id_nauczyciela, $status, $nowa_realizacja_id);
            $stmt_frekwencja->execute();
        }
        
        $polaczenie->commit();
        header('Location: realizacja_programu.php?data=' . $data);
        exit();
    } catch (mysqli_sql_exception $exception) {
        $polaczenie->rollback();
        die("Wystąpił błąd transakcji: " . $exception->getMessage());
    }
}


// --- Pobieranie danych do wyświetlenia formularza ---
$sql_dane = "SELECT p.nazwa_przedmiotu, k.id as klasa_id, k.nazwa_klasy, g.id as grupa_id, g.nazwa_grupy FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id = p.id LEFT JOIN klasy k ON n.klasa_id = k.id LEFT JOIN grupy g ON n.grupa_id = g.id WHERE n.id = ?";
$stmt = $polaczenie->prepare($sql_dane);
$stmt->bind_param("i", $nauczanie_id);
$stmt->execute();
$lekcja = $stmt->get_result()->fetch_assoc();

// Pobieranie uczniów na podstawie tego, czy lekcja dotyczy klasy czy grupy
$jednostka_typ = $lekcja['klasa_id'] ? 'klasa' : 'grupa';
$jednostka_id = $lekcja['klasa_id'] ?? $lekcja['grupa_id'];

if ($jednostka_typ === 'klasa') {
    $uczniowie_sql = "SELECT u.id, ui.nr_dziennika, u.imie, u.nazwisko FROM uzytkownicy u JOIN uczniowie_info ui ON u.id=ui.uzytkownik_id WHERE ui.klasa_id = ? ORDER BY ui.nr_dziennika, u.nazwisko, u.imie";
} else {
    $uczniowie_sql = "SELECT u.id, ui.nr_dziennika, u.imie, u.nazwisko FROM uzytkownicy u JOIN przypisania_grup pg ON u.id=pg.uczen_id JOIN uczniowie_info ui ON u.id=ui.uzytkownik_id WHERE pg.grupa_id = ? ORDER BY u.nazwisko, u.imie";
}
$stmt_uczniowie = $polaczenie->prepare($uczniowie_sql);
$stmt_uczniowie->bind_param("i", $jednostka_id);
$stmt_uczniowie->execute();
$uczniowie = $stmt_uczniowie->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Obecność i Temat Lekcji</title><link rel="stylesheet" href="style.css">
    <style> .frekwencja-opcje span { margin-left: 15px; cursor: pointer; font-weight: bold; } </style>
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Lekcja: <?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></h1>
            <p><strong>Klasa/Grupa:</strong> <?php echo htmlspecialchars($lekcja['nazwa_klasy'] ?? $lekcja['nazwa_grupy']); ?></p>
            <p><strong>Data:</strong> <?php echo $data; ?>, <strong>Lekcja nr:</strong> <?php echo $nr_lekcji; ?></p>
            <hr>
            <form action="" method="post">
                <input type="hidden" name="nauczanie_id" value="<?php echo $nauczanie_id; ?>">
                <label for="temat"><strong>Temat lekcji:</strong></label>
                <input type="text" name="temat" id="temat" required>
                
                <h3>Frekwencja</h3>
                <div class="frekwencja-opcje">
                    Zaznacz wszystkich jako:
                    <span onclick="ustawWszystkich('obecny')">Obecny</span>
                    <span onclick="ustawWszystkich('spozniony')">Spóźniony</span>
                    <span onclick="ustawWszystkich('nieobecny')">Nieobecny</span>
                </div>
                <table>
                    <thead><tr><th>Nr</th><th style="text-align: left;">Uczeń</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($uczniowie as $uczen): ?>
                        <tr>
                            <td><?php echo $uczen['nr_dziennika']; ?>.</td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?></td>
                            <td>
                                <select name="frekwencja[<?php echo $uczen['id']; ?>]">
                                    <option value="obecny" selected>Obecny</option>
                                    <option value="nieobecny">Nieobecny</option>
                                    <option value="spozniony">Spóźniony</option>
                                    <option value="usprawiedliwiony">Usprawiedliwiony</option>
                                    <option value="zwolniony">Zwolniony</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <input type="submit" value="Zapisz lekcję i frekwencję" class="przycisk">
            </form>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
<script>
    function ustawWszystkich(status) {
        const wszystkieListy = document.querySelectorAll('select[name^="frekwencja"]');
        wszystkieListy.forEach(select => {
            select.value = status;
        });
    }
</script>
</body>
</html>