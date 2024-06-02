<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    
<div class="register-container">

    <form class="register-form-container" id="registerForm" action="database/register-logic.php" method="POST">
        <label for="voornaam">voornaam</label>
        <input type="text" id="voornaam" name="voornaam" required>
        
        <label for="tussenvoegsel">Infix</label>
        <input type="text" id="tussenvoegsel" name="tussenvoegsel">
        
        <label for="achternaam">achternaam</label>
        <input type="text" id="achternaam" name="achternaam" required>
        
        <label for="email">email</label>
        <input type="email" id="email" name="email" required>
        
        <label for="wachtwoord">wachtwoord</label>
        <input type="wachtwoord" id="wachtwoord" name="wachtwoord" required>
        
        <label for="re-wachtwoord">re-wachtwoord</label>
        <input type="wachtwoord" id="re-wachtwoord" name="rewachtwoord" required>
        <br>
        <br>
        
        <button type="submit">register</button>
    </form>
    
</div>

</body>
</html>
