-- Clean up old nurse requests to fix duplicate request issues
-- This script removes old pending requests that might be causing conflicts

-- First, let's see what requests exist
SELECT * FROM nurse_requests ORDER BY id DESC;

-- Remove any old pending requests for specific nurse emails (replace with actual email)
-- DELETE FROM nurse_requests WHERE nurse_email = 'sample5@gmail.com' AND status = 'pending';

-- Or remove all old pending requests (use with caution)
-- DELETE FROM nurse_requests WHERE status = 'pending';

-- Reset auto-increment if needed
-- ALTER TABLE nurse_requests AUTO_INCREMENT = 1; 