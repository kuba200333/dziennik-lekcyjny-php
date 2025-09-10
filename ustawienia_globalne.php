<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
if ($polaczenie->connect_errno) {
    die("Błąd połączenia z bazą danych: " . $polaczenie->connect_error);
}
$aktywny_rok_id = $_SESSION['aktywny_rok_id'] ?? null;

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ustawienie aktywnego roku szkolnego
    if (isset($_POST['ustaw_aktywny'])) {
        $nowy_aktywny_id = $_POST['rok_szkolny_id'];
        // 1. Deaktywuj wszystkie inne lata
        $polaczenie->query("UPDATE lata_szkolne SET aktywny = 0");
        // 2. Aktywuj wybrany rok
        $stmt = $polaczenie->prepare("UPDATE lata_szkolne SET aktywny = 1 WHERE id = ?");
        $stmt->bind_param("i", $nowy_aktywny_id);
        $stmt->execute();
        $komunikat = "Pomyślnie zmieniono aktywny rok szkolny. Proszę się przelogować, aby zmiany zostały w pełni zastosowane.";
    }

    // Dodanie nowego roku szkolnego
    if (isset($_POST['dodaj_rok'])) {
        $nowy_rok_nazwa = $_POST['nazwa_roku'];
        if (preg_match('/^\d{4}\/\d{4}$/', $nowy_rok_nazwa)) {
            $stmt = $polaczenie->prepare("INSERT INTO lata_szkolne (nazwa) VALUES (?)");
            $stmt->bind_param("s", $nowy_rok_nazwa);
            if ($stmt->execute()) {
                $komunikat = "Dodano nowy rok szkolny: " . htmlspecialchars($nowy_rok_nazwa);
            } else {
                $komunikat_bledu = "Taki rok szkolny już istnieje.";
            }
        } else {
            $komunikat_bledu = "Nieprawidłowy format roku. Użyj formatu RRRR/RRRR (np. 2025/2026).";
        }
    }
    
    // Zapisywanie dat semestrów
    if (isset($_POST['zapisz_semestry'])) {
        $semestr1_od = $_POST['semestr1_od'];
        $semestr1_do = $_POST['semestr1_do'];
        $semestr2_od = $_POST['semestr2_od'];
        $semestr2_do = $_POST['semestr2_do'];
        
        $sql = "INSERT INTO semestry (rok_szkolny_id, numer_semestru, data_od, data_do) VALUES (?, 1, ?, ?), (?, 2, ?, ?)
                ON DUPLICATE KEY UPDATE data_od = VALUES(data_od), data_do = VALUES(data_do)";
        $stmt = $polaczenie->prepare($sql);
        $stmt->bind_param("isssis", $aktywny_rok_id, $semestr1_od, $semestr1_do, $aktywny_rok_id, $semestr2_od, $semestr2_do);
        $stmt->execute();
        $komunikat = "Zapisano daty trwania semestrów.";
    }
}

// Pobranie danych do wyświetlenia
$lata_szkolne = $polaczenie->query("SELECT id, nazwa, aktywny FROM lata_szkolne ORDER BY nazwa DESC")->fetch_all(MYSQLI_ASSOC);
$semestry = [];
if ($aktywny_rok_id) {
    $semestry = $polaczenie->query("SELECT numer_semestru, data_od, data_do FROM semestry WHERE rok_szkolny_id = $aktywny_rok_id")->fetch_all(MYSQLI_ASSOC);
}
$semestr1_dane = current(array_filter($semestry, fn($s) => $s['numer_semestru'] == 1)) ?: ['data_od' => '', 'data_do' => ''];
$semestr2_dane = current(array_filter($semestry, fn($s) => $s['numer_semestru'] == 2)) ?: ['data_od' => '', 'data_do' => ''];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Ustawienia Globalne</title>
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
                <h1>Ustawienia Globalne</h1>
                
                <?php if(isset($komunikat)) echo "<p style='color: green; font-weight: bold;'>$komunikat</p>"; ?>
                <?php if(isset($komunikat_bledu)) echo "<p class='error-text'>$komunikat_bledu</p>"; ?>

                <h2>Aktywny Rok Szkolny</h2>
                <p>Wybierz, w którym roku szkolnym dziennik ma aktualnie pracować.</p>
                <form action="" method="post">
                    <select name="rok_szkolny_id">
                        <?php foreach ($lata_szkolne as $rok): ?>
                            <option value="<?php echo $rok['id']; ?>" <?php if($rok['aktywny']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($rok['nazwa']); ?>
                                <?php if($rok['aktywny']) echo ' (Aktualny)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="ustaw_aktywny" class="przycisk">Ustaw jako aktywny</button>
                </form>
                <hr style="margin: 30px 0;">

                <h2>Dodaj Nowy Rok Szkolny</h2>
                <form action="" method="post">
                    <input type="text" name="nazwa_roku" placeholder="np. 2026/2027" required>
                    <button type="submit" name="dodaj_rok" class="przycisk">Dodaj Rok</button>
                </form>
                <hr style="margin: 30px 0;">

                <h2>Daty trwania semestrów</h2>
                <p>Ustaw daty rozpoczęcia i zakończenia semestrów dla aktywnego roku szkolnego.</p>
                <form action="" method="post">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4>Semestr 1</h4>
                            <label>Data rozpoczęcia:</label>
                            <input type="date" name="semestr1_od" value="<?php echo htmlspecialchars($semestr1_dane['data_od']); ?>" required>
                            <label>Data zakończenia:</label>
                            <input type="date" name="semestr1_do" value="<?php echo htmlspecialchars($semestr1_dane['data_do']); ?>" required>
                        </div>
                        <div>
                            <h4>Semestr 2</h4>
                            <label>Data rozpoczęcia:</label>
                            <input type="date" name="semestr2_od" value="<?php echo htmlspecialchars($semestr2_dane['data_od']); ?>" required>
                            <label>Data zakończenia:</label>
                            <input type="date" name="semestr2_do" value="<?php echo htmlspecialchars($semestr2_dane['data_do']); ?>" required>
                        </div>
                    </div>
                    <br>
                    <button type="submit" name="zapisz_semestry" class="przycisk">Zapisz daty semestrów</button>
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>