<?php
include 'dbconnection.php'; // Controleer of dit bestand correct is

// Haal de POST-variabelen op
$firstname = $_POST['firstname'] ?? '';
$infix = $_POST['infix'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$rePassword = $_POST['rePassword'] ?? '';

// Controleer of de wachtwoorden overeenkomen
if ($password !== $rePassword) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../register.php?error=passwords_do_not_match');
    exit;
}

// Controleer of alle velden zijn ingevuld
if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($rePassword)) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../register.php?error=empty_fields');
    exit;
}

// Controleer databaseverbinding
if ($conn->connect_error) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../register.php?error=connection_failed');
    exit;
}

// Bereid de SQL-instructie voor
$stmt = $conn->prepare("INSERT INTO credentials (firstname, infix, lastname, email, password) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../register.php?error=prepare_failed');
    exit;
}

// Hash het wachtwoord
$password_hashed = password_hash($password, PASSWORD_BCRYPT);

// Bind de parameters en voer de instructie uit
$stmt->bind_param("sssss", $firstname, $infix, $lastname, $email, $password_hashed);

if ($stmt->execute()) {
    // Succesvolle registratie, doorverwijzen naar de homepagina
    header('Location: ../index.php?success=registration_complete');
    exit;
} else {
    // Voor foutmeldingen kun je de gebruiker doorverwijzen naar een foutpagina of een bericht weergeven
    header('Location: ../register.php?error=execute_failed');
    exit;
}

// Sluit de statement en de verbinding
$stmt->close();
$conn->close();
?>
