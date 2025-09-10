<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor', 'nauczyciel'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$id_nauczyciela = $_SESSION['uzytkownik_id'];
$rola = $_SESSION['rola'];

// Pobieramy kategorie systemowe (dla wszystkich)
$kategorie_systemowe = $polaczenie->query("SELECT * FROM kategorie_ocen WHERE nauczyciel_id IS NULL ORDER BY nazwa_kategorii")->fetch_all(MYSQLI_ASSOC);

// Pobieramy kategorie własne zalogowanego nauczyciela
$stmt = $polaczenie->prepare("SELECT * FROM kategorie_ocen WHERE nauczyciel_id = ? ORDER BY nazwa_kategorii");
$stmt->bind_param("i", $id_nauczyciela);
$stmt->execute();
$kategorie_wlasne = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$polaczenie->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Kategoriami Ocen</title>
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h1>Zarządzanie Kategoriami Ocen</h1>
                    <a href="dodaj_kategorie.php" class="przycisk">Dodaj własną kategorię</a>
                </div>

                <h2>Kategorie Własne</h2>
                <p>Kategorie widoczne tylko dla Ciebie.</p>
                <table>
                    <thead><tr><th>Nazwa</th><th>Waga</th><th>Typ</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($kategorie_wlasne as $k): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($k['nazwa_kategorii']); ?></td>
                            <td><?php echo htmlspecialchars($k['waga']); ?></td>
                            <td><?php echo htmlspecialchars($k['typ_oceny']); ?></td>
                            <td><a href="edytuj_kategorie.php?id=<?php echo $k['id']; ?>" class="przycisk">Edytuj</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr style="margin: 30px 0;">

                <h2>Kategorie Systemowe</h2>
                <p>Kategorie dostępne dla wszystkich nauczycieli. Może nimi zarządzać tylko administrator.</p>
                <table>
                    <thead><tr><th>Nazwa</th><th>Waga</th><th>Typ</th><?php if ($rola === 'admin') echo '<th>Akcje</th>'; ?></tr></thead>
                    <tbody>
                         <?php foreach($kategorie_systemowe as $k): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($k['nazwa_kategorii']); ?></td>
                            <td><?php echo htmlspecialchars($k['waga']); ?></td>
                            <td><?php echo htmlspecialchars($k['typ_oceny']); ?></td>
                            <?php if ($rola === 'admin'): ?>
                                <td><a href="edytuj_kategorie.php?id=<?php echo $k['id']; ?>" class="przycisk">Edytuj</a></td>
                            <?php endif; ?>
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