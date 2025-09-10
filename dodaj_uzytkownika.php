<?php
session_start();
// Zabezpieczenie: tylko admin ma dostęp
if (!isset($_SESSION['zalogowany']) || $_SESSION['rola'] !== 'admin') {
    header('Location: dziennik.php');
    exit();
}

// Obsługa formularza po jego wysłaniu
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    require_once 'connect.php';
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

    $haslo_hash = password_hash($haslo, PASSWORD_DEFAULT);

    // --- LOGIKA GENEROWANIA UNIKALNEGO LOGINU ---
    $login_istnieje = true;
    do {
        if ($rola === 'nauczyciel' || $rola === 'dyrektor' || $rola === 'admin') {
            $pierwsza_cyfra = [1, 3, 5, 7, 9][array_rand([1, 3, 5, 7, 9])];
        } else { // 'uczen'
            $pierwsza_cyfra = [2, 4, 6, 8][array_rand([2, 4, 6, 8])];
        }
        $reszta_cyfr = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $login = $pierwsza_cyfra . $reszta_cyfr;

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
        $nowy_uzytkownik_id = $stmt_insert->insert_id;

        if ($rola === 'nauczyciel' || $rola === 'dyrektor' || $rola === 'admin') {
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
        
        // Ustawiamy komunikat sukcesu i przekierowujemy
        $_SESSION['sukces'] = "Pomyślnie dodano użytkownika. Jego login to: <b>$login</b>";
        header('Location: zarzadzaj_uzytkownikami.php');
        exit();
    } else {
        $komunikat_bledu = "Błąd: " . $stmt_insert->error;
    }
    $stmt_insert->close();
    $polaczenie->close();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Nowego Użytkownika</title>
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
                <h1>Dodaj Nowego Użytkownika</h1>
                
                <?php
                if (isset($komunikat_bledu)) {
                    echo '<p class="error-text">' . $komunikat_bledu . '</p>';
                }
                ?>

                <form action="" method="post" style="padding: 0;">
                    Imię: <br>
                    <input type="text" name="imie" required>
                    
                    Nazwisko: <br>
                    <input type="text" name="nazwisko" required>
                    
                    Email: <br>
                    <input type="email" name="email" >
                    
                    Hasło (tymczasowe): <br>
                    <input type="password" name="haslo" required>
                    
                    Rola: <br>
                    <select name="rola" required>
                        <option value="uczen">Uczeń</option>
                        <option value="nauczyciel">Nauczyciel</option>
                        <option value="dyrektor">Dyrektor</option>
                        <option value="admin">Admin</option>
                    </select>
                    <br><br>
                    <input type="submit" value="Dodaj użytkownika" class="przycisk">
                </form>
            </div>
        </main>
        
        <footer id="stopka">
            &copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny
        </footer>
    </div>
</body>
</html>