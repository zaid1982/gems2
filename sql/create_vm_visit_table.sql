DROP TABLE IF EXISTS vm_visit;

CREATE TABLE vm_visit (
  visit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id  SMALLINT NOT NULL,                              -- match cli_site.site_id exactly
  arrived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  name VARCHAR(100) NOT NULL,
  contact_no VARCHAR(50) NOT NULL,
  ic_no VARCHAR(30) NOT NULL,
  company VARCHAR(100) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  party_size INT UNSIGNED NOT NULL DEFAULT 1,
  host_name VARCHAR(100) NOT NULL,
  purpose TEXT NOT NULL,
  status ENUM('CHECKED_IN','CHECKED_OUT','CANCELLED') NOT NULL DEFAULT 'CHECKED_IN',
  access_card_no VARCHAR(50) DEFAULT NULL,
  created_via VARCHAR(30) NOT NULL DEFAULT 'WEB_FORM',
  created_by BIGINT UNSIGNED DEFAULT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vm_visit_site_arrived (site_id, arrived_at),
  INDEX idx_vm_visit_status (status),
  CONSTRAINT fk_vm_visit_site
    FOREIGN KEY (site_id) REFERENCES cli_site(site_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;