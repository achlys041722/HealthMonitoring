-- Migration Script: Update Immunization Options
-- This script removes the "Complete" option from MR and TD immunizations

-- Update immunization_mr enum to remove "Complete"
ALTER TABLE student_health 
MODIFY COLUMN immunization_mr ENUM('None', '1st dose', '2nd dose') DEFAULT 'None';

-- Update immunization_td enum to remove "Complete"
ALTER TABLE student_health 
MODIFY COLUMN immunization_td ENUM('None', '1st dose', '2nd dose') DEFAULT 'None';

-- Update any existing "Complete" values to "2nd dose"
UPDATE student_health 
SET immunization_mr = '2nd dose' 
WHERE immunization_mr = 'Complete';

UPDATE student_health 
SET immunization_td = '2nd dose' 
WHERE immunization_td = 'Complete';

-- Verify the changes
SELECT 
    'Immunization options updated successfully' as message,
    'MR: None, 1st dose, 2nd dose' as mr_options,
    'TD: None, 1st dose, 2nd dose' as td_options; 