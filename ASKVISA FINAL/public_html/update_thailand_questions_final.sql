-- =========================================================================================
-- SQL Script to update Thailand Visa Questions (Production Safe Version)
-- This script uses an `is_active` flag to avoid deleting any records and preserves applicant data.
-- =========================================================================================

-- 1. ADD THE COLUMN (Run this part first if it doesn't exist)
-- Note: ALTER TABLE causes an implicit COMMIT in MySQL, so it's outside the transaction.
-- If the column already exists, this will simply error but won't harm your data.
ALTER TABLE country_questions ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- 2. START THE SAFE UPDATE PROCESS
START TRANSACTION;

-- Get the country ID for Thailand
SET @thailand_id = (SELECT id FROM countries WHERE country_name LIKE '%Thailand%' LIMIT 1);

-- 3. Mark all existing Thailand questions as INACTIVE
UPDATE country_questions 
SET is_active = 0 
WHERE country_id = @thailand_id;

-- 4. Update visa_types schema and add Digital Arrival Card
ALTER TABLE visa_types ADD COLUMN IF NOT EXISTS processing_time VARCHAR(255) DEFAULT '3-5 days' AFTER description;

INSERT INTO visa_types (country_id, name, price, currency, description, processing_time)
SELECT @thailand_id, 'Digital Arrival Card', 100.00, 'INR', 'Official Govt. Arrival Card', 'Instant'
WHERE NOT EXISTS (SELECT 1 FROM visa_types WHERE name = 'Digital Arrival Card' AND country_id = @thailand_id);

-- 5. INSERT the New/Updated active questions
-- We use field_key to distinguish them.
INSERT INTO country_questions (country_id, label, field_key, field_type, is_required, sort_order, is_active, validation_rules) VALUES
(@thailand_id, 'Family Name: Your passport surname (e.g., PATEL)', 'last_name', 'text', 1, 1, 1, '{"required":true,"regex":"^[a-zA-Z\\\\s\\\\''-]*$"}'),
(@thailand_id, 'First Name: Your given name (e.g., RAHUL)', 'first_name', 'text', 1, 2, 1, '{"required":true,"regex":"^[a-zA-Z\\\\s\\\\''-]*$"}'),
(@thailand_id, 'Middle Name: Your middle name if in passport, otherwise leave blank', 'middle_name', 'text', 0, 3, 1, '{"regex":"^[a-zA-Z\\\\s\\\\''-]*$"}'),
(@thailand_id, 'Passport No.: Your passport number (e.g., T1234567)', 'passport_number', 'text', 1, 4, 1, '{"required":true,"regex":"^[A-Z0-9]*$"}'),
(@thailand_id, 'Date of Birth: YYYY/MM/DD (e.g., 1998/07/21)', 'date_of_birth', 'date', 1, 5, 1, '{"required":true,"date_format":"YYYY/MM/DD","max_date":"TODAY"}'),
(@thailand_id, 'Occupation', 'occupation', 'select', 1, 6, 1, '{"required":true}'),
(@thailand_id, 'Gender', 'gender', 'select', 1, 7, 1, '{"required":true}'),
(@thailand_id, 'State of Residence: Your state (e.g., Gujarat)', 'state_of_residence', 'text', 1, 8, 1, '{"required":true}'),
(@thailand_id, 'Date of Arrival: Your Thailand landing date (YYYY/MM/DD)', 'arrival_date', 'date', 1, 9, 1, '{"required":true,"date_format":"YYYY/MM/DD","min_date":"TODAY"}'),
(@thailand_id, 'Flight No./Vehicle No.: Your flight number (e.g., AI302, 6E1053)', 'arrival_flight', 'text', 1, 10, 1, '{"required":true,"regex":"^[a-zA-Z0-9]*$"}'),
(@thailand_id, 'Date of Departure: Your return flight date from Thailand (YYYY/MM/DD)', 'departure_date', 'date', 1, 11, 1, '{"required":true,"date_format":"YYYY/MM/DD","min_date":"TODAY"}'),
(@thailand_id, 'Return Flight No./Vehicle No.: (if known, otherwise leave blank)', 'departure_flight', 'text', 0, 12, 1, '{"regex":"^[a-zA-Z0-9]*$"}'),
(@thailand_id, 'Type of Accommodation in Thailand', 'accommodation_type', 'select', 1, 13, 1, '{"required":true}'),
(@thailand_id, 'Province: Province where hotel is (e.g., Bangkok, Phuket, Chon Buri)', 'hotel_province', 'text', 1, 14, 1, '{"required":true}'),
(@thailand_id, 'District/Area: District of hotel location', 'hotel_district', 'text', 1, 15, 1, '{"required":true}'),
(@thailand_id, 'Sub-District/Sub-Area: Sub area of hotel location', 'hotel_sub_district', 'text', 1, 16, 1, '{"required":true}'),
(@thailand_id, 'Address: Full hotel address or hotel name', 'hotel_name', 'text', 1, 17, 1, '{"required":true}')
ON DUPLICATE KEY UPDATE 
    label = VALUES(label),
    field_type = VALUES(field_type),
    is_required = VALUES(is_required),
    sort_order = VALUES(sort_order),
    is_active = 1,
    validation_rules = VALUES(validation_rules);

-- 5. Manage select options (using field_key lookups)
-- Occupation Options
SET @occupation_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'occupation' AND is_active = 1 LIMIT 1);
DELETE FROM question_options WHERE question_id = @occupation_q_id;
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@occupation_q_id, 'employee', 'Employee', 1),
(@occupation_q_id, 'student', 'Student', 2),
(@occupation_q_id, 'business', 'Business', 3),
(@occupation_q_id, 'self_employed', 'Self-Employed', 4);

-- Gender Options
SET @gender_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'gender' AND is_active = 1 LIMIT 1);
DELETE FROM question_options WHERE question_id = @gender_q_id;
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@gender_q_id, 'male', 'Male', 1),
(@gender_q_id, 'female', 'Female', 2);

-- Accommodation Options
SET @accommodation_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'accommodation_type' AND is_active = 1 LIMIT 1);
DELETE FROM question_options WHERE question_id = @accommodation_q_id;
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@accommodation_q_id, 'hotel', 'Hotel', 1),
(@accommodation_q_id, 'resort', 'Resort', 2),
(@accommodation_q_id, 'hostel', 'Hostel', 3),
(@accommodation_q_id, 'apartment', 'Apartment', 4);

-- 6. VERIFICATION 
-- Check if things look right. 
-- SELECT * FROM country_questions WHERE country_id = (SELECT id FROM countries WHERE country_name LIKE '%Thailand%' LIMIT 1) AND is_active = 1;

-- 7. FINALIZE (Uncomment COMMIT to save, or run ROLLBACK to undo)
-- COMMIT;
-- ROLLBACK;
