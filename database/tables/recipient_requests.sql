CREATE TABLE IF NOT EXISTS `recipient_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `required_organ` varchar(50) NOT NULL,
  `priority_level` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `request_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `hospital_id` (`hospital_id`),
  CONSTRAINT `recipient_requests_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `recipient_registration` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipient_requests_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
