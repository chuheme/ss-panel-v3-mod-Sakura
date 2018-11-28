ALTER TABLE `ss_node` CHANGE `dns_type` `dns_type` VARCHAR(8) NULL DEFAULT NULL;
UPDATE `ss_node` SET `dns_type` = 'A' WHERE `ss_node`.`dns_type` = 1;
UPDATE `ss_node` SET `dns_type` = 'CNAME' WHERE `ss_node`.`dns_type` = 2;