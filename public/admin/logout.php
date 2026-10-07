<?php
session_start();
session_destroy();
// Automatic logout from the idle timer — let the login page say why
if (isset($_GET['timeout'])) {
    session_start();
    session_regenerate_id(true);
    $_SESSION['timed_out'] = true;
}
header('Location: index.php');
exit;
