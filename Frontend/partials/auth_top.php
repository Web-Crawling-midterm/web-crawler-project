<?php
/** Phần đầu dùng chung cho các trang xác thực. Biến cần có: $pageTitle, $heading, $subtitle, $icon */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> | CrawlWise</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">
  <main class="auth-card">
    <div class="auth-logo"><i class="bi <?= htmlspecialchars($icon ?? 'bi-diagram-3-fill', ENT_QUOTES) ?>"></i></div>
    <h1><?= htmlspecialchars($heading, ENT_QUOTES) ?></h1>
    <p class="auth-sub"><?= htmlspecialchars($subtitle, ENT_QUOTES) ?></p>
