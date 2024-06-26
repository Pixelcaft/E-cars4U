<?php
ini_set('display_errors', '0');     // Don't display errors
error_reporting(E_ALL | E_STRICT);  // Report all errors

// Define a whitelist of allowed HTTP methods
$allowed_methods = array('POST');

// Get the HTTP method from the server
$method = $_SERVER['REQUEST_METHOD'];

// Check if the method is in the whitelist
if (!in_array($method, $allowed_methods)) {
    // If the method is not in the whitelist, send a 405 Method Not Allowed response
    http_response_code(405);
    $response = array("message" => "Method not allowed.");
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($response);
    error_log("Method not allowed: " . $method); // Audit log
    exit;
}

// Check if the Content-Type is application/json
if ($_SERVER["CONTENT_TYPE"] != 'application/x-www-form-urlencoded') {
    // If the Content-Type is not application/x-www-form-urlencoded, send a 415 Unsupported Media Type response
    http_response_code(415);
    $response = array("message" => "Unsupported Media Type.");
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($response);
    error_log("Unsupported Media Type: " . $_SERVER["CONTENT_TYPE"]); // Audit log
    exit;
}

// Include the database connection file
include 'dbconnection.php';

// Get the POST variables and sanitize them
$voornaam = filter_input(INPUT_POST, 'voornaam', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$tussenvoegsel = filter_input(INPUT_POST, 'tussenvoegsel', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$achternaam = filter_input(INPUT_POST, 'achternaam', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$wachtwoord = filter_input(INPUT_POST, 'wachtwoord', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$rewachtwoord = filter_input(INPUT_POST, 'rewachtwoord', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Check if the email is valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // If the email is not valid, redirect to the registration page with an error message
    header('Location: ../register.php?error=invalid_email');
    error_log("Invalid email: " . $email); // Audit log
    exit;
}

// Check if the passwords match
if ($wachtwoord !== $rewachtwoord) {
    // If the passwords do not match, redirect to the registration page with an error message
    header('Location: ../register.php?error=passwords_do_not_match');
    error_log("Passwords do not match for email: " . $email); // Audit log
    exit;
}

// Check if all fields are filled
if (empty($voornaam) || empty($achternaam) || empty($email) || empty($wachtwoord) || empty($rewachtwoord)) {
    // If not, redirect to the registration page with an error message
    header('Location: ../register.php?error=empty_fields');
    error_log("Empty fields for email: " . $email); // Audit log
    exit;
}

// Check the database connection
if ($conn->connect_error) {
    // If there's a connection error, redirect to the registration page with an error message
    header('Location: ../register.php?error=connection_failed');
    error_log("Database connection failed: " . $conn->connect_error); // Audit log
    exit;
}

// Prepare the SQL statement to check if the email is already in use
$stmt = $conn->prepare("SELECT * FROM credentials WHERE email = ?");

if (!$stmt) {
    // If the statement preparation failed, redirect to the registration page with an error message
    header('Location: ../register.php?error=prepare_failed');
    error_log("Statement preparation failed for email: " . $email); // Audit log
    exit;
}

// Bind the email parameter to the SQL statement and execute it
$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    // If the execution failed, redirect to the registration page with an error message
    header('Location: ../register.php?error=execute_failed');
    error_log("Statement execution failed for email: " . $email); // Audit log
    exit;
}

// Get the result of the query
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If the email is already in use, redirect to the registration page with an error message
    header('Location: ../register.php?error=email_in_use');
    error_log("Email already in use: " . $email); // Audit log
    exit;
}

// Close the statement
$stmt->close();

// Prepare the SQL statement to insert the new user
$stmt = $conn->prepare("INSERT INTO credentials (voornaam, tussenvoegsel, achternaam, email, wachtwoord) VALUES (?, ?, ?, ?, ?)");

// Hash the password
$password_hashed = password_hash($wachtwoord, PASSWORD_BCRYPT);

// Bind the parameters to the SQL statement and execute it
$stmt->bind_param("sssss", $voornaam, $tussenvoegsel, $achternaam, $email, $password_hashed);

if ($stmt->execute()) {
    // If the registration is successful, redirect to the home page with a success message
    header('Location: ../index.php?success=registration_complete');
    error_log("Registration successful for email: " . $email); // Audit log
    exit;
} else {
    // If the execution failed, redirect to the registration page with an error message
    header('Location: ../register.php?error=execute_failed');
    error_log("Registration failed for email: " . $email); // Audit log
    exit;
}

// Close the statement and the database connection
$stmt->close();
$conn->close();
?>