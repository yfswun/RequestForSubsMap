<?php

class GeocodeAddresses {

   public static function geocodeAdds($addArr) {

      $MAP_OBJECT = new GoogleMapAPI();
      $MAP_OBJECT->_minify_js = isset($_REQUEST["min"])?FALSE:TRUE;

      $results = [];
      
      foreach ($addArr as $add) {
         $geocodes_full = $MAP_OBJECT->geoGetCoordsFull($add);
         $addResult = [];
         $addResult['address'] = $add;
         if (   $geocodes_full->status == 'OK'
             && $geocodes_full->results[0]->types[0] == 'street_address'
             && $geocodes_full->results[0]->geometry->location_type == 'ROOFTOP') {
            $addResult['success'] = 1;
            $addResult['coords']['lat'] = $geocodes_full->results[0]->geometry->location->lat;
            $addResult['coords']['lng'] = $geocodes_full->results[0]->geometry->location->lng;
         } else {
            $addResult['success'] = 0;
            $addResult['coords']['lat'] = null;
            $addResult['coords']['lng'] = null;
         }
         $results[] = $addResult;
      }

      return $results;
   }
}
?>
