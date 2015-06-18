<?php

class MakeMap {

   public function makeGoogleMap() {
   
      $success = false;
      
      $purifier_config = HTMLPurifier_Config::createDefault();
      $purifier = new HTMLPurifier($purifier_config);

      // Connect to MySQL database
      $conn = new mysqli(DBInfo::HOST, DBInfo::USERNAME, DBInfo::PASSWORD, DBInfo::DATABASE);
      if ($conn->connect_errno) {
         die('Failed to connect to MySQL: (' . $conn->connect_errno . ') ' . $conn->connect_error);
      }


      // ****************************************
      // Select Map Data
      // ****************************************

      $sql = ""
      . "  select"
      . "  A.id, A.ad_headline, A.ad_location, A.loc_id"
      . ", L.latitude, L.longitude"
      . ", A.ad_text, A.email, A.name, A.ad_WorkPhone, A.ad_phone"
      . "  from       akj9c_adsmanager_ads A"
      . "  inner join akj9c_adsmanager_locations L"
      . "  on         A.loc_id = L.loc_id"
      . "  where"
      . Constants::getCommonCriteria()
      . "  order by A.ad_location, ad_headline, A.ad_text"
      . ";";

      $qry = $conn->prepare($sql);
      if (!$qry) {
         die('Prepare failed: (' . $conn->errno .') ' . $conn->error);
      } else {
         $execQry = $qry->execute();
         if (!$execQry) {
            die('Execute failed: (' . $qry->errno .') ' . $qry->error);
            $qry->close();
         } else {
            $result = $qry->get_result();
            if (!$result) {
               die('Getting result set failed: (' . $qry->errno .') ' . $qry->error);
            } else {
               $mapData = $result->fetch_all(MYSQLI_ASSOC);
               $result->close();
            }
         }
         $qry->close();
      }

      $conn->close();


      // ****************************************
      // Create Map
      // ****************************************

      $MAP_OBJECT = new GoogleMapAPI();
      $MAP_OBJECT->_minify_js = isset($_REQUEST["min"])?FALSE:TRUE;
      
      $MAP_OBJECT->setMapType('ROADMAP');
      $MAP_OBJECT->disableSidebar();

      $sidebar_html = '';

      //add markers to map and create sidebar html

      foreach ($mapData as $item) {

         // Apply HTML Purifier to data
         $clean_company_name = $purifier->purify($item['ad_headline']);
         $clean_ad_loc       = $purifier->purify($item['ad_location']);
         $clean_ad_text      = $purifier->purify($item['ad_text']);
         $clean_name         = $purifier->purify($item['name']);
         $clean_email        = $purifier->purify($item['email']);
         $clean_phone        = $purifier->purify($item['ad_WorkPhone']);
         $clean_fax          = $purifier->purify($item['ad_phone']);
         $clean_long         = $purifier->purify($item['longitude']);
         $clean_lat          = $purifier->purify($item['latitude']);
         
         // popup window content
         $pop_text =   '<div class="GMap">'
                     . '<p class="GMapPopupHeader">Contract Information</p>'
                     . '<table class="GMap">'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Company Name</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_company_name . '</td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Contract Location</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_ad_loc . '</td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Contract Description</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_ad_text . '</td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Name</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_name . '</td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Email</th>'
                        . '<td class="GMap GMapPopupContent">'
                        .    '<a href="mailto:' . $clean_email . '?Subject=' . $clean_ad_loc . '"'
                        .    ' target="_top">' . $clean_email . '</a></td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Phone</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_phone . '</td>'
                     . '</tr>'
                     . '<tr>'
                        . '<th class="GMap GMapPopupContent">Fax</th>'
                        . '<td class="GMap GMapPopupContent">' . $clean_fax . '</td>'
                     . '</tr>'
                     . '</table>'
                     . '</div>';
         
         //add the marker to the map.
         $marker_id = $MAP_OBJECT->addMarkerByCoords( $clean_long
                                                    , $clean_lat
                                                    , $clean_ad_loc
                                                    , $pop_text
                                                    );

         //create an id to be used for the marker opener <a>
         $opener_id = "opener_" . $marker_id;

         //append <li> item to sidebar html
         $sidebar_html .= "<li class='GMap' id='$opener_id'>";
         $sidebar_html .=     '<a class="GMap" href="#">' . $clean_ad_loc . '</a>';
         $sidebar_html .=     '<p class="GMap">' . substr($clean_ad_text, 0, 40) . ' ...' . '</p>';
         $sidebar_html .= '</li>';

         //add marker opener id to map object
         $MAP_OBJECT->addMarkerOpener($marker_id, $opener_id);

      }

      $MAP_OBJECT->setWidth('65%');
      $MAP_OBJECT->setZoomLevel('12');
      $MAP_OBJECT->disableZoomEncompass();

      // start of HTML including opened head tag, title, css link
      $path_parts = array(dirname(__FILE__), 'html', 'MapHtmlStart.html');
      $path = join(DIRECTORY_SEPARATOR, $path_parts);
      $html = file_get_contents($path);

      $js = $MAP_OBJECT->getHeaderJS();
      $js .= $MAP_OBJECT->getMapJS();
      $html .= $js;

      $html .= '</head>';
      $html .= '<body>';

      // map section with containers for header, note, and sidebar
      $path_parts = array(dirname(__FILE__), 'html', 'MapHtmlMapSection.html');
      $path = join(DIRECTORY_SEPARATOR, $path_parts);
      $mapSection = file_get_contents($path);
      $mapSection = str_replace("[{sidebar_html}]", $sidebar_html, $mapSection);
      $html .= $mapSection;

      $js = $MAP_OBJECT->getMap();
      $html .= $js;
      $html .= '</div>';   // close the container div with sidebar and map
      
      $js = $MAP_OBJECT->getOnLoad();
      $html .= $js;
      
      // acknowledgement for libraries
      $path_parts = array(dirname(__FILE__), 'html', 'Acknowledge.html');
      $path = join(DIRECTORY_SEPARATOR, $path_parts);
      $footer = file_get_contents($path);
      $html .= $footer;
      
      $html .= '</body>';
      $html .= '</html>';

      echo $html;

      $success = true;
      return $success;
      
   }
}
?>