<?php
require_once '../config/session.php';
unset($_SESSION['view_as']);
header('Location: users.php');
exit;
