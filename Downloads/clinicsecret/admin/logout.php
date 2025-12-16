<?php
require_once __DIR__ . '/../config.php';
unset($_SESSION['admin_id']);
header('Location: /clinicsecret/admin/index.php');
exit;
