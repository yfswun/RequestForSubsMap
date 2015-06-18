<?php

class Autoloader {
   public function autoloader() {
      require_once 'config/DBInfo.php';
      require_once 'lib/PHPGoogleMapAPIV3/GoogleMapAPIV3.php';
      require_once 'lib/PHPGoogleMapAPIV3/JSMin.php';
      require_once 'lib/HTMLPurifier/library/HTMLPurifier.auto.php';
      require_once 'Constants.php';
   }
}
?>