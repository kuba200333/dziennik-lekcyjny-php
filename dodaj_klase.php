<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Obsługa wysłanego formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa_klasy = $_POST['nazwa_klasy'];
    $skrot_klasy = $_POST['skrot_klasy'];
    // Używamy operatora ?? aby bezpiecznie obsłużyć brak wyboru wychowawcy
    $wychowawca_id = $_POST['wychowawca_id'] ?: NULL; 
    
    // Pobieramy ID aktywnego roku szkolnego z sesji!
    $aktywny_rok_id = $_SESSION['aktywny_rok_id'];

    $stmt = $polaczenie->prepare("INSERT INTO klasy (rok_szkolny_id, nazwa_klasy, skrot_klasy, wychowawca_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $aktywny_rok_id, $nazwa_klasy, $skrot_klasy, $wychowawca_id);
    
    if ($stmt->execute()) {
        header('Location: zarzadzaj_klasami.php');
        exit();
    } else {
        $komunikat_bledu = "Błąd podczas dodawania klasy: " . $stmt->error;
    }
}

// Pobieramy listę nauczycieli do wyświetlenia w formularzu
$nauczyciele_wynik = $polaczenie->query("SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u WHERE u.rola IN ('nauczyciel', 'dyrektor', 'admin') AND u.aktywny = 1 ORDER BY u.nazwisko");
$nauczyciele = $nauczyciele_wynik->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Nową Klasę</title>
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
                <h1>Dodaj Nową Klasę</h1>
                
                <?php if (isset($komunikat_bledu)) echo '<p class="error-text">' . $komunikat_bledu . '</p>'; ?>

                <form action="" method="post" style="padding: 0;">
                    Pełna nazwa klasy: <br>
                    <input type="text" name="nazwa_klasy" placeholder="np. Klasa 1 Technik Informatyk" required>
                    
                    Skrót klasy: <br>
                    <input type="text" name="skrot_klasy" placeholder="np. 1TI" required>
                    
                    Wychowawca (opcjonalnie): <br>
                    <select name="wychowawca_id">
                        <option value="">-- Brak --</option>
                        <?php foreach ($nauczyciele as $nauczyciel): ?>
                            <option value="<?php echo $nauczyciel['id']; ?>">
                                <?php echo htmlspecialchars($nauczyciel['imie'] . ' ' . $nauczyciel['nazwisko']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <input type="submit" value="Dodaj klasę" class="przycisk">
                </form>
            </div>
        </main>
        
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>