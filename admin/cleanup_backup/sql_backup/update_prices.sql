-- Update SERTOP prices (rows 61-72)
UPDATE sertop_products SET base_price = 57.72 WHERE size_inches = 8 AND model = 'P1';
UPDATE sertop_products SET base_price = 57.72 WHERE size_inches = 8 AND model = 'P2';
UPDATE sertop_products SET base_price = 57.72 WHERE size_inches = 8 AND model = 'P3';

UPDATE sertop_products SET base_price = 64.93 WHERE size_inches = 9 AND model = 'P1';
UPDATE sertop_products SET base_price = 64.93 WHERE size_inches = 9 AND model = 'P2';
UPDATE sertop_products SET base_price = 64.93 WHERE size_inches = 9 AND model = 'P3';

UPDATE sertop_products SET base_price = 72.15 WHERE size_inches = 10 AND model = 'P1';
UPDATE sertop_products SET base_price = 72.15 WHERE size_inches = 10 AND model = 'P2';
UPDATE sertop_products SET base_price = 72.15 WHERE size_inches = 10 AND model = 'P3';

UPDATE sertop_products SET base_price = 79.37 WHERE size_inches = 11 AND model = 'P1';
UPDATE sertop_products SET base_price = 79.37 WHERE size_inches = 11 AND model = 'P2';
UPDATE sertop_products SET base_price = 79.37 WHERE size_inches = 11 AND model = 'P3';

-- Update MARKER prices (rows 74-76)
UPDATE marker_products SET base_price = 57.72 WHERE model = 'M1';
UPDATE marker_products SET base_price = 57.72 WHERE model = 'M2';
UPDATE marker_products SET base_price = 57.72 WHERE model = 'M3';

-- Update BASE prices (rows 78-84)
UPDATE base_products SET base_price = 57.72 WHERE size_inches = 6 AND model = 'B1';
UPDATE base_products SET base_price = 57.72 WHERE size_inches = 6 AND model = 'B2';
UPDATE base_products SET base_price = 57.72 WHERE size_inches = 6 AND model = 'B3';

UPDATE base_products SET base_price = 64.93 WHERE size_inches = 8 AND model = 'B1';
UPDATE base_products SET base_price = 64.93 WHERE size_inches = 8 AND model = 'B2';
UPDATE base_products SET base_price = 64.93 WHERE size_inches = 8 AND model = 'B3';

-- Update SLANT prices (rows 87-91)
UPDATE slant_products SET base_price = 57.72 WHERE model = 'S1';
UPDATE slant_products SET base_price = 57.72 WHERE model = 'S2';
UPDATE slant_products SET base_price = 57.72 WHERE model = 'S3';
UPDATE slant_products SET base_price = 57.72 WHERE model = 'S4';
UPDATE slant_products SET base_price = 57.72 WHERE model = 'S5';
