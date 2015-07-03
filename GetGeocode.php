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

      $tablePrefix = DBInfo::TABLE_PREFIX;
      
      // ***************************************************************
      // Update ads table with previous geocoding results (same address)
      // ***************************************************************

      $process = 'Update ads table with previous geocoding results';

      $sql = ""
      . " update     {$tablePrefix}_adsmanager_ads A"
      . " inner join {$tablePrefix}_adsmanager_locations L"
      // . " on         A.ad_location = L.ad_location"
      . " on         A.ad_siteaddress = L.ad_siteaddress"
      . "        and A.ad_sitecity = L.ad_sitecity"
      . "        and A.ad_sitestate = L.ad_sitestate"
      . "        and A.ad_sitezip = L.ad_sitezip"
      . " set        A.loc_id = L.loc_id"
      . " where"
      . $commonCriteria
      . "       and  A.loc_id is null"
      . "       and  L.latitude is not null"
      . "       and  L.longitude is not null"
      ;

var_dump($sql);

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
      . "insert into {$tablePrefix}_adsmanager_locations"
      // . " (ad_location)"
      . " (ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip)"
      // . " select A.ad_location"
      . " select A.ad_siteaddress"
      . "      , A.ad_sitecity"
      . "      , A.ad_sitestate"
      . "      , A.ad_sitezip"
      . " from   {$tablePrefix}_adsmanager_ads A"
      . " where"
      . $commonCriteria
      . "   and  A.loc_id is null"
      // . "   and  A.ad_location not in"
      // . "           (select ad_location"
      . "   and  concat_ws(',', A.ad_siteaddress, A.ad_sitecity, A.ad_sitestate, A.ad_sitezip)"
      . "           not in"
      . "           (select concat_ws(',', ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip)"
      . "            from   {$tablePrefix}_adsmanager_locations)"
      // . " group by A.ad_location"
      . " group by A.ad_siteaddress"
      . "        , A.ad_sitecity"
      . "        , A.ad_sitestate"
      . "        , A.ad_sitezip"
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
      // . "select  ad_location"
      . "select  concat_ws(',', ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip) full_add"
      . "  from  {$tablePrefix}_adsmanager_locations"
      . " where  latitude is null"
      . "   and  longitude is null"
      // . " group by ad_location"
      . " group by concat_ws(',', ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip)"
      // . " order by ad_location;"
      . " order by concat_ws(',', ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip)"
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
               // $addresses[] = $row['ad_location'];
               $addresses[] = $row['full_add'];
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
         . "update {$tablePrefix}_adsmanager_locations"
         . "   set latitude = (?)"
         . "     , longitude = (?)"
         // . " where ad_location = (?)"
         . " where concat_ws(',', ad_siteaddress, ad_sitecity, ad_sitestate, ad_sitezip) = (?)"
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
         . " update     {$tablePrefix}_adsmanager_ads A"
         . " inner join {$tablePrefix}_adsmanager_locations L"
         // . " on         A.ad_location = L.ad_location"
         . " on         A.ad_siteaddress = L.ad_siteaddress"
         . "        and A.ad_sitecity = L.ad_sitecity"
         . "        and A.ad_sitestate = L.ad_sitestate"
         . "        and A.ad_sitezip = L.ad_sitezip"
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