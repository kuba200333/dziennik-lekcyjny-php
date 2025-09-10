<?php
// Ten plik musi mieć dostęp do sesji, aby znać rolę użytkownika
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprawdzamy rolę, aby uniknąć błędów, jeśli sesja nie istnieje
$rola = $_SESSION['rola'] ?? 'gosc'; // 'gosc' jako wartość domyślna

// Pobieramy nazwę aktualnie otwartego pliku, aby podświetlić aktywny link
$aktywnaStrona = basename($_SERVER['PHP_SELF']);
?>

<nav id="menu">
    <div id="logo-dziennika">
        <?php 
            // Zmieniamy tytuł w menu w zależności od roli
            if ($rola === 'admin' || $rola === 'dyrektor') {
                echo 'ADMIN';
            } else {
                echo 'DZIENNIK';
            }
        ?>
    </div>
    <ul id="menu-nawigacja">
        <li><a href="dziennik.php" class="<?php if ($aktywnaStrona == 'dziennik.php') echo 'aktywny'; ?>">Panel Główny</a></li>
        
        <?php
        // Dynamiczne generowanie reszty menu na podstawie roli
        switch ($rola) {
            case 'admin':
                echo '<li><a href="zarzadzaj_uzytkownikami.php" class="' . ($aktywnaStrona == 'zarzadzaj_uzytkownikami.php' ? 'aktywny' : '') . '">Zarządzaj użytkownikami</a></li>';
                echo '<li><a href="zarzadzaj_klasami.php" class="' . ($aktywnaStrona == 'zarzadzaj_klasami.php' ? 'aktywny' : '') . '">Zarządzaj klasami</a></li>';
                echo '<li><a href="zarzadzaj_przedmiotami.php" class="' . ($aktywnaStrona == 'zarzadzaj_przedmiotami.php' ? 'aktywny' : '') . '">Zarządzaj przedmiotami</a></li>';
                echo '<li><a href="zarzadzaj_kategoriami.php" class="' . ($aktywnaStrona == 'zarzadzaj_kategoriami.php' ? 'aktywny' : '') . '">Zarządzaj kategoriami ocen</a></li>';
                echo '<li><a href="ustawienia_globalne.php" class="' . ($aktywnaStrona == 'ustawienia_globalne.php' ? 'aktywny' : '') . '">Ustawienia Globalne</a></li>';
                echo '<li><a href="zarzadzaj_grupami.php" class="' . ($aktywnaStrona == 'zarzadzaj_grupami.php' ? 'aktywny' : '') . '">Zarządzaj grupami</a></li>';
                echo '<li><a href="podglad_ocen.php" class="' . ($aktywnaStrona == 'podglad_ocen.php' ? 'aktywny' : '') . '">Podgląd Ocen</a></li>';
                echo '<li><a href="uczniowie_masowo.php" class="' . ($aktywnaStrona == 'uczniowie_masowo.php' ? 'aktywny' : '') . '">Dodawanie masowe uczniów</a></li>';
                echo '<li><a href="zarzadzaj_planem.php" class="' . ($aktywnaStrona == 'zarzadzaj_planem.php' ? 'aktywny' : '') . '">Zarządzaj planem lekcji</a></li>';
                echo '<li><a href="zarzadzaj_zastepstwami.php" class="' . ($aktywnaStrona == 'zarzadzaj_zastepstwami.php' ? 'aktywny' : '') . '">Zarządzaj zastępstwami</a></li>';
                echo '<li><a href="plan.php" class="' . ($aktywnaStrona == 'plan.php' ? 'aktywny' : '') . '">Plan lekcji</a></li>';
                echo '<li><a href="moje_nauczanie.php" class="' . ($aktywnaStrona == 'moje_nauczanie.php' ? 'aktywny' : '') . '">Nauczanie</a></li>';
                break;
            case 'dyrektor':
                echo '<li><a href="podglad_ocen.php" class="' . ($aktywnaStrona == 'podglad_ocen.php' ? 'aktywny' : '') . '">Podgląd Ocen</a></li>';
                echo '<li><a href="zarzadzaj_kategoriami.php" class="' . ($aktywnaStrona == 'zarzadzaj_kategoriami.php' ? 'aktywny' : '') . '">Zarządzaj kategoriami ocen</a></li>';
                echo '<li><a href="moje_nauczanie.php" class="' . ($aktywnaStrona == 'moje_nauczanie.php' ? 'aktywny' : '') . '">Nauczanie</a></li>';

                break;
            case 'nauczyciel':
                echo '<li><a href="realizacja_programu.php" class="' . ($aktywnaStrona == 'realizacja_programu.php' ? 'aktywny' : '') . '">Realizacja programu</a></li>';
                echo '<li><a href="moje_nauczanie.php" class="' . ($aktywnaStrona == 'moje_nauczanie.php' ? 'aktywny' : '') . '">Nauczanie</a></li>';
                echo '<li><a href="zarzadzaj_kategoriami.php" class="' . ($aktywnaStrona == 'zarzadzaj_kategoriami.php' ? 'aktywny' : '') . '">Zarządzaj kategoriami ocen</a></li>';
                break;
                echo '<li><a href="dodaj_kategorie.php" class="' . ($aktywnaStrona == 'dodaj_kategorie.php' ? 'aktywny' : '') . '">Dodaj kategorię oceny</a></li>';
                break;
            case 'uczen':

                break;
        }
        ?>
    </ul>
</nav>