<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connect.php';
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

    $nazwa = $_POST['nazwa_przedmiotu'];
    $skrot = $_POST['skrot'];
    
    // Pobieramy ID aktywnego roku szkolnego z sesji
    $aktywny_rok_id = $_SESSION['aktywny_rok_id'];

    $stmt = $polaczenie->prepare("INSERT INTO przedmioty (rok_szkolny_id, nazwa_przedmiotu, skrot) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $aktywny_rok_id, $nazwa, $skrot);
    
    if ($stmt->execute()) {
        header('Location: zarzadzaj_przedmiotami.php');
        exit();
    } else {
        // Sprawdzamy, czy błąd to zduplikowana nazwa
        if ($polaczenie->errno === 1062) {
            $komunikat_bledu = "Przedmiot o takiej nazwie lub skrócie już istnieje.";
        } else {
            $komunikat_bledu = "Błąd podczas dodawania przedmiotu: " . $stmt->error;
        }
    }
    $polaczenie->close();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Nowy Przedmiot</title>
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
                <h1>Dodaj Nowy Przedmiot</h1>
                
                <?php if (isset($komunikat_bledu)) echo '<p class="error-text">' . $komunikat_bledu . '</p>'; ?>

                <form action="" method="post" style="padding: 0;">
                    Pełna nazwa przedmiotu: <br>
                    <input type="text" name="nazwa_przedmiotu" placeholder="np. Język polski" required>
                    
                    Skrót przedmiotu (max 20 znaków): <br>
                    <input type="text" name="skrot" placeholder="np. j.polski" required>
                    
                    <br><br>
                    <input type="submit" value="Dodaj przedmiot" class="przycisk">
                </form>
            </div>
        </main>
        
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>