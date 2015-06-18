<?php

require_once 'Autoloader.php';
$autoloader = new Autoloader();
$autoloader->autoloader();

// geocoding and data update
require_once 'GetGeocode.php';
$getGeocode = new GetGeocode();
if (!($getGeocode->geocode())) {
   die("Geocoding and data update failed\n");
}

// Generate the map page
require_once 'MakeMap.php';
$makeMap = new MakeMap();
if (!($makeMap->makeGoogleMap())) {
   die("Map page generation failed\n");
}

?>
