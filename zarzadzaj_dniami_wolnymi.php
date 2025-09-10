<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dodaj_dzien'])) {
        $data = $_POST['data'];
        $nazwa = $_POST['nazwa'];
        $stmt = $polaczenie->prepare("INSERT INTO dni_wolne (rok_szkolny_id, data, nazwa) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $aktywny_rok_id, $data, $nazwa);
        $stmt->execute();
    }
    if (isset($_POST['usun_dzien'])) {
        $id_do_usuniecia = $_POST['id_dnia'];
        $stmt = $polaczenie->prepare("DELETE FROM dni_wolne WHERE id = ?");
        $stmt->bind_param("i", $id_do_usuniecia);
        $stmt->execute();
    }
    header('Location: zarzadzaj_dniami_wolnymi.php');
    exit();
}

// Pobieranie dni wolnych dla aktywnego roku
$dni_wolne = $polaczenie->query("SELECT id, data, nazwa FROM dni_wolne WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY data ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Zarządzanie Dniami Wolnymi</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Zarządzanie Dniami Wolnymi</h1>
            <p>Dodaj dni wolne od zajęć dydaktycznych dla aktywnego roku szkolnego.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <div>
                    <h3>Dodaj nowy dzień wolny</h3>
                    <form action="" method="post" style="padding: 0;">
                        <label>Data:</label>
                        <input type="date" name="data" required>
                        <label>Nazwa / Okazja:</label>
                        <input type="text" name="nazwa" placeholder="np. Egzaminy ósmoklasisty" required>
                        <button type="submit" name="dodaj_dzien" class="przycisk">Dodaj</button>
                    </form>
                </div>
                <div>
                    <h3>Lista dni wolnych w tym roku szkolnym</h3>
                    <table>
                        <thead><tr><th>Data</th><th>Nazwa</th><th>Akcja</th></tr></thead>
                        <tbody>
                            <?php foreach ($dni_wolne as $dzien): ?>
                            <tr>
                                <td><?php echo $dzien['data']; ?></td>
                                <td><?php echo htmlspecialchars($dzien['nazwa']); ?></td>
                                <td>
                                    <form action="" method="post" onsubmit="return confirm('Czy na pewno usunąć?');">
                                        <input type="hidden" name="id_dnia" value="<?php echo $dzien['id']; ?>">
                                        <button type="submit" name="usun_dzien" class="przycisk przycisk-usun">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>