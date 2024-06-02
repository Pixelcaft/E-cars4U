<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reisbureau Formulier</title>
</head>
<body>
    <h2>Reisbureau Formulier</h2>
    <form action="submitForm.php" method="post">
        <label for="naam">Naam:</label><br>
        <input type="text" id="naam" name="naam" required><br>
        <label for="achternaam">Achternaam:</label><br>
        <input type="text" id="achternaam" name="achternaam" required><br>
        <label for="prijs">Prijs:</label><br>
        <input type="number" id="prijs" name="prijs" required><br>
        <label for="zitplaatsen">Zitplaatsen:</label><br>
        <input type="number" id="zitplaatsen" name="zitplaatsen" required><br>
        <label for="type">Type:</label><br>
        <input type="text" id="type" name="type" required><br><br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>
