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
    header('Content-Type: application/x-www-form-urlencoded');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($response);
    error_log("Method not allowed: " . $method);  // Log the error
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
    error_log("Unsupported Media Type: " . $_SERVER["CONTENT_TYPE"]);  // Log the error
    exit;
}

// Start the session
session_start();

// Include the database connection file
include 'dbconnection.php';

// Get the email and password from the POST request and sanitize them
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$wachtwoord = filter_input(INPUT_POST, 'wachtwoord', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Check if the email and password fields are filled
if (empty($email) || empty($wachtwoord)) {
    // If not, redirect to the login page with an error message
    header('Location: ../login.php?error=empty_fields');
    error_log("Empty fields: email or password");  // Log the error
    exit;
}

// Check the database connection
if ($conn->connect_error) {
    // If there's a connection error, redirect to the login page with an error message
    header('Location: ../login.php?error=connection_failed');
    error_log("Database connection failed: " . $conn->connect_error);  // Log the error
    exit;
}

// Prepare the SQL statement to select the user with the given email
$stmt = $conn->prepare("SELECT id, wachtwoord FROM credentials WHERE email = ?");

if (!$stmt) {
    // If the statement preparation failed, redirect to the login page with an error message
    header('Location: ../login.php?error=prepare_failed');
    error_log("Statement preparation failed: " . $conn->error);  // Log the error
    exit;
}

// Bind the email parameter to the SQL statement and execute it
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // If a user with the given email is found, bind the result to variables
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    // Verify the password
    if (password_verify($wachtwoord, $hashed_password)) {
        // If the password is correct, store the user id in the session
        $_SESSION['id'] = $id;

        // Regenerate the session id to prevent session hijacking
        session_regenerate_id(true);
        
        // Redirect to the home page with a success message
        header('Location: ../index.php?success=login_complete');
        error_log("User logged in: " . $email);  // Log the successful login
        exit;
    } else {
        // If the password is incorrect, redirect to the login page with an error message
        header('Location: ../login.php?error=invalid_password');
        error_log("Invalid password: " . $email);  // Log the error
        exit;
    }
} else {
    // If no user with the given email is found, redirect to the login page with an error message
    header('Location: ../login.php?error=user_not_found');
    error_log("User not found: " . $email);  // Log the error
    exit;
}

// Close the statement and the database connection
$stmt->close();
$conn->close();
?>