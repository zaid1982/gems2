-- Create table for Visitor Management Hosts (per site)
CREATE TABLE IF NOT EXISTS vm_host (
  host_id INT AUTO_INCREMENT PRIMARY KEY,
  site_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NULL,
  contact_no VARCHAR(50) NULL,
  department VARCHAR(100) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vm_host_site_name (site_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional FK if cli_site exists and you enforce it:
-- ALTER TABLE vm_host ADD CONSTRAINT fk_vm_host_site FOREIGN KEY (site_id) REFERENCES cli_site(site_id);
