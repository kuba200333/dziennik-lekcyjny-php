<?php
// Krok 1: Zawsze rozpoczynaj sesję, aby uzyskać do niej dostęp.
session_start();

// Krok 2: Usuń wszystkie zmienne przechowywane w sesji.
// Przypisanie pustej tablicy to najpewniejszy sposób na wyczyszczenie wszystkiego.
$_SESSION = array();

// Krok 3: Zniszcz sesję na serwerze.
// To usuwa plik sesji po stronie serwera.
session_destroy();

// Krok 4: Przekieruj użytkownika na stronę logowania.
header('Location: index.php');

// Krok 5: Zakończ wykonywanie skryptu, aby upewnić się,
// że nic więcej się nie wykona po przekierowaniu.
exit();
?>