-- ============================================
-- Fix companies.status column to support 'pending'
-- ============================================

-- Step 1: Check current column definition
-- Run this first to see the current structure:
-- SHOW COLUMNS FROM companies WHERE Field = 'status';

-- Step 2: If status is ENUM, modify it to include 'pending'
-- This query will:
-- - Add 'pending' to the ENUM values
-- - Set default to 'pending'
-- - Make it NOT NULL
ALTER TABLE companies 
MODIFY COLUMN status ENUM('active', 'inactive', 'pending') 
NOT NULL 
DEFAULT 'pending';

-- Step 3: If status is VARCHAR, modify it to ensure sufficient length
-- (Uncomment and use this if status is VARCHAR instead of ENUM)
-- ALTER TABLE companies 
-- MODIFY COLUMN status VARCHAR(20) 
-- NOT NULL 
-- DEFAULT 'pending';

-- Step 4: Verify the change
-- Run this to confirm the modification:
-- SHOW COLUMNS FROM companies WHERE Field = 'status';

-- ============================================
-- Optional: Update existing NULL or invalid values
-- ============================================
-- If you have existing records with NULL or invalid status values:
-- UPDATE companies SET status = 'pending' WHERE status IS NULL OR status NOT IN ('active', 'inactive', 'pending');
