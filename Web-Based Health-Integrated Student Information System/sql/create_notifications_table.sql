-- Universal notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_role ENUM('principal','teacher','nurse') NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Add index for fast lookup
CREATE INDEX idx_notifications_user ON notifications(user_id, user_role, status);