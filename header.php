<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Clinic Secret Referral Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/clinicsecret/dashboard.php">Clinic Secret Referral Portal</a>
    <div class="d-flex">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="/clinicsecret/dashboard.php" class="nav-link text-white">Dashboard</a>
        <a href="/clinicsecret/logout.php" class="nav-link text-white ms-3">Logout</a>
      <?php else: ?>
        <a href="/clinicsecret/login.php" class="nav-link text-white">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container my-4">
