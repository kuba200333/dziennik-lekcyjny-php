<?php
session_start();
// Skopiuj zabezpieczenia i połączenie z bazą z pliku zarzadzaj_nauczaniem.php
// ...
if (!isset($_GET['grupa_id']) || !is_numeric($_GET['grupa_id'])) {
    header('Location: zarzadzaj_grupami.php');
    exit();
}
$id_grupy = $_GET['grupa_id'];
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Pobranie nazwy grupy
$grupa_nazwa = $polaczenie->query("SELECT nazwa_grupy FROM grupy WHERE id = $id_grupy")->fetch_assoc()['nazwa_grupy'];

// Pobranie przydziałów dla tej grupy
$sql = "SELECT n.id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        JOIN uzytkownicy u ON n.nauczyciel_id = u.id
        WHERE n.rok_szkolny_id = ? AND n.grupa_id = ?
        ORDER BY p.nazwa_przedmiotu";
$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $aktywny_rok_id, $id_grupy);
$stmt->execute();
$przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Nauczaniem w Grupie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
            </header>
        <main id="glowny">
            <div class="karta">
                 <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1>Przydziały dla grupy: <?php echo htmlspecialchars($grupa_nazwa); ?></h1>
                    <a href="dodaj_przydzial_grupy.php?grupa_id=<?php echo $id_grupy; ?>" class="przycisk">Dodaj nowy przydział</a>
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
                        <?php foreach ($przydzialy as $przydzial): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($przydzial['nazwa_przedmiotu']); ?></td>
                                <td><?php echo htmlspecialchars($przydzial['nauczyciel']); ?></td>
                                <td>
                                    <a href="edytuj_przydzial.php?id=<?php echo $przydzial['id']; ?>" class="przycisk">Edytuj</a>
                                    <form action="usun_przydzial.php" method="post" style="display:inline;" onsubmit="return confirm('Czy na pewno usunąć ten przydział?');">
                                        <input type="hidden" name="id_przydzialu" value="<?php echo $przydzial['id']; ?>">
                                        <input type="hidden" name="redirect_url" value="zarzadzaj_nauczaniem_grupy.php?grupa_id=<?php echo $id_grupy; ?>">
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