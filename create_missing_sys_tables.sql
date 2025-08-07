-- Create missing system tables for PTW functionality
-- Based on usage in api/ptw.php

-- Create sys_site table
CREATE TABLE IF NOT EXISTS `sys_site` (
  `site_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_code` varchar(20) NOT NULL,
  `site_name` varchar(100) NOT NULL,
  `site_running_no` int(11) NOT NULL DEFAULT 0,
  `site_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `site_code` (`site_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sys_user table if it doesn't exist
CREATE TABLE IF NOT EXISTS `sys_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100),
  `site_id` int(11) DEFAULT 1,
  `user_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create sys_user_role table if it doesn't exist
CREATE TABLE IF NOT EXISTS `sys_user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default site data
INSERT IGNORE INTO `sys_site` (`site_id`, `site_code`, `site_name`, `site_running_no`) 
VALUES (1, 'GEMS', 'GEMS Main Site', 0);

-- Insert default user data  
INSERT IGNORE INTO `sys_user` (`user_id`, `user_name`, `user_email`, `site_id`) 
VALUES (1, 'Test User', 'test@gems.com', 1);

-- Insert default user role
INSERT IGNORE INTO `sys_user_role` (`user_id`, `role_id`) 
VALUES (1, 1);
