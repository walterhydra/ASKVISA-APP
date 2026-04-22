-- =========================================================================================
-- SQL Script to update Thailand Visa Questions in the ASKVISA database.
-- =========================================================================================

-- 1. Get the country ID for Thailand
SET @thailand_id = (SELECT id FROM countries WHERE country_name LIKE '%Thailand%' LIMIT 1);

-- 2. Delete existing questions for Thailand to avoid duplicates or orphaned options
-- This will also cascade delete question_options if foreign keys are set up correctly.
-- If not, we should manually delete options first. Let's be safe and delete options first.
DELETE FROM question_options WHERE question_id IN (SELECT id FROM country_questions WHERE country_id = @thailand_id);
DELETE FROM country_questions WHERE country_id = @thailand_id;

-- 3. Insert new/updated questions
INSERT INTO country_questions (country_id, label, field_key, field_type, is_required, sort_order) VALUES
(@thailand_id, 'Family Name: Your passport surname (e.g., PATEL)', 'last_name', 'text', 1, 1),
(@thailand_id, 'First Name: Your given name (e.g., RAHUL)', 'first_name', 'text', 1, 2),
(@thailand_id, 'Middle Name: Your middle name if in passport, otherwise leave blank', 'middle_name', 'text', 0, 3),
(@thailand_id, 'Passport No.: Your passport number (e.g., T1234567)', 'passport_number', 'text', 1, 4),
(@thailand_id, 'Passport Front Image', 'passport_front', 'file', 1, 5),
(@thailand_id, 'Passport Back Image', 'passport_back', 'file', 1, 6),
(@thailand_id, 'Date of Birth: YYYY / MM / DD (e.g., 1998 / 07 / 21)', 'date_of_birth', 'date', 1, 7),
(@thailand_id, 'Occupation', 'occupation', 'select', 1, 8),
(@thailand_id, 'Gender', 'gender', 'select', 1, 9),
(@thailand_id, 'State of Residence: Your state (e.g., Gujarat)', 'state_of_residence', 'text', 1, 10),
(@thailand_id, 'Phone No.: 10-digit mobile number', 'phone_number', 'text', 1, 11),
(@thailand_id, 'Email: User Email Address', 'email', 'text', 1, 12),
(@thailand_id, 'Date of Arrival: Your Thailand landing date (YYYY/MM/DD)', 'arrival_date', 'date', 1, 13),
(@thailand_id, 'Flight No./Vehicle No.: Your flight number (e.g., AI302, 6E1053)', 'arrival_flight', 'text', 1, 14),
(@thailand_id, 'Date of Departure: Your return flight date from Thailand (YYYY/MM/DD)', 'departure_date', 'date', 1, 15),
(@thailand_id, 'Return Flight No./Vehicle No.: (if known, otherwise leave blank)', 'departure_flight', 'text', 0, 16),
(@thailand_id, 'Type of Accommodation in Thailand', 'accommodation_type', 'select', 1, 17),
(@thailand_id, 'Province: Province where hotel is (e.g., Bangkok, Phuket, Chon Buri)', 'hotel_province', 'text', 1, 18),
(@thailand_id, 'District/Area: District of hotel location', 'hotel_district', 'text', 1, 19),
(@thailand_id, 'Sub-District/Sub-Area: Sub area of hotel location', 'hotel_sub_district', 'text', 1, 20),
(@thailand_id, 'Address: Full hotel address or hotel name', 'hotel_name', 'text', 1, 21);

-- 4. Insert options for 'select' type questions

-- Occupation (Question 8)
SET @occupation_q_id = LAST_INSERT_ID() - 13; -- Assuming sequential IDs, but let's be safer by querying
SET @occupation_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'occupation' LIMIT 1);
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@occupation_q_id, 'employee', 'Employee', 1),
(@occupation_q_id, 'student', 'Student', 2),
(@occupation_q_id, 'business', 'Business', 3),
(@occupation_q_id, 'self_employed', 'Self-Employed', 4);

-- Gender (Question 9)
SET @gender_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'gender' LIMIT 1);
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@gender_q_id, 'male', 'Male', 1),
(@gender_q_id, 'female', 'Female', 2);

-- Accommodation Type (Question 17)
SET @accommodation_q_id = (SELECT id FROM country_questions WHERE country_id = @thailand_id AND field_key = 'accommodation_type' LIMIT 1);
INSERT INTO question_options (question_id, option_value, option_label, sort_order) VALUES
(@accommodation_q_id, 'hotel', 'Hotel', 1),
(@accommodation_q_id, 'resort', 'Resort', 2),
(@accommodation_q_id, 'hostel', 'Hostel', 3),
(@accommodation_q_id, 'apartment', 'Apartment', 4);

-- Script Complete.
