-- Migration Script: Add assigned_school column to nurses table
-- This script adds the missing assigned_school column that nurse functionality requires

-- Add assigned_school column to nurses table
ALTER TABLE nurses 
ADD COLUMN assigned_school VARCHAR(255) DEFAULT NULL;

-- Update the column comment for clarity
ALTER TABLE nurses 
MODIFY COLUMN assigned_school VARCHAR(255) DEFAULT NULL COMMENT 'School name where the nurse is assigned';

-- Verify the change
DESCRIBE nurses; 