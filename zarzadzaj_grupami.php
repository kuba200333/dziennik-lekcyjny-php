<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Pobieramy grupy TYLKO dla aktywnego roku szkolnego
$sql = "SELECT g.id, g.nazwa_grupy, g.skrot_grupy, COUNT(pg.id) AS liczba_uczniow
        FROM grupy g
        LEFT JOIN przypisania_grup pg ON g.id = pg.grupa_id
        WHERE g.rok_szkolny_id = ?
        GROUP BY g.id
        ORDER BY g.nazwa_grupy";

$stmt = $polaczenie->prepare($sql);
$stmt->bind_param("i", $aktywny_rok_id);
$stmt->execute();
$grupy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$polaczenie->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Grupami</title>
    <link rel="stylesheet" href="style.css">
    <style> .akcje-form { display: inline-block; } .przycisk-usun { background-color: #e74c3c; } </style>
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
                    <h1>Zarządzanie Grupami</h1>
                    <a href="dodaj_grupe.php" class="przycisk">Dodaj nową grupę</a>
                </div>
                <p>Lista grup w aktywnym roku szkolnym.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Nazwa Grupy</th>
                            <th>Skrót</th>
                            <th>Liczba uczniów</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupy as $grupa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grupa['nazwa_grupy']); ?></td>
                                <td><?php echo htmlspecialchars($grupa['skrot_grupy']); ?></td>
                                <td><?php echo $grupa['liczba_uczniow']; ?></td>
                                <td>
                                    <a href="zarzadzaj_nauczaniem_grupy.php?grupa_id=<?php echo $grupa['id']; ?>" class="przycisk">Nauczanie</a>
                                    <a href="zarzadzaj_skladem_grupy.php?id=<?php echo $grupa['id']; ?>" class="przycisk">Zarządzaj składem</a>
                                    <a href="edytuj_grupe.php?id=<?php echo $grupa['id']; ?>" class="przycisk">Edytuj</a>
                                    <button class="przycisk przycisk-usun">Usuń</button>
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