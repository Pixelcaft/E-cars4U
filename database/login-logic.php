<?php
session_start(); // Start de sessie
include 'dbconnection.php'; // Controleer of dit bestand correct is

// Haal de POST-variabelen op
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Controleer of de velden zijn ingevuld
if (empty($email) || empty($password)) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../login.php?error=empty_fields');
    exit;
}

// Controleer databaseverbinding
if ($conn->connect_error) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../login.php?error=connection_failed');
    exit;
}

// Bereid de SQL-instructie voor
$stmt = $conn->prepare("SELECT id, password FROM credentials WHERE email = ?");

if (!$stmt) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../login.php?error=prepare_failed');
    exit;
}

// Bind de parameters en voer de instructie uit
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $hashed_password); // Bind the 'id' column as well
    $stmt->fetch();

    // Controleer het wachtwoord
    if (password_verify($password, $hashed_password)) {
        // Sla de gebruikersgegevens op in de sessie
        $_SESSION['id'] = $id;

        // Regenereren sessie-id om sessiehijacking te voorkomen
        session_regenerate_id(true);
        
        // Succesvolle login, doorverwijzen naar de homepagina
        header('Location: ../index.php?success=login_complete');
        exit;
    } else {
        // Ongeldig wachtwoord
        header('Location: ../login.php?error=invalid_password');
        exit;
    }
} else {
    // Geen gebruiker gevonden met dit e-mailadres
    header('Location: ../login.php?error=user_not_found');
    exit;
}

// Sluit de statement en de verbinding
$stmt->close();
$conn->close();
?>
