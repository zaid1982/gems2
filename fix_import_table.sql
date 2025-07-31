-- Fix reserved keyword issue in wo_import_log table
USE `gems`;

-- Rename row_number to import_row_number to avoid reserved keyword conflict
ALTER TABLE `wo_import_log` CHANGE `row_number` `import_row_number` int(11) NOT NULL;
