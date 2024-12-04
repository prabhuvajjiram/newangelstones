-- Create models table
CREATE TABLE IF NOT EXISTS models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some initial model data
INSERT INTO models (name) VALUES 
('Model 1'),
('Model 2'),
('Model 3'),
('Model 4'),
('Model 5'),
('Model 6'),
('Model 7'),
('Model 8'),
('Model 9'),
('Model 10');
