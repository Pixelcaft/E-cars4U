<?php
// database/dbconnection.php
// servername => localhost
// username => root
//password => empty
//database name => gameon
$conn = mysqli_connect("localhost", "root", "", "e-cars4u");
// check connection
if($conn === false){
    die("ERROR: Could not connect. "
        . mysqli_connect_error());
}
?>