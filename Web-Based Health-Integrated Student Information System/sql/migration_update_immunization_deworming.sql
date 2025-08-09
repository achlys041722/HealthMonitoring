-- Migration Script: Update Immunization and Deworming Fields
-- This script updates the database schema to include "Complete" options for MR and TD immunizations
-- and consolidates deworming fields into a single dropdown with new options

-- Step 1: Update the immunization_mr and immunization_td ENUMs to include "Complete"
ALTER TABLE student_health 
MODIFY COLUMN immunization_mr ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None';

ALTER TABLE student_health 
MODIFY COLUMN immunization_td ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None';

-- Step 2: Consolidate deworming_1st and deworming_2nd into a single deworming column
-- First, add the new consolidated column
ALTER TABLE student_health 
ADD COLUMN deworming ENUM('None', '1st Dose', '2nd Dose (Complete)') DEFAULT 'None';

-- Update the new column based on existing deworming values
-- Logic: If either 1st or 2nd dose is 'Complete', set to '2nd Dose (Complete)'
-- If 1st dose is '1st dose' or '2nd dose', set to '1st Dose'
-- Otherwise, set to 'None'
UPDATE student_health 
SET deworming = CASE 
    WHEN deworming_1st = 'Complete' OR deworming_2nd = 'Complete' THEN '2nd Dose (Complete)'
    WHEN deworming_1st = '1st dose' OR deworming_1st = '2nd dose' OR deworming_2nd = '1st dose' OR deworming_2nd = '2nd dose' THEN '1st Dose'
    ELSE 'None'
END;

-- Drop the old columns
ALTER TABLE student_health 
DROP COLUMN deworming_1st;

ALTER TABLE student_health 
DROP COLUMN deworming_2nd;

-- Step 3: Verify the changes
SELECT 
    'Schema updated successfully. New options available:' as message,
    'MR: None, 1st dose, 2nd dose, Complete' as mr_options,
    'TD: None, 1st dose, 2nd dose, Complete' as td_options,
    'Deworming: None, 1st Dose, 2nd Dose (Complete)' as deworming_options; 