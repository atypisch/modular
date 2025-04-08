<?php
session_start();
session_destroy();
header('Location: /modular/admin/auth/login.php');
exit; 