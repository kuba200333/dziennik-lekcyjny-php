<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php'); exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

$wybrana_data = $_GET['data'] ?? date('Y-m-d');
$dzien_tygodnia = date('N', strtotime($wybrana_data));

// Pobieramy wszystkie lekcje z planu na dany dzień, które są prowadzone przez nieobecnego nauczyciela
// Pobieramy lekcje wymagające akcji ORAZ te, które już mają zdefiniowane zastępstwo na dany dzień
$sql = "SELECT 
            pl.id as plan_id, 
            pl.nr_lekcji,
            n.id as nauczanie_id,
            p.nazwa_przedmiotu, 
            CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel_oryginalny, 
            n.nauczyciel_id,
            COALESCE(k.nazwa_klasy, g.nazwa_grupy) as jednostka,
            z.id as zastepstwo_id,
            z.typ as typ_zastepstwa,
            CONCAT(uz.imie, ' ', uz.nazwisko) AS nauczyciel_zastepca
        FROM plan_lekcji pl
        JOIN nauczanie n ON pl.nauczanie_id = n.id
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        LEFT JOIN klasy k ON n.klasa_id = k.id
        LEFT JOIN grupy g ON n.grupa_id = g.id
        LEFT JOIN zastepstwa z ON n.id = z.oryginalne_nauczanie_id AND z.data = ?
        LEFT JOIN nauczanie nz ON z.nowe_nauczanie_id = nz.id
        LEFT JOIN uzytkownicy uz ON nz.nauczyciel_id = uz.id
        WHERE pl.rok_szkolny_id = ? 
        AND pl.dzien_tygodnia = ?
        AND ? BETWEEN pl.data_od AND pl.data_do
        AND n.nauczyciel_id IN (
            SELECT nn.nauczyciel_id FROM nieobecnosci_nauczycieli nn 
            WHERE ? BETWEEN nn.data_od AND nn.data_do
        )
        ORDER BY pl.nr_lekcji";

$stmt = $polaczenie->prepare($sql);
// Ważna zmiana: teraz przekazujemy 5 parametrów
$stmt->bind_param("siiss", $wybrana_data, $aktywny_rok_id, $dzien_tygodnia, $wybrana_data, $wybrana_data);
$stmt->execute();
$lekcje_do_zastepstwa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Zarządzanie Zastępstwami</title><link rel="stylesheet" href="style.css">
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
                <h1>Zarządzanie Zastępstwami</h1>
                <a href="dodaj_nieobecnosc.php" class="przycisk">Dodaj nieobecność nauczyciela</a>
            </div>
            <form action="" method="get">
                <label for="data">Pokaż lekcje wymagające zastępstwa w dniu:</label>
                <input type="date" name="data" value="<?php echo $wybrana_data; ?>" onchange="this.form.submit()">
            </form>
            <hr>
            <table>
                <thead><tr><th>Lekcja</th><th>Przedmiot</th><th>Klasa/Grupa</th><th>Nieobecny Nauczyciel</th><th>Status</th><th>Akcja</th></tr></thead>
                <tbody>
                    <?php if (empty($lekcje_do_zastepstwa)): ?>
                        <tr><td colspan="6">Brak lekcji wymagających zastępstwa w wybranym dniu.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lekcje_do_zastepstwa as $lekcja): ?>
                        <tr>
                            <td><?php echo $lekcja['nr_lekcji']; ?></td>
                            <td><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></td>
                            <td><?php echo htmlspecialchars($lekcja['jednostka']); ?></td>
                            <td><?php echo htmlspecialchars($lekcja['nauczyciel_oryginalny']); ?></td>
                            <td>
                                <?php if ($lekcja['typ_zastepstwa'] === 'zastepstwo'): ?>
                                    <span style="color: #27ae60; font-weight: bold;">Zastępstwo: <?php echo htmlspecialchars($lekcja['nauczyciel_zastepca']); ?></span>
                                <?php elseif ($lekcja['typ_zastepstwa'] === 'odwolane'): ?>
                                    <span style="color: #c0392b; font-weight: bold;">Lekcja odwołana</span>
                                <?php else: ?>
                                    <span style="color: #f39c12; font-weight: bold;">Wymaga akcji</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="akcja_zastepstwo.php?plan_id=<?php echo $lekcja['plan_id']; ?>&data=<?php echo $wybrana_data; ?>" class="przycisk">
                                    <?php echo $lekcja['zastepstwo_id'] ? 'Zmień' : 'Zarządzaj'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>