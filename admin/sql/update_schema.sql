-- Add bio column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS bio TEXT AFTER email;

-- Add profile_image column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) AFTER bio;

-- Add created_at and updated_at columns if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
