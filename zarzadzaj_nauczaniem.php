<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}
if (!isset($_GET['klasa_id']) || !is_numeric($_GET['klasa_id'])) {
    header('Location: zarzadzaj_klasami.php');
    exit();
}
$id_klasy = $_GET['klasa_id'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Pobranie nazwy klasy
$klasa_nazwa = $polaczenie->query("SELECT nazwa_klasy FROM klasy WHERE id = $id_klasy")->fetch_assoc()['nazwa_klasy'];

// Pobranie przydziałów TYLKO dla całej klasy (grupa_id IS NULL)
$sql = "SELECT n.id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        WHERE n.rok_szkolny_id = ? AND n.klasa_id = ? AND n.grupa_id IS NULL
        ORDER BY p.nazwa_przedmiotu";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $aktywny_rok_id, $id_klasy);
$stmt->execute();
$przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Nauczaniem w Klasie</title>
    <link rel="stylesheet" href="style.css">
    <style> .przycisk-usun { background-color: #e74c3c; } </style>
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
                    <h1>Przydziały dla klasy: <?php echo htmlspecialchars($klasa_nazwa); ?></h1>
                    <a href="dodaj_przydzial_klasy.php?klasa_id=<?php echo $id_klasy; ?>" class="przycisk">Dodaj nowy przydział</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Przedmiot</th>
                            <th>Nauczyciel</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($przydzialy as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['nazwa_przedmiotu']); ?></td>
                            <td><?php echo htmlspecialchars($p['nauczyciel']); ?></td>
                            <td>
                                <a href="edytuj_przydzial.php?id=<?php echo $p['id']; ?>" class="przycisk">Edytuj</a>
                                <form action="usun_przydzial.php" method="post" style="display:inline;" onsubmit="return confirm('Czy na pewno usunąć ten przydział?');">
                                    <input type="hidden" name="id_przydzialu" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="zarzadzaj_nauczaniem.php?klasa_id=<?php echo $id_klasy; ?>">
                                    <button type="submit" class="przycisk przycisk-usun">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>