<?php
session_start();
// Zabezpieczenie: dostęp dla nauczycieli, dyrektorów i adminów
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['nauczyciel', 'dyrektor', 'admin'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];
$id_nauczyciela = $_SESSION['uzytkownik_id'];

// Pobieramy wszystkie przydziały (nauczanie) dla zalogowanego nauczyciela w aktywnym roku szkolnym
$sql = "SELECT 
            n.id AS przydzial_id,
            p.nazwa_przedmiotu,
            k.nazwa_klasy,
            g.nazwa_grupy,
            k.id AS klasa_id,
            g.id AS grupa_id
        FROM nauczanie n
        JOIN przedmioty p ON n.przedmiot_id = p.id
        LEFT JOIN klasy k ON n.klasa_id = k.id
        LEFT JOIN grupy g ON n.grupa_id = g.id
        WHERE n.nauczyciel_id = ? AND n.rok_szkolny_id = ?
        ORDER BY k.nazwa_klasy, g.nazwa_grupy, p.nazwa_przedmiotu";

$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("ii", $id_nauczyciela, $aktywny_rok_id);
$stmt->execute();
$przydzialy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$polaczenie->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moje Nauczanie</title>
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
                <h1>Moje zajęcia lekcyjne</h1>
                <p>Wybierz zajęcia, aby przejść do panelu ocen.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Przedmiot</th>
                            <th>Klasa / Grupa</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($przydzialy)): ?>
                            <tr>
                                <td colspan="3">Nie masz żadnych przydziałów w tym roku szkolnym.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($przydzialy as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['nazwa_przedmiotu']); ?></td>
                                    <td><?php echo htmlspecialchars($p['nazwa_klasy'] ?? $p['nazwa_grupy']); ?></td>
                                    <td>
                                        <a href="panel_ocen_nauczyciela.php?przydzial_id=<?php echo $p['przydzial_id']; ?>" class="przycisk">Oceny</a>
                                        <a href="#" class="przycisk">Frekwencja</a>
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