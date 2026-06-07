<?php
// logout.php - clears the session and sends the user back to login
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
