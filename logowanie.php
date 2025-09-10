<?php
session_start();
require_once 'connect.php'; // Zakładam, że masz plik connect.php z danymi do bazy

// Sprawdzamy, czy formularz został wysłany
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Nawiązanie połączenia z bazą danych (w stylu obiektowym - jest czytelniej)
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

    // Sprawdzenie, czy połączenie się udało
    if ($polaczenie->connect_errno) {
        die("Błąd połączenia z bazą danych: " . $polaczenie->connect_error);
    }

    $login = $_POST['login'];
    $haslo = $_POST['haslo'];

    // --- NAJWAŻNIEJSZA ZMIANA: ZAPYTANIA PRZYGOTOWANE ---
    // 1. Przygotowujemy zapytanie z placeholderem (?) zamiast wklejać zmienną
    $sql = "SELECT id, haslo, rola, imie, nazwisko FROM uzytkownicy WHERE login = ? AND aktywny = 1";
    $stmt = $polaczenie->prepare($sql);

    // 2. Podpinamy zmienną $login do placeholdera. "s" oznacza, że to string (tekst).
    $stmt->bind_param("s", $login);

    // 3. Wykonujemy zapytanie
    $stmt->execute();

    // 4. Pobieramy wynik
    $rezultat = $stmt->get_result();

    if ($rezultat->num_rows === 1) {
        $uzytkownik = $rezultat->fetch_assoc();

        // Sprawdzamy, czy hasło z formularza pasuje do hasła (hashu) w bazie
        if (password_verify($haslo, $uzytkownik['haslo'])) {
            
            // Hasło poprawne - logujemy użytkownika
            $_SESSION['zalogowany'] = true;
            $_SESSION['uzytkownik_id'] = $uzytkownik['id'];
            $_SESSION['imie'] = $uzytkownik['imie'];
            $_SESSION['nazwisko'] = $uzytkownik['nazwisko'];
            $_SESSION['rola'] = $uzytkownik['rola']; // <-- TUTAJ ZAPISUJEMY ROLĘ!

            // Pobieramy ID aktywnego roku szkolnego
            $wynik_roku = $polaczenie->query("SELECT id FROM lata_szkolne WHERE aktywny = 1 LIMIT 1");
            if ($wynik_roku->num_rows === 1) {
                $rok = $wynik_roku->fetch_assoc();
                $_SESSION['aktywny_rok_id'] = $rok['id'];
            } else {
                // Coś jest nie tak, nie ma aktywnego roku.
                // Można tu wylogować użytkownika lub pokazać błąd.
                $_SESSION['blad'] = 'Błąd krytyczny: Brak aktywnego roku szkolnego w systemie!';
                header('Location: index.php');
                exit();
            }

            // Czyszczenie ewentualnych starych błędów
            unset($_SESSION['blad']);
            
            // Przekierowanie do głównej strony dziennika
            header('Location: dziennik.php');
            exit(); // Ważne: kończymy skrypt po przekierowaniu

        } else {
            // Hasło nieprawidłowe
            $_SESSION['blad'] = 'Nieprawidłowy login lub hasło!';
            header('Location: index.php');
            exit();
        }

    } else {
        // Użytkownik nie znaleziony
        $_SESSION['blad'] = 'Nieprawidłowy login lub hasło!';
        header('Location: index.php');
        exit();
    }

    $stmt->close();
    $polaczenie->close();
}
?>