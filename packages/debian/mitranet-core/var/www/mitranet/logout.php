<?php
require_once(__DIR__ . '/includes/api.inc');
MitraNetApi::logout();
header('Location: /login.php');
exit;
