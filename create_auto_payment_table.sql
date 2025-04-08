CREATE TABLE IF NOT EXISTS auto_payment_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_id VARCHAR(10) NOT NULL,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    completed_at DATETIME DEFAULT NULL,
    message_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 