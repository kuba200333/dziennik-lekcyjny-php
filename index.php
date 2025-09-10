<?php session_start(); // Start sesji, aby wyświetlić błędy ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie do Dziennika</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="logowanie">
        <h1>Logowanie do Dziennika</h1>
        <form action="logowanie.php" method="post">
            Login: <br>
            <input type="text" name="login" required> <br>
            Hasło: <br> 
            <input type="password" name="haslo" required> <br><br>
            <input type="submit" value="Zaloguj się">
        </form>

        <?php
            // Wyświetlanie komunikatu o błędzie, jeśli istnieje
            if (isset($_SESSION['blad'])) {
                echo '<p style="color:red;">' . $_SESSION['blad'] . '</p>';
                unset($_SESSION['blad']); // Usuwamy błąd po wyświetleniu
            }
        ?>
    </div>
</body>
</html>