<?php
session_start();
// Zabezpieczenie: tylko admin ma dostęp
if (!isset($_SESSION['zalogowany']) || $_SESSION['rola'] !== 'admin') {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);

// Pobieramy wszystkich użytkowników z bazy danych
$wynik = $polaczenie->query("SELECT id, login, imie, nazwisko, email, rola, aktywny FROM uzytkownicy ORDER BY nazwisko, imie");
$uzytkownicy = $wynik->fetch_all(MYSQLI_ASSOC);

$polaczenie->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Użytkownikami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Prosty styl dla przycisków akcji w jednej linii */
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
                    <h1>Zarządzanie Użytkownikami</h1>
                    <a href="dodaj_uzytkownika.php" class="przycisk">Dodaj nowego użytkownika</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Lp.</th>
                            <th>Imię i Nazwisko</th>
                            <th>Login</th>
                            <th>Email</th>
                            <th>Rola</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uzytkownicy as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['imie'] . ' ' . $user['nazwisko']); ?></td>
                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['rola']); ?></td>
                                <td><?php echo $user['aktywny'] ? 'Aktywny' : 'Zablokowany'; ?></td>
                                <td>
                                    <a href="edytuj_uzytkownika.php?id=<?php echo $user['id']; ?>" class="przycisk">Edytuj</a>
                                    
                                    <form class="akcje-form" action="usun_uzytkownika.php" method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika? To działanie jest nieodwracalne!');">
                                        <input type="hidden" name="id_uzytkownika" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="przycisk przycisk-usun">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
        
        <footer id="stopka">
            &copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny
        </footer>
    </div>
</body>
</html>