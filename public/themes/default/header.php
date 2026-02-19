<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>12 Column Flex Layout</title>

  <!-- CSS -->
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/menu.css">
  <link rel="stylesheet" href="css/responsive.css">

  <!-- JS Library -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/custom.js" defer></script>
</head>
<body>

<header class="site-header">
  <div class="container">
    <div class="logo">LOGO</div>
    <nav class="main-nav">
      <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">About</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
    </nav>
    <button class="mobile-nav-toggle" aria-label="Toggle menu">☰</button>
  </div>
</header>

<nav class="mobile-nav">
  <ul>
    <li><a href="#">Home</a></li>
    <li><a href="#">About</a></li>
    <li><a href="#">Contact</a></li>
  </ul>
</nav>