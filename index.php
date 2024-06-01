
<?php
session_start(); // Start of hervat de sessie

// Controleer of de gebruiker is ingelogd
if (isset($_SESSION['id'])) {
    echo "<h1>Welkom, " . $_SESSION['id'] . "!</h1>";
    echo '<a href="database/logout-logic.php">Uitloggen</a>'; // Logout link
} else {
    echo "<h1>U bent niet ingelogd. <a href='login.php'>Login</a></h1>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style/style.css">
</head>

<body>
    <center>
        <div class="button-container">

            <button class="anonymous button" onclick="anonymous()">
            <h1>
                rental car
            </h1>
            </button>
            <button class="landlord button" onclick="landLords()">
            <h1>
                landLords
            </h1>    
            </button>
        </div>
    </center>

    <script>
        function anonymous() {
            window.location.href = "dashboard-anonymous.php";
        }
        function landLords() {
            window.location.href = "dashboard-landlords.php";
        }
    </script>
</body>
</html>