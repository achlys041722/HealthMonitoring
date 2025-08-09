<?php
session_start();
session_destroy();
header('Location: /Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/login.php');
exit(); 