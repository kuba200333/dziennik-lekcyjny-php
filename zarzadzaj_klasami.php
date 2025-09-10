<?php
session_start();
// Zabezpieczenie: tylko admin lub dyrektor ma dostęp
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Pobieramy ID aktywnego roku szkolnego z sesji!
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Pobieramy klasy TYLKO dla aktywnego roku szkolnego
$sql = "SELECT k.id, k.nazwa_klasy, k.skrot_klasy, CONCAT(u.imie, ' ', u.nazwisko) AS wychowawca
        FROM klasy k
        LEFT JOIN uzytkownicy u ON k.wychowawca_id = u.id
        WHERE k.rok_szkolny_id = ?
        ORDER BY k.nazwa_klasy";

$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("i", $aktywny_rok_id);
$stmt->execute();
$wynik = $stmt->get_result();
$klasy = $wynik->fetch_all(MYSQLI_ASSOC);

$polaczenie->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Klasami</title>
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
                    <h1>Zarządzanie Klasami</h1>
                    <a href="dodaj_klase.php" class="przycisk">Dodaj nową klasę</a>
                </div>
                <p>Lista klas w aktywnym roku szkolnym.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Lp.</th>
                            <th>Nazwa Klasy</th>
                            <th>Skrót</th>
                            <th>Wychowawca</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($klasy) > 0): ?>
                            <?php foreach ($klasy as $index => $klasa): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($klasa['nazwa_klasy']); ?></td>
                                    <td><?php echo htmlspecialchars($klasa['skrot_klasy']); ?></td>
                                    <td><?php echo $klasa['wychowawca'] ? htmlspecialchars($klasa['wychowawca']) : '<i>Brak</i>'; ?></td>
                                    <td>
                                        <a href="zarzadzaj_nauczaniem.php?klasa_id=<?php echo $klasa['id']; ?>" class="przycisk">Nauczanie</a>
                                        <a href="przypisz_uczniow.php?id=<?php echo $klasa['id']; ?>" class="przycisk">Uczniowie</a>
                                        <a href="edytuj_klase.php?id=<?php echo $klasa['id']; ?>" class="przycisk">Edytuj</a>
                                        <button class="przycisk przycisk-usun">Usuń</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Brak zdefiniowanych klas dla tego roku szkolnego.</td>
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