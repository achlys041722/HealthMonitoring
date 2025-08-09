-- Script to assign existing nurses to a school for testing
-- Run this after adding the assigned_school column

-- First, let's see what schools exist
SELECT id, school_name FROM schools;

-- Assign nurses to the first available school (modify as needed)
UPDATE nurses 
SET assigned_school = (SELECT school_name FROM schools LIMIT 1)
WHERE assigned_school IS NULL;

-- Verify the assignment
SELECT id, full_name, email, assigned_school FROM nurses; 