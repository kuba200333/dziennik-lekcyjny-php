<?php
// Ta sekcja PHP na górze pliku będzie przetwarzać dane wysłane z formularza
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once 'connect.php'; // Dołączamy dane do połączenia z bazą

    // Połączenie z bazą danych
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    if ($polaczenie->connect_errno) {
        die("Błąd połączenia: " . $polaczenie->connect_error);
    }

    // Odbieramy dane z formularza
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $haslo = $_POST['haslo'];
    $rola = $_POST['rola'];

    // Zawsze szyfrujemy hasło!
    $haslo_hash = password_hash($haslo, PASSWORD_DEFAULT);

    // --- LOGIKA GENEROWANIA UNIKALNEGO LOGINU ---
    $login_istnieje = true;
    do {
        // Generujemy pierwszą cyfrę w zależności od roli
        // ZMIANA TUTAJ: Dyrektor dostaje login jak nauczyciel (nieparzysty)
        if ($rola === 'nauczyciel' || $rola === 'dyrektor') {
            // Pierwsza cyfra nieparzysta
            $pierwsza_cyfra = [1, 3, 5, 7, 9][array_rand([1, 3, 5, 7, 9])];
        } else { // 'uczen'
            // Pierwsza cyfra parzysta (bez zera)
            $pierwsza_cyfra = [2, 4, 6, 8][array_rand([2, 4, 6, 8])];
        }

        // Generujemy pozostałe 4 cyfry
        $reszta_cyfr = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $login = $pierwsza_cyfra . $reszta_cyfr;

        // Sprawdzamy, czy wygenerowany login już istnieje w bazie
        $stmt_check = $polaczenie->prepare("SELECT id FROM uzytkownicy WHERE login = ?");
        $stmt_check->bind_param("s", $login);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows === 0) {
            $login_istnieje = false;
        }
        $stmt_check->close();

    } while ($login_istnieje);
    // --- KONIEC LOGIKI GENEROWANIA LOGINU ---

    // Wstawiamy nowego użytkownika do bazy danych
    $stmt_insert = $polaczenie->prepare("INSERT INTO uzytkownicy (login, haslo, email, imie, nazwisko, rola) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("ssssss", $login, $haslo_hash, $email, $imie, $nazwisko, $rola);

    if ($stmt_insert->execute()) {
        // Pobieramy ID właśnie utworzonego użytkownika
        $nowy_uzytkownik_id = $stmt_insert->insert_id;

        // W zależności od roli, dodajemy wpis do odpowiedniej tabeli _info
        // ZMIANA TUTAJ: Dyrektor jest również dodawany do tabeli nauczycieli
        if ($rola === 'nauczyciel' || $rola === 'dyrektor') {
            $stmt_info = $polaczenie->prepare("INSERT INTO nauczyciele_info (uzytkownik_id) VALUES (?)");
            $stmt_info->bind_param("i", $nowy_uzytkownik_id);
            $stmt_info->execute();
            $stmt_info->close();
        } elseif ($rola === 'uczen') {
            $stmt_info = $polaczenie->prepare("INSERT INTO uczniowie_info (uzytkownik_id) VALUES (?)");
            $stmt_info->bind_param("i", $nowy_uzytkownik_id);
            $stmt_info->execute();
            $stmt_info->close();
        }

        $komunikat = "Utworzono nowego użytkownika: <b>" . htmlspecialchars($imie) . " " . htmlspecialchars($nazwisko) . "</b> (Rola: $rola). <br>Jego unikalny login to: <b>$login</b>";
    } else {
        $komunikat = "Błąd podczas tworzenia użytkownika: " . $stmt_insert->error;
    }

    $stmt_insert->close();
    $polaczenie->close();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Testowa Rejestracja Użytkowników</title>
    <link rel="stylesheet" href="style.css"> <style>
        .logowanie { max-width: 600px; margin: 40px auto; }
        .komunikat { padding: 15px; background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="logowanie">
        <h1>Formularz testowy - Tworzenie użytkowników</h1>
        <p>Ten formularz automatycznie wygeneruje unikalny, 5-cyfrowy login.</p>
        
        <form action="" method="post">
            Imię: <br>
            <input type="text" name="imie" required> <br>
            Nazwisko: <br>
            <input type="text" name="nazwisko" required> <br>
            Email: <br>
            <input type="email" name="email" required> <br>
            Hasło: <br>
            <input type="password" name="haslo" required> <br>
            Rola: <br>
            <select name="rola" required>
                <option value="uczen">Uczeń (login zacznie się cyfrą parzystą)</option>
                <option value="nauczyciel">Nauczyciel (login zacznie się cyfrą nieparzystą)</option>
                <option value="dyrektor">Dyrektor (login zacznie się cyfrą nieparzystą)</option>
            </select><br><br>
            <input type="submit" value="Stwórz użytkownika">
        </form>

        <?php
            if (isset($komunikat)) {
                echo '<div class="komunikat">' . $komunikat . '</div>';
            }
        ?>
        
        <p style="margin-top: 20px;"><a href="index.php">Przejdź do strony logowania</a></p>
    </div>
</body>
</html>