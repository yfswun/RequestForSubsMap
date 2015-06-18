<?php

class GetGeocode {

   public function geocode() {

      require_once 'GeocodeAddresses.php';

      $success = false;
      
      $commonCriteria = Constants::getCommonCriteria();

      // ***************************************************************
      // Connect to MySQL database
      // ***************************************************************

      $conn = new mysqli(DBInfo::HOST, DBInfo::USERNAME, DBInfo::PASSWORD, DBInfo::DATABASE);

      if ($conn->connect_errno) {
         die('Failed to connect to the database: (' . $conn->connect_errno . ') ' . $conn->connect_error . "\n");
      }


      // ***************************************************************
      // Update ads table with previous geocoding results (same address)
      // ***************************************************************

      $process = 'Update ads table with previous geocoding results';

      $sql = ""
      . " update     akj9c_adsmanager_ads A"
      . " inner join akj9c_adsmanager_locations L"
      . " on         A.ad_location = L.ad_location"
      . " set        A.loc_id = L.loc_id"
      . " where"
      . $commonCriteria
      . "       and  A.loc_id is null"
      . "       and  L.latitude is not null"
      . "       and  L.longitude is not null"
      ;

      $qry = $conn->prepare($sql);
      if (!$qry) {
         die("Prepare failed: $process :\n($conn->errno) $conn->error");
      } elseif (!$qry->execute()) {
         die("Execute failed: $process :\n($qry->errno) $qry->error");
      }
      $qry->close();


      // ****************************************
      // Insert new data for geocoding
      // ****************************************

      $process = 'Insert new data for geocoding';

      $sql = ""
      . "insert into akj9c_adsmanager_locations"
      . " (ad_location)"
      . " select A.ad_location"
      . " from   akj9c_adsmanager_ads A"
      . " where"
      . $commonCriteria
      . "   and  A.loc_id is null"
      . "   and  A.ad_location not in"
      . "           (select ad_location"
      . "            from   akj9c_adsmanager_locations)"
      . " group by A.ad_location"
      ;

      $qry = $conn->prepare($sql);
      if (!$qry) {
         die("Prepare failed: $process :\n($conn->errno) $conn->error");
      } elseif (!$qry->execute()) {
         die("Execute failed: $process :\n($qry->errno) $qry->error");
      }
      $qry->close();


      // ****************************************
      // Select new locations for geocoding
      // ****************************************

      // This will select old locations which had not been geocoded before. OK to retry.

      $process = 'Select new locations for geocoding';

      $sql = ""
      . "select  ad_location"
      . "  from  akj9c_adsmanager_locations"
      . " where  latitude is null"
      . "   and  longitude is null"
      . " group by ad_location"
      . " order by ad_location;"
      ;

      $qry = $conn->prepare($sql);
      if (!$qry) {
         die("Prepare failed: $process :\n($conn->errno) $conn->error");
      } elseif (!$qry->execute()) {
         die("Execute failed: $process :\n($qry->errno) $qry->error");
      } else {
         $result = $qry->get_result();
         if (!$result) {
            die("Get result set failed: $process :\n($qry->errno) $qry->error");
         }
         if ($result->num_rows > 0) {
            $addresses = [];
            while ($row = $result->fetch_assoc()) {
               $addresses[] = $row['ad_location'];
            }
         }
         $result->close();
         $qry->close();
      }


      // ****************************************
      // Call geocode function
      // ****************************************

      $geocodeResults = GeocodeAddresses::geocodeAdds($addresses);

      if (!$geocodeResults) {
         die('Geocoding failed');
      }


      // ****************************************
      // Update locations table
      // ****************************************

      $process = 'Update locations table';

      if ($geocodeResults) {

         $updatedLocRows = 0;
         
         $sql = ""
         . "update akj9c_adsmanager_locations"
         . "   set latitude = (?)"
         . "     , longitude = (?)"
         . " where ad_location = (?)"
         ;

         $qry = $conn->prepare($sql);
         
         if (!$qry) {
            die("Prepare failed: $process :\n($conn->errno) $conn->error");
         } else {
            foreach ($geocodeResults as $geoadd) {
               if ($geoadd['success'] == 1) {
                  $binded = $qry->bind_param(  'dds'
                                             , $geoadd['coords']['lat']
                                             , $geoadd['coords']['lng']
                                             , $geoadd['address']);
                  if (!$binded) {
                     die("Bind param failed: $process :\n($qry->errno) $qry->error");
                  } else {
                     if (!$qry->execute()) {
                        die("Execute failed: $process :\n($qry->errno) $qry->error");
                     } else {
                        $updatedLocRows += $conn->affected_rows;
                     }
                  }
               }
            }
         }
         $qry->close();
      }


      // ****************************************
      // Update ads table
      // ****************************************

      $process = 'Update ads table';

      if ($geocodeResults && $updatedLocRows > 0) {

         $sql = ""
         . " update     akj9c_adsmanager_ads A"
         . " inner join akj9c_adsmanager_locations L"
         . " on         A.ad_location = L.ad_location"
         . " set        A.loc_id = L.loc_id"
         . " where"
         . $commonCriteria
         . "        and L.latitude is not null"
         . "        and L.longitude is not null"
         ;

         $qry = $conn->prepare($sql);
         if (!$qry) {
            die("Prepare failed: $process :\n($conn->errno) $conn->error");
         } elseif (!$qry->execute()) {
            die("Execute failed: $process :\n($qry->errno) $qry->error");
         }
         $qry->close();

      }

      $conn->close();

      $success = true;
      return $success;
   }
}
?>