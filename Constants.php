<?php

class Constants {

// Location Map | Request for Subs
// Common Criteria Constant

private $commonCriteria;

public static function getCommonCriteria() {

   $commonCriteria = " "
   
   // published
   . "     A.published = 1"

   // created within the last month
. " and datediff(curdate(), date_created) < 32"

   // exclude email, fax, multiple, and various
// . " and lower(trim(A.ad_location)) not like '%email%'"
. " and lower(trim(A.ad_siteaddress)) not like '%email%'"
// . " and lower(trim(A.ad_location)) not like '%fax%'"
. " and lower(trim(A.ad_siteaddress)) not like '%fax%'"
// . " and lower(A.ad_location) not regexp 'multiple|various'"
. " and lower(A.ad_siteaddress) not regexp 'multiple|various'"

   // exclude some known general locations
// . " and lower(trim(A.ad_location)) not in"
. " and lower(trim(A.ad_siteaddress)) not in"
. "     (  'united states', 'ca', 'california', 'bay area'"
. "      , 'san francisco, ca', 'san francisco ca', 'san francisco', 'sf')"

   // exclude city/county only
// . " and lower(trim(A.ad_location)) not regexp '^[[:alpha:] ]*,?[ ]*[a-z]{2}[ ]?[[:digit:]]{0,5}$'"
// . " and lower(trim(A.ad_location)) not regexp '^city of .+$'"
// . " and lower(trim(A.ad_location)) not regexp '^.*county$'"
. " and (   A.ad_siteaddress is null"
. "      or A.ad_siteaddress = ''"

   // exclude phone number
   // http://stackoverflow.com/questions/16699007/regular-expression-to-match-standard-10-digit-phone-number
// . " and trim(A.ad_location) not regexp"
. " and trim(A.ad_siteaddress) not regexp"
. "       '^("
. "\\\\" // 2 slashes in regexp
. "+[[:digit:]]{1,2} )?[[.(.]]?[[:digit:]]{3}[[.).]]?[ .-]?[[:digit:]]{3}[ .-]?[[:digit:]]{4}$'"

   // only 7 digits with optional separators
// . " and trim(A.ad_location) not regexp '^[[:digit:]]{3}[ .-]?[[:digit:]]{4}$'"
. " and trim(A.ad_siteaddress) not regexp '^[[:digit:]]{3}[ .-]?[[:digit:]]{4}$'"
;

return $commonCriteria;
}

}
?>
