use asianinc_subs;

ALTER TABLE `akj9c_adsmanager_locations`
  ADD COLUMN `ad_siteaddress` VARCHAR(50) NULL  AFTER `loc_id`
, ADD COLUMN `ad_sitecity` VARCHAR(25) NULL  AFTER `ad_siteaddress`
, ADD COLUMN `ad_sitestate` VARCHAR(2) NULL  AFTER `ad_sitecity`
, ADD COLUMN `ad_sitezip` VARCHAR(5) NULL  AFTER `ad_sitestate`
;
