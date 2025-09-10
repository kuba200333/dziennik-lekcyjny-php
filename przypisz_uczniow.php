<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$id_klasy = $_GET['id'];

// Obsługa formularza zapisu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uczniowie_w_klasie = $_POST['uczniowie'] ?? [];

    // 1. Wypisujemy wszystkich obecnych uczniów z tej klasy (ustawiamy klasa_id = NULL)
    $stmt_wypisz = $polaczenie->prepare("UPDATE uczniowie_info SET klasa_id = NULL WHERE klasa_id = ?");
    $stmt_wypisz->bind_param("i", $id_klasy);
    $stmt_wypisz->execute();

    // 2. Przypisujemy zaznaczonych uczniów do tej klasy
    if (!empty($uczniowie_w_klasie)) {
        $placeholders = implode(',', array_fill(0, count($uczniowie_w_klasie), '?'));
        $types = str_repeat('i', count($uczniowie_w_klasie));
        
        $stmt_przypisz = $polaczenie->prepare("UPDATE uczniowie_info SET klasa_id = ? WHERE uzytkownik_id IN ($placeholders)");
        $stmt_przypisz->bind_param("i" . $types, $id_klasy, ...$uczniowie_w_klasie);
        $stmt_przypisz->execute();
    }
    
    header('Location: zarzadzaj_klasami.php');
    exit();
}

// Pobranie danych o klasie
$stmt_klasa = $polaczenie->prepare("SELECT nazwa_klasy FROM klasy WHERE id = ?");
$stmt_klasa->bind_param("i", $id_klasy);
$stmt_klasa->execute();
$klasa_nazwa = $stmt_klasa->get_result()->fetch_assoc()['nazwa_klasy'];

// Pobranie uczniów przypisanych do TEJ klasy
$stmt_uczniowie_w_klasie = $polaczenie->prepare("SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN uczniowie_info ui ON u.id = ui.uzytkownik_id WHERE ui.klasa_id = ? ORDER BY u.nazwisko, u.imie");
$stmt_uczniowie_w_klasie->bind_param("i", $id_klasy);
$stmt_uczniowie_w_klasie->execute();
$uczniowie_w_klasie = $stmt_uczniowie_w_klasie->get_result()->fetch_all(MYSQLI_ASSOC);

// Pobranie uczniów bez przydziału (wolnych)
$uczniowie_wolni_wynik = $polaczenie->query("SELECT u.id, u.imie, u.nazwisko FROM uzytkownicy u JOIN uczniowie_info ui ON u.id = ui.uzytkownik_id WHERE ui.klasa_id IS NULL AND u.rola = 'uczen' ORDER BY u.nazwisko, u.imie");
$uczniowie_wolni = $uczniowie_wolni_wynik->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzaj Uczniami Klasy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .kontener-uczniow { display: flex; justify-content: space-between; }
        .lista-uczniow { width: 48%; }
        .lista-uczniow ul { list-style-type: none; padding: 10px; background-color: var(--kolor-tlo); border-radius: 5px; height: 300px; overflow-y: auto; }
        .lista-uczniow li { padding: 5px; }
    </style>
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
             </header>
        <main id="glowny">
            <div class="karta">
                <h1>Zarządzaj składem klasy: <?php echo htmlspecialchars($klasa_nazwa); ?></h1>
                <form action="" method="post">
                    <div class="kontener-uczniow">
                        <div class="lista-uczniow">
                            <h3>Uczniowie w tej klasie</h3>
                            <ul>
                                <?php foreach($uczniowie_w_klasie as $uczen): ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" name="uczniowie[]" value="<?php echo $uczen['id']; ?>" checked>
                                            <?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="lista-uczniow">
                            <h3>Uczniowie bez przydziału</h3>
                             <ul>
                                <?php foreach($uczniowie_wolni as $uczen): ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" name="uczniowie[]" value="<?php echo $uczen['id']; ?>">
                                            <?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <br>
                    <input type="submit" value="Zapisz zmiany w składzie klasy" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>