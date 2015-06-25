CREATE  TABLE `asianinc_subs`.`akj9c_ads_cities` (
  `city_id` INT NOT NULL AUTO_INCREMENT ,
  `city_name` VARCHAR(50) NOT NULL ,
  `city_state` VARCHAR(2) NOT NULL ,
  PRIMARY KEY (`city_state`, `city_name`) ,
  INDEX `ads_cities_idx_city_id` (`city_id` ASC) )
COMMENT = 'Lookup table for city & state dropdowns.';
