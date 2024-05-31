<?php

include 'dbconnection.php';

// Controleer de databaseverbinding
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API-sleutel voor authenticatie (vervang dit met je eigen sleutelmechanisme)
$valid_api_key = 'your_api_key';

// Controleer of de ontvangen API-sleutel geldig is
function is_valid_api_key($api_key) {
    global $valid_api_key;
    return ($api_key === $valid_api_key);
}

// Voer een CRUD-bewerking uit op basis van de ontvangen API-aanroep
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toevoegen van een nieuwe auto
    if (isset($_POST['action']) && $_POST['action'] === 'add_car' && is_valid_api_key($_POST['api_key'])) {
        // Gegevens van het formulier ophalen
        $auto_naam = $_POST["auto_naam"];
        $type = $_POST["type"];
        $zitplaatsen = $_POST["zitplaatsen"];
        $prijs = $_POST["prijs"];
        $verhuurder_email = $_POST["verhuurder_email"]; // Haal de verhuurder e-mail op

        // SQL-query voor het toevoegen van een auto
        $sql = "INSERT INTO cars (carname, cartype, placestosit, price, gehuurd, verhuurde) VALUES ('$auto_naam', '$type', '$zitplaatsen', '$prijs', '0', '$verhuurder_email')";

        // Voer de SQL-query uit
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Auto succesvol toegevoegd']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>
