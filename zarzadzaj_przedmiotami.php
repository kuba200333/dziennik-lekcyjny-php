<?php
session_start();
// Zabezpieczenie: tylko admin lub dyrektor ma dostęp
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
if ($polaczenie->connect_errno) {
    die("Błąd połączenia: " . $polaczenie->connect_error);
}

// --- POBIERANIE WSZYSTKICH DANYCH NA POCZĄTKU ---

// 1. Pobieramy ID aktywnego roku szkolnego z sesji
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// 2. Pobieramy nazwę aktywnego roku szkolnego
$rok_szkolny_nazwa = 'Nieznany'; // Wartość domyślna
$wynik_roku = $polaczenie->query("SELECT nazwa FROM lata_szkolne WHERE id = $aktywny_rok_id");
if ($rok = $wynik_roku->fetch_assoc()) {
    $rok_szkolny_nazwa = $rok['nazwa'];
}

// 3. Pobieramy listę przedmiotów TYLKO dla aktywnego roku szkolnego
$stmt = $polaczenie->prepare("SELECT id, nazwa_przedmiotu, skrot FROM przedmioty WHERE rok_szkolny_id = ? ORDER BY nazwa_przedmiotu");
$stmt->bind_param("i", $aktywny_rok_id);
$stmt->execute();
$wynik = $stmt->get_result();
$przedmioty = $wynik->fetch_all(MYSQLI_ASSOC);

// --- KONIEC POBIERANIA DANYCH ---
$polaczenie->close(); // Zamykamy połączenie dopiero teraz, gdy mamy już wszystkie dane.
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Przedmiotami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .akcje-form { display: inline-block; margin-right: 5px; }
        .przycisk-usun { background-color: #e74c3c; }
        .przycisk-usun:hover { background-color: #c0392b; }
    </style>
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1>Zarządzanie Przedmiotami</h1>
                    <a href="dodaj_przedmiot.php" class="przycisk">Dodaj nowy przedmiot</a>
                </div>
                <p>Wyświetlane przedmioty dla aktywnego roku szkolnego: <strong><?php echo htmlspecialchars($rok_szkolny_nazwa); ?></strong></p>

                <table>
                    <thead>
                        <tr>
                            <th>Lp.</th>
                            <th>Pełna Nazwa Przedmiotu</th>
                            <th>Skrót</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($przedmioty) > 0): ?>
                            <?php foreach ($przedmioty as $index => $przedmiot): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($przedmiot['nazwa_przedmiotu']); ?></td>
                                    <td><?php echo htmlspecialchars($przedmiot['skrot']); ?></td>
                                    <td>
                                        <a href="#" class="przycisk">Edytuj</a>
                                        <button class="przycisk przycisk-usun">Usuń</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">Brak zdefiniowanych przedmiotów dla tego roku szkolnego.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>