-- Demo Dataset for Personal Finance Management System

-- Demo Users
INSERT INTO users (name, email, password) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Edgah Kipkemoi', 'edgah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- User-specific categories (in addition to default ones)
INSERT INTO categories (category_name, user_id) VALUES 
('Rent', 1),
('Gym Membership', 1),
('Books', 1),
('Coffee', 2),
('Travel', 2),
('Subscriptions', 3),
('University Fees', 3);

-- Demo Budgets
INSERT INTO budgets (amount, month, year, user_id) VALUES 
(50000.00, 12, 2025, 1), -- John's December budget
(45000.00, 11, 2025, 1), -- John's November budget
(35000.00, 12, 2025, 2), -- Jane's December budget
(40000.00, 12, 2025, 3), -- Edgah's December budget
(38000.00, 11, 2025, 3); -- Edgah's November budget

-- Demo Expenses for John Doe (user_id = 1)
INSERT INTO expenses (amount, description, expense_date, category_id, user_id) VALUES 
-- December 2025 expenses
(25000.00, 'Monthly rent payment', '2025-12-01', 9, 1), -- Rent category
(1200.00, 'Lunch at Java House', '2025-12-19', 1, 1), -- Food & Dining
(450.00, 'Uber to CBD', '2025-12-18', 2, 1), -- Transportation
(3500.00, 'Groceries - Carrefour', '2025-12-18', 3, 1), -- Shopping
(2800.00, 'Electricity bill', '2025-12-17', 5, 1), -- Bills & Utilities
(800.00, 'Movie tickets', '2025-12-16', 4, 1), -- Entertainment
(2500.00, 'Gym membership', '2025-12-15', 10, 1), -- Gym Membership
(1500.00, 'Fuel for car', '2025-12-14', 2, 1), -- Transportation
(2200.00, 'Internet bill', '2025-12-13', 5, 1), -- Bills & Utilities
(850.00, 'Dinner with friends', '2025-12-12', 1, 1), -- Food & Dining
(1200.00, 'New books', '2025-12-11', 11, 1), -- Books
(750.00, 'Coffee and snacks', '2025-12-10', 1, 1), -- Food & Dining
(3200.00, 'Clothing shopping', '2025-12-09', 3, 1), -- Shopping
(1800.00, 'Medical checkup', '2025-12-08', 6, 1), -- Healthcare
(950.00, 'Bus fare for week', '2025-12-07', 2, 1), -- Transportation

-- November 2025 expenses
(25000.00, 'Monthly rent payment', '2025-11-01', 9, 1),
(1800.00, 'Groceries', '2025-11-15', 3, 1),
(2200.00, 'Electricity bill', '2025-11-10', 5, 1),
(1500.00, 'Entertainment', '2025-11-20', 4, 1);

-- Demo Expenses for Jane Smith (user_id = 2)
INSERT INTO expenses (amount, description, expense_date, category_id, user_id) VALUES 
-- December 2025 expenses
(1800.00, 'Weekly groceries', '2025-12-19', 3, 2),
(650.00, 'Coffee shop visits', '2025-12-18', 12, 2), -- Coffee category
(2200.00, 'Flight booking', '2025-12-17', 13, 2), -- Travel category
(1200.00, 'Restaurant dinner', '2025-12-16', 1, 2),
(800.00, 'Taxi rides', '2025-12-15', 2, 2),
(3500.00, 'Shopping mall', '2025-12-14', 3, 2),
(1500.00, 'Utility bills', '2025-12-13', 5, 2),
(900.00, 'Cinema and popcorn', '2025-12-12', 4, 2),
(1100.00, 'Pharmacy items', '2025-12-11', 6, 2),
(750.00, 'Online course', '2025-12-10', 7, 2);

-- Demo Expenses for Edgah Kipkemoi (user_id = 3)
INSERT INTO expenses (amount, description, expense_date, category_id, user_id) VALUES 
-- December 2025 expenses
(15000.00, 'University tuition', '2025-12-01', 15, 3), -- University Fees
(2500.00, 'Netflix, Spotify subscriptions', '2025-12-19', 14, 3), -- Subscriptions
(1800.00, 'Textbooks for semester', '2025-12-18', 7, 3), -- Education
(950.00, 'Lunch at campus', '2025-12-17', 1, 3),
(600.00, 'Matatu fare', '2025-12-16', 2, 3),
(2200.00, 'Laptop accessories', '2025-12-15', 3, 3),
(1200.00, 'Phone bill', '2025-12-14', 5, 3),
(800.00, 'Movie with friends', '2025-12-13', 4, 3),
(1500.00, 'Medical insurance', '2025-12-12', 6, 3),
(3200.00, 'Project materials', '2025-12-11', 7, 3),
(750.00, 'Food delivery', '2025-12-10', 1, 3),
(1100.00, 'Stationery supplies', '2025-12-09', 7, 3),

-- November 2025 expenses
(15000.00, 'University tuition', '2025-11-01', 15, 3),
(1200.00, 'Study materials', '2025-11-15', 7, 3),
(800.00, 'Transport', '2025-11-20', 2, 3);