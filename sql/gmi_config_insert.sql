-- Insert default gamification configuration values
-- Run this SQL to populate the gmi_config table with default values

INSERT INTO gmi_config (config_key, config_value, data_type, description, last_updated_by, last_updated_at, status) VALUES
('tier_medalist_threshold', '150', 'int', 'Minimum completed tasks required to achieve Medalist tier', 'system', NOW(), 1),
('tier_finisher_threshold', '80', 'int', 'Minimum completed tasks required to achieve Finisher tier', 'system', NOW(), 1),
('mbv_tier1_threshold', '50', 'int', 'MBV threshold for tier 1 multiplier (lowest performance)', 'system', NOW(), 1),
('mbv_tier2_threshold', '100', 'int', 'MBV threshold for tier 2 multiplier (medium performance)', 'system', NOW(), 1),
('mbv_tier1_multiplier', '1', 'int', 'Point multiplier for tier 1 (MBV <= 50)', 'system', NOW(), 1),
('mbv_tier2_multiplier', '3', 'int', 'Point multiplier for tier 2 (51 <= MBV <= 100)', 'system', NOW(), 1),
('mbv_tier3_multiplier', '5', 'int', 'Point multiplier for tier 3 (MBV > 100)', 'system', NOW(), 1),
('weight_completed', '0.3', 'float', 'Weight percentage for completion points (30%)', 'system', NOW(), 1),
('weight_ontime', '0.7', 'float', 'Weight percentage for on-time points (70%)', 'system', NOW(), 1),
('weight_late_penalty', '0.15', 'float', 'Weight percentage for late penalty (15%)', 'system', NOW(), 1),
('self_finding_points', '5', 'int', 'Points awarded per self-finding work order', 'system', NOW(), 1),
('point_scale_factor', '10000', 'int', 'Scaling factor for all point calculations', 'system', NOW(), 1),
('productivity_base', '90', 'int', 'Base productivity percentage for calculations', 'system', NOW(), 1),
('wo_ontime_multiplier', '2', 'int', 'Multiplier for work order on-time calculations', 'system', NOW(), 1);

-- Optional: Create indexes for better performance
CREATE INDEX idx_gmi_config_key ON gmi_config (config_key);
CREATE INDEX idx_gmi_config_status ON gmi_config (status);
