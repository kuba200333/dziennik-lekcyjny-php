<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $klasa_id = (int)$_POST['klasa_id'];
    $lista_uczniow_raw = trim($_POST['lista_uczniow']);
    
    // Dzielimy wklejony tekst na linie i usuwamy puste wpisy
    $linie = explode("\n", $lista_uczniow_raw);
    $linie = array_filter(array_map('trim', $linie));

    $domyslne_haslo = 'haslo123'; // Możesz ustawić dowolne hasło startowe
    $haslo_hash = password_hash($domyslne_haslo, PASSWORD_DEFAULT);

    // Pobieramy najwyższy numer w dzienniku w tej klasie, aby kontynuować numerację
    $wynik_nr = $polaczenie->query("SELECT MAX(nr_dziennika) as max_nr FROM uczniowie_info WHERE klasa_id = $klasa_id");
    $startowy_nr_dziennika = ($wynik_nr->fetch_assoc()['max_nr'] ?? 0) + 1;

    $polaczenie->begin_transaction();
    try {
        foreach ($linie as $index => $linia) {
            $czesci = explode(' ', $linia, 2);
            $imie = $czesci[0] ?? '';
            $nazwisko = $czesci[1] ?? '';
            
            if (empty($imie) || empty($nazwisko)) continue; // Pomiń nieprawidłowe linie

            // --- Generowanie unikalnego loginu dla ucznia (zaczyna się od cyfry parzystej) ---
            $login_istnieje = true;
            do {
                $pierwsza_cyfra = [2, 4, 6, 8][array_rand([2, 4, 6, 8])];
                $reszta_cyfr = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $login = $pierwsza_cyfra . $reszta_cyfr;
                $stmt_check = $polaczenie->prepare("SELECT id FROM uzytkownicy WHERE login = ?");
                $stmt_check->bind_param("s", $login);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows === 0) $login_istnieje = false;
            } while ($login_istnieje);
            // --- Koniec generowania loginu ---

            $email = $login . '@szkola.local'; // Przykładowy, unikalny email

            // 1. Dodaj wpis do tabeli `uzytkownicy`
            $stmt_uzytkownik = $polaczenie->prepare("INSERT INTO uzytkownicy (login, haslo, email, imie, nazwisko, rola) VALUES (?, ?, ?, ?, ?, 'uczen')");
            $stmt_uzytkownik->bind_param("sssss", $login, $haslo_hash, $email, $imie, $nazwisko);
            $stmt_uzytkownik->execute();
            
            // 2. Pobierz ID nowo utworzonego użytkownika
            $nowy_uzytkownik_id = $stmt_uzytkownik->insert_id;
            
            // 3. Dodaj wpis do tabeli `uczniowie_info`
            $nr_dziennika = $startowy_nr_dziennika + $index;
            $stmt_uczen_info = $polaczenie->prepare("INSERT INTO uczniowie_info (uzytkownik_id, nr_dziennika, klasa_id) VALUES (?, ?, ?)");
            $stmt_uczen_info->bind_param("iii", $nowy_uzytkownik_id, $nr_dziennika, $klasa_id);
            $stmt_uczen_info->execute();
        }
        
        $polaczenie->commit();
        $_SESSION['sukces'] = "Pomyślnie dodano " . count($linie) . " uczniów.";
        header('Location: zarzadzaj_klasami.php');
        exit();

    } catch (mysqli_sql_exception $exception) {
        $polaczenie->rollback();
        $komunikat_bledu = "Wystąpił błąd transakcji. Żaden uczeń nie został dodany. Błąd: " . $exception->getMessage();
    }
}

// Pobranie klas do listy wyboru
$klasy = $polaczenie->query("SELECT id, nazwa_klasy FROM klasy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_klasy")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodawanie masowe Uczniów</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            </header>
        <main id="glowny">
            <div class="karta">
                <h1>Hurtowe dodawanie uczniów</h1>
                <p>Wklej listę uczniów w formacie "Imię Nazwisko", każdy w nowej linii. Skrypt automatycznie utworzy dla nich konta i przypisze do wybranej klasy.</p>
                
                <?php if (isset($komunikat_bledu)) echo '<p class="error-text">' . $komunikat_bledu . '</p>'; ?>

                <form action="" method="post" style="padding: 0;">
                    <label for="klasa_id">Wybierz klasę docelową:</label><br>
                    <select name="klasa_id" id="klasa_id" required>
                        <option value="">-- Wybierz klasę --</option>
                        <?php foreach ($klasy as $klasa): ?>
                            <option value="<?php echo $klasa['id']; ?>"><?php echo htmlspecialchars($klasa['nazwa_klasy']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <br><br>

                    <label for="lista_uczniow">Wklej listę uczniów (Imię Nazwisko, każdy w nowej linii):</label><br>
                    <textarea name="lista_uczniow" id="lista_uczniow" rows="15" style="width: 100%;" placeholder="Jan Kowalski&#10;Anna Nowak&#10;Piotr Wiśniewski..."></textarea>
                    
                    <br><br>
                    <input type="submit" value="Dodaj Uczniów" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>