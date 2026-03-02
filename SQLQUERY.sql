CREATE DATABASE IF NOT EXISTS expense_tracker
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE expense_tracker;

CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(20) NOT NULL,
    email VARCHAR(25) NOT NULL UNIQUE,
    password VARCHAR(150) NOT NULL,
    user_role ENUM('Student','Corporate') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('Income','Expense','Both') NOT NULL DEFAULT 'Both',

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    amount DECIMAL(12,2) NOT NULL CHECK (amount > 0),
    type ENUM('Income','Expense') NOT NULL,
    category_id INT(11) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_transactions_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, type) VALUES
('Salary','Income'),
('Freelance','Income'),
('Business Income','Income'),
('Scholarship','Income'),
('Part-time Job','Income'),

('Investment','Income'),
('Food & Dining','Expense'),
('Rent','Expense'),
('Transport','Expense'),
('Office Supplies','Expense'),

('Healthcare','Expense'),
('Education','Expense'),
('Entertainment','Expense'),
('Utilities','Expense'),
('Shopping','Expense'),

('Other','Both');

INSERT INTO users (username, email, password, user_role) VALUES
(
'Suraj Gupta',
'suraj@gmail.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'Student'
),
(
'Suraj Corporate',
'corporate@company.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'Corporate'
);

INSERT INTO transactions
(user_id, amount, type, category_id, description, date) VALUES

-- Suraj Gupta
(1,15000.00,'Income',4,'Monthly scholarship received','2026-02-01'),
(1,3000.00,'Income',5,'Part-time tutoring job','2026-02-10'),
(1,2500.00,'Expense',7,'Mess fees and groceries','2026-02-03'),
(1,4000.00,'Expense',8,'Hostel rent for February','2026-02-05'),
(1,600.00,'Expense',9,'Bus pass monthly','2026-02-06'),
(1,1200.00,'Expense',12,'Textbooks and stationery','2026-02-12'),
(1,500.00,'Expense',13,'Movie and outing','2026-02-15'),

-- Suraj Corporate
(2,75000.00,'Income',1,'February salary credited','2026-02-01'),
(2,20000.00,'Income',3,'Business consulting fee','2026-02-08'),
(2,12000.00,'Expense',8,'Office cabin rent (shared)','2026-02-02'),
(2,5000.00,'Expense',10,'Printer paper, toner, supplies','2026-02-05'),
(2,8500.00,'Expense',7,'Team lunch and client dinner','2026-02-14'),
(2,3200.00,'Expense',9,'Cab rides and fuel reimbursement','2026-02-18'),
(2,2000.00,'Expense',11,'Gym membership and checkup','2026-02-20');


SELECT * FROM users;

SELECT * FROM categories;

SELECT * FROM transactions;

SELECT COUNT(*) AS total_transactions FROM transactions;
