<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/connect.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: /clinicsecret/admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Clinic Secret Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/clinicsecret/admin/dashboard.php">Admin - Clinic Secret</a>
    <div class="d-flex">
      <a href="/clinicsecret/admin/users.php" class="nav-link text-white">Users</a>
      <a href="/clinicsecret/admin/products.php" class="nav-link text-white ms-3">Products</a>
      <a href="/clinicsecret/admin/payouts.php" class="nav-link text-white ms-3">Payouts</a>
      <a href="/clinicsecret/admin/payout_add.php" class="nav-link text-white ms-3">Add Payout</a>
      <a href="/clinicsecret/admin/logout.php" class="nav-link text-white ms-3">Logout</a>
    </div>
  </div>
</nav>
<div class="container my-4">
