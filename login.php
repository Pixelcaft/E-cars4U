<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    
<div class="login-container">

    <form class="login-form-container" id="loginform" action="database/login-logic.php" method="POST">
        <label for="email">email</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">wachtwoord</label>
        <input type="password" id="wachtwoord" name="wachtwoord" required>
        <br><br>
        <button type="submit">Login</button>
        <br>
        <br>
        <br>
        <a href="register.php">registeren</a>

    </form>    
</div>

</body>
</html>
