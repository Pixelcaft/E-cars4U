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
        <label for="firstname">firstname</label>
        <input type="text" id="firstname" name="firstname" required>
        
        <label for="infix">Infix</label>
        <input type="text" id="infix" name="infix">
        
        <label for="lastname">lastname</label>
        <input type="text" id="lastname" name="lastname" required>
        
        <label for="email">email</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">password</label>
        <input type="password" id="password" name="password" required>
        
        <label for="re-password">re-password</label>
        <input type="password" id="re-password" name="rePassword" required>
        <br>
        <br>
        
        <button type="submit">register</button>
    </form>
    
</div>

</body>
</html>
