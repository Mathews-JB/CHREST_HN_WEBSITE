USE PROJECTEVENTS;

-- Clear existing admin users to prevent duplicates/conflicts
TRUNCATE TABLE admin_users;

-- Insert default admin user
-- Username: admin
-- Password: admin123
INSERT INTO admin_users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

SELECT 'Admin user reset successfully' as message;
