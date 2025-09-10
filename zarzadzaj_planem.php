<?php
session_start();
if (!isset($_SESSION['zalogowany']) || !in_array($_SESSION['rola'], ['admin', 'dyrektor'])) {
    header('Location: dziennik.php');
    exit();
}

require_once 'connect.php';
$polaczenie = new mysqli($host, $db_user, $db_password, $db_name);
$aktywny_rok_id = $_SESSION['aktywny_rok_id'];

// Pobieramy klasy i grupy do listy wyboru
$jednostki = [];
$klasy = $polaczenie->query("SELECT id, nazwa_klasy FROM klasy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_klasy")->fetch_all(MYSQLI_ASSOC);
foreach ($klasy as $klasa) $jednostki['klasa-' . $klasa['id']] = $klasa['nazwa_klasy'];
$grupy = $polaczenie->query("SELECT id, nazwa_grupy FROM grupy WHERE rok_szkolny_id = $aktywny_rok_id ORDER BY nazwa_grupy")->fetch_all(MYSQLI_ASSOC);
foreach ($grupy as $grupa) $jednostki['grupa-' . $grupa['id']] = 'Grupa: ' . $grupa['nazwa_grupy'];

$wybrana_jednostka = $_GET['jednostka'] ?? null;
$plan_lekcji = [];

if ($wybrana_jednostka) {
    list($typ, $id) = explode('-', $wybrana_jednostka);
    $warunek_sql = ($typ === 'klasa') ? "n.klasa_id = ?" : "n.grupa_id = ?";

    $sql = "SELECT pl.id, pl.dzien_tygodnia, pl.nr_lekcji, p.nazwa_przedmiotu, CONCAT(u.imie, ' ', u.nazwisko) AS nauczyciel, pl.sala, pl.data_od, pl.data_do
            FROM plan_lekcji pl
            JOIN nauczanie n ON pl.nauczanie_id = n.id
            JOIN przedmioty p ON n.przedmiot_id = p.id
            JOIN uzytkownicy u ON n.nauczyciel_id = u.id
            WHERE pl.rok_szkolny_id = ? AND $warunek_sql
            ORDER BY pl.nr_lekcji, pl.dzien_tygodnia";
    $stmt = $polaczenie->prepare($sql);
    $stmt->bind_param("ii", $aktywny_rok_id, $id);
    $stmt->execute();
    $wynik = $stmt->get_result();
    while($lekcja = $wynik->fetch_assoc()) {
        $plan_lekcji[$lekcja['dzien_tygodnia']][$lekcja['nr_lekcji']] = $lekcja;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><title>Zarządzanie Planem Lekcji</title><link rel="stylesheet" href="style.css">
<style>
    /* Kontener na przyciski Edytuj/Usuń */
    .plan-akcje {
        display: flex;
        justify-content: space-evenly; /* Równe odstępy */
        align-items: center;           /* Wyrównanie w pionie */
        margin-top: 8px;
    }

    /* Reset marginesu dla formularza, aby nie psuł układu */
    .plan-akcje form {
        margin: 0;
    }

    /* KLUCZOWA ZMIANA: Bardziej precyzyjny styl dla linku i przycisku */
    .plan-akcje a.przycisk,
    .plan-akcje button.przycisk {
        /* Reset domyślnego wyglądu */
        font-family: inherit;
        cursor: pointer;
        text-decoration: none;

        /* Jawne ustawienie tego samego paddingu dla obu elementów */
        padding: 2px 5px;

        /* Wygląd tekstu */
        font-size: 0.8rem;
        color: white;
        opacity: 0.8;
    }
</style>
</head>
<body>
<div id="kontener">
    <?php require_once 'szablony/menu.php'; ?>
    <header id="naglowek">
        </header>
    <main id="glowny">
        <div class="karta">
            <h1>Zarządzanie Planem Lekcji</h1>
            <form action="" method="get">
                <label for="jednostka">Wybierz klasę lub grupę, aby edytować jej plan:</label>
                <select name="jednostka" id="jednostka" onchange="this.form.submit()">
                    <option value="">-- Wybierz --</option>
                    <?php foreach ($jednostki as $key => $nazwa): ?>
                        <option value="<?php echo $key; ?>" <?php if($wybrana_jednostka == $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($nazwa); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($wybrana_jednostka): ?>
            <hr style="margin: 20px 0;">
            <div style="overflow-x: auto;">
            <table>
                <thead><tr><th>Lekcja</th><th>Poniedziałek</th><th>Wtorek</th><th>Środa</th><th>Czwartek</th><th>Piątek</th></tr></thead>
                <tbody>
                    <?php for ($nr_lekcji = 1; $nr_lekcji <= 8; $nr_lekcji++): ?>
                    <tr>
                        <td><b><?php echo $nr_lekcji; ?></b></td>
                        <?php for ($dzien = 1; $dzien <= 5; $dzien++): ?>
                            <td class="plan-komorka">
                                <?php if (isset($plan_lekcji[$dzien][$nr_lekcji])): 
                                    $lekcja = $plan_lekcji[$dzien][$nr_lekcji];
                                ?>
                                    <b><?php echo htmlspecialchars($lekcja['nazwa_przedmiotu']); ?></b><br>
                                    <small><?php echo htmlspecialchars($lekcja['nauczyciel']); ?></small><br>
                                    <small>s. <?php echo htmlspecialchars($lekcja['sala']); ?></small><br>
                                    <small style="color: #7f8c8d; font-size: 0.75rem;"><?php echo htmlspecialchars($lekcja['data_od'] . ' - ' . $lekcja['data_do']); ?></small>
                                    <div class="plan-akcje">
                                        <a href="edytuj_lekcje.php?id=<?php echo $lekcja['id']; ?>" class="przycisk">Edytuj</a>
                                        <form action="usun_lekcje.php" method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć tę lekcję z planu?');">
                                            <input type="hidden" name="id_lekcji" value="<?php echo $lekcja['id']; ?>">
                                            <input type="hidden" name="redirect_jednostka" value="<?php echo $wybrana_jednostka; ?>">
                                            <button type="submit" class="przycisk przycisk-usun">Usuń</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <a href="dodaj_lekcje.php?jednostka=<?php echo $wybrana_jednostka; ?>&dzien=<?php echo $dzien; ?>&nr=<?php echo $nr_lekcji; ?>" class="przycisk">+ Dodaj</a>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <footer id="stopka">&copy; <?php echo date('Y'); ?> Nowy Dziennik Lekcyjny</footer>
</div>
</body>
</html>