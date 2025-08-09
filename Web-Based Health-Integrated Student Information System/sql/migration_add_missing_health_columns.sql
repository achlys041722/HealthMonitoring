-- Migration Script: Add Missing Health Assessment Columns
-- This script adds the missing columns to the student_health table

-- Add height_for_age column
ALTER TABLE student_health 
ADD COLUMN height_for_age ENUM('Severely Stunted', 'Stunted', 'Normal', 'Tall') DEFAULT NULL;

-- Add weight_for_age column
ALTER TABLE student_health 
ADD COLUMN weight_for_age ENUM('Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese') DEFAULT NULL;

-- Add four_ps_beneficiary column
ALTER TABLE student_health 
ADD COLUMN four_ps_beneficiary BOOLEAN DEFAULT FALSE;

-- Add immunization_mr column
ALTER TABLE student_health 
ADD COLUMN immunization_mr ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None';

-- Add immunization_td column
ALTER TABLE student_health 
ADD COLUMN immunization_td ENUM('None', '1st dose', '2nd dose', 'Complete') DEFAULT 'None';

-- Add immunization_hpv column
ALTER TABLE student_health 
ADD COLUMN immunization_hpv ENUM('None', 'Complete', 'Incomplete') DEFAULT 'None';

-- Add ailments column
ALTER TABLE student_health 
ADD COLUMN ailments TEXT DEFAULT NULL;

-- Verify the changes
SELECT 
    'Schema updated successfully. Added columns:' as message,
    'height_for_age: Severely Stunted, Stunted, Normal, Tall' as height_for_age_options,
    'weight_for_age: Severely Underweight, Underweight, Normal, Overweight, Obese' as weight_for_age_options,
    'four_ps_beneficiary: BOOLEAN' as four_ps_beneficiary_type,
    'immunization_mr: None, 1st dose, 2nd dose, Complete' as immunization_mr_options,
    'immunization_td: None, 1st dose, 2nd dose, Complete' as immunization_td_options,
    'immunization_hpv: None, Complete, Incomplete' as immunization_hpv_options,
    'ailments: TEXT' as ailments_type; 