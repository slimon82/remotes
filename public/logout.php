<?php

require __DIR__ . '/../src/AdminAuth.php';

use App\AdminAuth;

AdminAuth::logout();
header('Location: login.php');
exit;
