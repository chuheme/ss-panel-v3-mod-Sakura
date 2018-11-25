ALTER TABLE `ss_node` CHANGE `node_ip` `node_ip` VARCHAR(16) NULL DEFAULT NULL;
ALTER TABLE `ss_node` CHANGE `bandwidthlimit_resetday` `bandwidthlimit_resetday` INT(2) NOT NULL DEFAULT '0';
ALTER TABLE `ss_node` CHANGE `dns_value` `dns_value` VARCHAR(128) NULL DEFAULT NULL;
ALTER TABLE `ss_node` CHANGE `info` `info` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;