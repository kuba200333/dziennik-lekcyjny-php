<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_grupami.php');
    exit();
}
$id_grupy = $_GET['id'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Obsługa zapisu formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uczniowie_w_grupie = $_POST['uczniowie_ids'] ?? [];

    $polaczenie->begin_transaction();
    try {
        // 1. Czyścimy obecny skład grupy
        $stmt_usun = $polaczenie->prepare("DELETE FROM przypisania_grup WHERE grupa_id = ?");
        $stmt_usun->bind_param("i", $id_grupy);
        $stmt_usun->execute();

        // 2. Dodajemy na nowo zaznaczonych uczniów
        if (!empty($uczniowie_w_grupie)) {
            $stmt_dodaj = $polaczenie->prepare("INSERT INTO przypisania_grup (grupa_id, uczen_id) VALUES (?, ?)");
            foreach ($uczniowie_w_grupie as $uczen_id) {
                $stmt_dodaj->bind_param("ii", $id_grupy, $uczen_id);
                $stmt_dodaj->execute();
            }
        }
        $polaczenie->commit();
    } catch (mysqli_sql_exception $exception) {
        $polaczenie->rollback();
        die("Błąd zapisu: " . $exception->getMessage());
    }

    header('Location: zarzadzaj_grupami.php');
    exit();
}

// Pobieranie danych do wyświetlenia
$grupa = $polaczenie->query("SELECT nazwa_grupy FROM grupy WHERE id = $id_grupy")->fetch_assoc();
$uczniowie_przypisani_wynik = $polaczenie->query("SELECT uczen_id FROM przypisania_grup WHERE grupa_id = $id_grupy");
$uczniowie_przypisani_ids = array_column($uczniowie_przypisani_wynik->fetch_all(MYSQLI_ASSOC), 'uczen_id');

// Pobieramy WSZYSTKICH aktywnych uczniów z ich klasami w BIEŻĄCYM ROKU SZKOLNYM
$sql = "SELECT u.id, u.imie, u.nazwisko, k.nazwa_klasy 
        FROM uzytkownicy u 
        JOIN uczniowie_info ui ON u.id = ui.uzytkownik_id 
        LEFT JOIN klasy k ON ui.klasa_id = k.id 
        WHERE u.rola = 'uczen' AND u.aktywny = 1 AND (k.rok_szkolny_id = ? OR ui.klasa_id IS NULL) 
        ORDER BY k.nazwa_klasy, u.nazwisko, u.imie";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("i", $aktywny_rok_id);
$stmt->execute();
$wszyscy_uczniowie = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzaj Składem Grupy</title>
    <link rel="stylesheet" href="style.css">
    <style> .lista-klas { margin-bottom: 20px; } .lista-klas h3 { margin-top: 15px; } </style>
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
             </header>
        <main id="glowny">
            <div class="karta">
                <h1>Zarządzaj składem grupy: <?php echo htmlspecialchars($grupa['nazwa_grupy']); ?></h1>
                <p>Zaznacz uczniów, którzy mają należeć do tej grupy.</p>
                <form action="" method="post">
                    <?php
                    $aktualna_klasa = null;
                    foreach ($wszyscy_uczniowie as $uczen):
                        // Wyświetl nagłówek klasy, jeśli się zmienił
                        if ($uczen['nazwa_klasy'] !== $aktualna_klasa):
                            $aktualna_klasa = $uczen['nazwa_klasy'];
                            echo '<h3>' . ($aktualna_klasa ? htmlspecialchars($aktualna_klasa) : 'Uczniowie nieprzypisani') . '</h3>';
                        endif;
                        
                        $czy_zaznaczony = in_array($uczen['id'], $uczniowie_przypisani_ids) ? 'checked' : '';
                    ?>
                        <div class="uczen-checkbox">
                            <label>
                                <input type="checkbox" name="uczniowie_ids[]" value="<?php echo $uczen['id']; ?>" <?php echo $czy_zaznaczony; ?>>
                                <?php echo htmlspecialchars($uczen['nazwisko'] . ' ' . $uczen['imie']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <br>
                    <input type="submit" value="Zapisz skład grupy" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>