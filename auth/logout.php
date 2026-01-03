<?php
session_start();
session_unset();
session_destroy();
header('Location: ../signin.php?success=' . urlencode('You have been logged out.'));
exit;