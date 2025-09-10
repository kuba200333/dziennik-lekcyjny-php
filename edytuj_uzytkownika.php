<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['rola'] !== 'admin') {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Sprawdzamy, czy ID użytkownika jest podane i jest liczbą
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_uzytkownikami.php');
    exit();
}
$id_uzytkownika = $_GET['id'];

// Obsługa wysłanego formularza (jeśli dane zostały zmienione)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tutaj powinna być walidacja danych!
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $rola = $_POST['rola'];
    $aktywny = $_POST['aktywny'];
    
    $stmt = $polaczenie->prepare("UPDATE uzytkownicy SET imie=?, nazwisko=?, email=?, rola=?, aktywny=? WHERE id=?");
    $stmt->bind_param("ssssii", $imie, $nazwisko, $email, $rola, $aktywny, $id_uzytkownika);
    $stmt->execute();
    
    // Opcjonalna zmiana hasła
    if (!empty($_POST['haslo'])) {
        $haslo_hash = password_hash($_POST['haslo'], PASSWORD_DEFAULT);
        $stmt_pass = $polaczenie->prepare("UPDATE uzytkownicy SET haslo=? WHERE id=?");
        $stmt_pass->bind_param("si", $haslo_hash, $id_uzytkownika);
        $stmt_pass->execute();
    }
    
    header('Location: zarzadzaj_uzytkownikami.php');
    exit();
}

// Pobranie aktualnych danych użytkownika do wyświetlenia w formularzu
$stmt = $polaczenie->prepare("SELECT imie, nazwisko, email, rola, aktywny FROM uzytkownicy WHERE id = ?");
$stmt->bind_param("i", $id_uzytkownika);
$stmt->execute();
$wynik = $stmt->get_result();
if ($wynik->num_rows === 0) {
    // Jeśli nie znaleziono użytkownika o takim ID
    header('Location: zarzadzaj_uzytkownikami.php');
    exit();
}
$uzytkownik = $wynik->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Użytkownika</title>
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
                <h1>Edytuj dane użytkownika: <?php echo htmlspecialchars($uzytkownik['imie'] . ' ' . $uzytkownik['nazwisko']); ?></h1>
                
                <form action="" method="post">
                    Imię: <br>
                    <input type="text" name="imie" value="<?php echo htmlspecialchars($uzytkownik['imie']); ?>" required>
                    
                    Nazwisko: <br>
                    <input type="text" name="nazwisko" value="<?php echo htmlspecialchars($uzytkownik['nazwisko']); ?>" required>
                    
                    Email: <br>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($uzytkownik['email']); ?>" >
                    
                    Nowe hasło: <br>
                    <input type="password" name="haslo" placeholder="Wypełnij tylko, jeśli chcesz zmienić hasło">
                    
                    Rola: <br>
                    <select name="rola" required>
                        <option value="uczen" <?php if($uzytkownik['rola'] == 'uczen') echo 'selected'; ?>>Uczeń</option>
                        <option value="nauczyciel" <?php if($uzytkownik['rola'] == 'nauczyciel') echo 'selected'; ?>>Nauczyciel</option>
                        <option value="dyrektor" <?php if($uzytkownik['rola'] == 'dyrektor') echo 'selected'; ?>>Dyrektor</option>
                        <option value="admin" <?php if($uzytkownik['rola'] == 'admin') echo 'selected'; ?>>Admin</option>
                    </select>

                    Status: <br>
                    <select name="aktywny" required>
                        <option value="1" <?php if($uzytkownik['aktywny'] == 1) echo 'selected'; ?>>Aktywny</option>
                        <option value="0" <?php if($uzytkownik['aktywny'] == 0) echo 'selected'; ?>>Zablokowany</option>
                    </select>
                    <br><br>
                    <input type="submit" value="Zapisz zmiany" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>