<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$id_przydzialu = $_GET['id'];

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nauczyciel_id = $_POST['nauczyciel_id'];
    $redirect_url = $_POST['redirect_url'];
    
    $stmt = $polaczenie->prepare("UPDATE nauczanie SET nauczyciel_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $nauczyciel_id, $id_przydzialu);
    $stmt->execute();
    
    header('Location: ' . $redirect_url);
    exit();
}

// Pobranie danych przydziału
$sql = "SELECT p.nazwa_przedmiotu, n.nauczyciel_id, k.nazwa_klasy, g.nazwa_grupy, n.klasa_id, n.grupa_id
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        LEFT JOIN klasy k ON n.klasa_id = k.id
        LEFT JOIN grupy g ON n.grupa_id = g.id
        WHERE n.id = ?";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("i", $id_przydzialu);
$stmt->execute();
$przydzial = $stmt->get_result()->fetch_assoc();

// Pobranie listy nauczycieli
$nauczyciele = $polaczenie->query("SELECT id, imie, nazwisko FROM uzytkownicy WHERE rola IN ('nauczyciel', 'dyrektor', 'admin') AND aktywny = 1 ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Przydział</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
             </header>
        <main id="glowny">
            <div class="karta">
                <h1>Edytuj przydział</h1>
                <p><strong>Przedmiot:</strong> <?php echo htmlspecialchars($przydzial['nazwa_przedmiotu']); ?></p>
                <p><strong>Klasa/Grupa:</strong> <?php echo htmlspecialchars($przydzial['nazwa_klasy'] ?? $przydzial['nazwa_grupy']); ?></p>

                <form action="" method="post" style="padding: 0;">
                    Nowy nauczyciel:<br>
                    <select name="nauczyciel_id" required>
                        <?php foreach ($nauczyciele as $n): ?>
                            <option value="<?php echo $n['id']; ?>" <?php if($przydzial['nauczyciel_id'] == $n['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($n['imie'] . ' ' . $n['nazwisko']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
                    <br><br>
                    <input type="submit" value="Zapisz zmianę" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>