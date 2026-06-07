<?php
// admin/logout.php - clears the session and sends admin back to login
session_start();
session_destroy();
header("Location: ../login.php");
exit();
?>
