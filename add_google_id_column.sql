-- Add google_id column to users table for Google OAuth support
-- Run this SQL in phpMyAdmin or your MySQL client
-- 
-- Instructions:
-- 1. Open phpMyAdmin (usually at http://localhost/phpmyadmin or http://localhost:8080/phpmyadmin)
-- 2. Select your database (jessiecane)
-- 3. Click on the "SQL" tab
-- 4. Copy and paste the SQL below
-- 5. Click "Go" to execute

ALTER TABLE `users` 
ADD COLUMN `google_id` VARCHAR(255) NULL AFTER `password`,
ADD INDEX `idx_google_id` (`google_id`);

-- This allows users to log in with Google OAuth
-- The google_id will store the unique Google user ID
-- 
-- Note: If you get an error saying the column already exists, that's okay - it means it's already been added.

