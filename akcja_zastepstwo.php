<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php'); exit();
}
if (!isset($_GET['plan_id']) || !isset($_GET['data'])) {
    header('Location: zarzadzaj_zastepstwami.php'); exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

$plan_id = (int)$_GET['plan_id'];
$data = $_GET['data'];
$dzien_tygodnia = date('N', strtotime($data));

// Pobieranie informacji o lekcji z planu
$sql_lekcja = "SELECT pl.nr_lekcji, n.id as nauczanie_id, p.nazwa_przedmiotu, COALESCE(k.nazwa_klasy, g.nazwa_grupy) as jednostka
               FROM plan_lekcji pl JOIN nauczanie n ON pl.nauczanie_id = n.id JOIN przedmioty p ON n.przedmiot_id = p.id
               LEFT JOIN klasy k ON n.klasa_id = k.id LEFT JOIN grupy g ON n.grupa_id = g.id WHERE pl.id = ?";
$stmt = $polaczenie->prepare($sql_lekcja);
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$lekcja = $stmt->get_result()->fetch_assoc();
if (!$lekcja) { die("Nie znaleziono lekcji w planie."); }
$nr_lekcji = $lekcja['nr_lekcji'];
$oryginalne_nauczanie_id = $lekcja['nauczanie_id'];

// Sprawdzamy, czy dla tej lekcji w tym dniu istnieje już wpis w tabeli zastepstwa
$zastepstwo_istniejace = $polaczenie->query("SELECT * FROM zastepstwa WHERE oryginalne_nauczanie_id = $oryginalne_nauczanie_id AND data = '$data'")->fetch_assoc();

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akcja = $_POST['akcja'];
    
    // NAJPIERW USUŃ STARY WPIS, JEŚLI ISTNIEJE
    if ($zastepstwo_istniejace) {
        $polaczenie->query("DELETE FROM zastepstwa WHERE id = " . $zastepstwo_istniejace['id']);
    }

    if ($akcja === 'zastepstwo') {
        $nowe_nauczanie_id = $_POST['nowe_nauczanie_id'];
        $nowa_sala = $_POST['nowa_sala'];
        $typ = 'zastepstwo';
        $stmt_insert = $polaczenie->prepare("INSERT INTO zastepstwa (rok_szkolny_id, data, nr_lekcji, oryginalne_nauczanie_id, nowe_nauczanie_id, nowa_sala, typ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isiisss", $aktywny_rok_id, $data, $nr_lekcji, $oryginalne_nauczanie_id, $nowe_nauczanie_id, $nowa_sala, $typ);
        $stmt_insert->execute();
    } elseif ($akcja === 'odwolaj') {
        $typ = 'odwolane';
        $stmt_insert = $polaczenie->prepare("INSERT INTO zastepstwa (rok_szkolny_id, data, nr_lekcji, oryginalne_nauczanie_id, typ) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isiis", $aktywny_rok_id, $data, $nr_lekcji, $oryginalne_nauczanie_id, $typ);
        $stmt_insert->execute();
    } // Jeśli akcja to 'usun_wpis', stary wpis został już usunięty
    
    header('Location: zarzadzaj_zastepstwami.php?data=' . $data);
    exit();
}

// Pobieranie dostępnych nauczycieli
$sql_dostepni = "SELECT u.id, CONCAT(u.imie, ' ', u.nazwisko) as nauczyciel FROM uzytkownicy u WHERE u.rola IN ('nauczyciel', 'dyrektor', 'admin') AND u.aktywny = 1 AND u.id NOT IN (SELECT n.nauczyciel_id FROM plan_lekcji pl JOIN nauczanie n ON pl.nauczanie_id = n.id WHERE pl.dzien_tygodnia = ? AND pl.nr_lekcji = ? AND pl.rok_szkolny_id = ?) ORDER BY u.nazwisko";
$stmt_dostepni = $polaczenie->prepare($sql_dostepni);
$stmt_dostepni->bind_param("iii", $dzien_tygodnia, $nr_lekcji, $aktywny_rok_id);
$stmt_dostepni->execute();
$dostepni_nauczyciele = $stmt_dostepni->get_result()->fetch_all(MYSQLI_ASSOC);

// Pobieramy wszystkie przydziały do listy wyboru
$wszystkie_przydzialy = $polaczenie->query("SELECT n.id, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) as nauczyciel FROM nauczanie n JOIN przedmioty p ON n.przedmiot_id=p.id JOIN uzytkownicy u ON n.nauczyciel_id=u.id WHERE n.rok_szkolny_id = $aktywny_rok_id ORDER BY p.nazwa_przedmiotu")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Akcja: Zastępstwo</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek"></header>
    <main id="glowny">
        <div class="karta">
            <h1>Zarządzaj lekcją</h1>
            <p><strong>Przedmiot:</strong> <?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></p>
            <p><strong>Klasa/Grupa:</strong> <?php echo htmlspecialchars($lekcja['jednostka']); ?></p>
            <p><strong>Data:</strong> <?php echo $data; ?>, <strong>Lekcja nr:</strong> <?php echo $nr_lekcji; ?></p>
            <hr>
            
            <?php if($zastepstwo_istniejace): ?>
                <div style="background: #e8f6ef; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0;">Obecnie zdefiniowano akcję dla tej lekcji.</h4>
                    <p>Możesz ją zmienić poniżej lub całkowicie usunąć.</p>
                    <form action="" method="post" style="padding: 0;">
                        <input type="hidden" name="data" value="<?php echo $data; ?>">
                        <button type="submit" name="akcja" value="usun_wpis" class="przycisk przycisk-usun">Usuń istniejący wpis i przywróć lekcję</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <form action="" method="post" style="padding: 0;">
                <input type="hidden" name="oryginalne_nauczanie_id" value="<?php echo $oryginalne_nauczanie_id; ?>">
                <input type="hidden" name="data" value="<?php echo $data; ?>">
                
                <h4>1. Przydziel zastępstwo</h4>
                <select name="nowe_nauczanie_id">
                    <option value="<?php echo $lekcja['nauczanie_id']; ?>">Ten sam przedmiot</option>
                    <optgroup label="Dostępni nauczyciele (inny przedmiot)">
                        <?php foreach($wszystkie_przydzialy as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php if($zastepstwo_istniejace && $zastepstwo_istniejace['nowe_nauczanie_id'] == $p['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($p['nazwa_przedmiotu'] . ' - ' . $p['nauczyciel']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <input type="text" name="nowa_sala" placeholder="Nowa sala (opcjonalnie)" value="<?php echo htmlspecialchars($zastepstwo_istniejace['nowa_sala'] ?? ''); ?>">
                <button type="submit" name="akcja" value="zastepstwo" class="przycisk">Zapisz zastępstwo</button>
            </form>
            <hr style="margin: 20px 0;">

            <form action="" method="post" style="padding: 0;">
                <input type="hidden" name="oryginalne_nauczanie_id" value="<?php echo $oryginalne_nauczanie_id; ?>">
                <input type="hidden" name="data" value="<?php echo $data; ?>">
                <h4>2. Odwołaj zajęcia</h4>
                <button type="submit" name="akcja" value="odwolaj" class="przycisk przycisk-usun" onclick="return confirm('Czy na pewno odwołać te zajęcia?');">Odwołaj lekcję</button>
            </form>
            <hr style="margin: 20px 0;">

            <h4>3. Przesuń inne zajęcia w to miejsce</h4>
            <p>Aby przesunąć zajęcia, najpierw **odwołaj** obecną lekcję. Następnie przejdź do panelu "Zarządzaj Planem Lekcji" i dodaj nową lekcję w tym wolnym terminie.</p>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>