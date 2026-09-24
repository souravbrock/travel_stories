<?php
// Front controller — served as document root of tstory.reddevils.co.in
$cfgFile = file_exists(__DIR__.'/config/config.php') ? __DIR__.'/config/config.php'
 : (file_exists(__DIR__.'/../config/config.php') ? __DIR__.'/../config/config.php'
 : (file_exists(__DIR__.'/../../config/config.php') ? __DIR__.'/../../config/config.php' : null));
$cfg = $cfgFile ? require $cfgFile : ['app_name'=>'Travel Stories'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Travel Stories — Discover India</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b3d62">
</head>
<body>
<header class="top"><a class="brand" href="#/">🧭 Travel Stories</a>
<nav><a href="#/map">Map</a><a href="#/packages">Packages</a><a href="#/builder">Trip Builder</a><a href="#/vendor">Vendor</a><a href="#/admin">Admin</a><span id="who"></span><button id="logout" hidden>Logout</button></nav></header>
<main id="app"></main>
<footer>Travel Stories · tstory.reddevils.co.in · <span id="yr"></span></footer>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/assets/js/app.js"></script>
</body></html>
