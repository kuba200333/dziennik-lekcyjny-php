<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connect.php';
    $polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
    
    $aktywny_rok_id = $_SESSION['aktywny_rok_id'];
    $nazwa = $_POST['nazwa_grupy'];
    $skrot = $_POST['skrot_grupy'];
    $opis = $_POST['opis'];
    
    $stmt = $polaczenie->prepare("INSERT INTO grupy (rok_szkolny_id, nazwa_grupy, skrot_grupy, opis) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $aktywny_rok_id, $nazwa, $skrot, $opis);
    $stmt->execute();
    
    header('Location: zarzadzaj_grupami.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Nową Grupę</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="kontener">
        <?php require_once 'szablony/menu.php'; ?>
        <header id="naglowek">
             </header>
        <main id="glowny">
            <div class="karta">
                <h1>Dodaj Nową Grupę</h1>
                <form action="" method="post" style="padding: 0;">
                    Nazwa grupy: <br>
                    <input type="text" name="nazwa_grupy" placeholder="np. Grupa Angielski B2" required>
                    
                    Skrót grupy: <br>
                    <input type="text" name="skrot_grupy" placeholder="np. ANG-B2" required>
                    
                    Opis (opcjonalnie): <br>
                    <textarea name="opis"></textarea>
                    <br><br>
                    <input type="submit" value="Dodaj grupę" class="przycisk">
                </form>
            </div>
        </main>
        <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
    </div>
</body>
</html>