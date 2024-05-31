<?php
session_start(); // Start of hervat de sessie

// Controleer of de sessievariabelen zijn ingesteld
if (isset($_SESSION['email'])) {
    echo "Ingelogd als: " . $_SESSION['email'];
} else {
    echo "Niet ingelogd.";
}

// Debug informatie
echo "<pre>";
print_r($_SESSION); // Print alle sessievariabelen voor debugging
echo "</pre>";
?>
