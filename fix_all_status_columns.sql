-- ============================================
-- Fix all status columns to support 'pending'
-- ============================================

-- Fix companies.status
ALTER TABLE companies 
MODIFY COLUMN status ENUM('active', 'inactive', 'pending') 
NOT NULL 
DEFAULT 'pending';

-- Fix users.status
ALTER TABLE users 
MODIFY COLUMN status ENUM('active', 'inactive', 'pending') 
NOT NULL 
DEFAULT 'active';

-- Fix company_subscriptions.status
ALTER TABLE company_subscriptions 
MODIFY COLUMN status ENUM('active', 'expired', 'cancelled', 'pending') 
NOT NULL 
DEFAULT 'pending';

-- ============================================
-- Verify all changes
-- ============================================
-- SHOW COLUMNS FROM companies WHERE Field = 'status';
-- SHOW COLUMNS FROM users WHERE Field = 'status';
-- SHOW COLUMNS FROM company_subscriptions WHERE Field = 'status';
