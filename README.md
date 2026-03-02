# 💰 Personal Expense Tracker
### For Students & Corporates | PHP + MySQL + Bootstrap 5

> A full-stack web application to track personal income and expenses, built as a **DPU MCA Semester 2 – DBMS Project**.
> **Developer:** Suraj Gupta

---

## 📌 Project Overview

**Personal Expense Tracker** is a web-based financial management tool that allows users to:
- Register as a **Student** or **Corporate** user
- Log **Income** and **Expense** transactions
- View a **Dashboard** with real-time totals (Income, Expense, Balance)
- Browse and filter their complete **Transaction History**
- Delete individual transactions

---

## 🎯 Features

| Feature | Description |
|---|---|
| 🔐 User Authentication | Register & Login with hashed passwords (BCrypt) |
| 👥 User Roles | Student and Corporate role selection at registration |
| ➕ Add Transactions | Add Income or Expense with category, amount, date, and description |
| 📊 Dashboard | Live totals: Total Income, Total Expense, Remaining Balance |
| 📈 Expense Ratio Bar | Visual progress bar showing spending vs income |
| 📋 Transaction History | Full list with filters (Type, Category, Date Range) |
| 🗑️ Delete Transactions | Remove any transaction with confirmation |
| 🔒 Secure Sessions | PHP Sessions with session regeneration & secure logout |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 7.4+ (Vanilla) |
| **Database** | MySQL 5.7+ / MySQL 8.0+ |
| **DB Extension** | `mysqli` (Prepared Statements) |
| **Frontend** | HTML5, Bootstrap 5.3, Bootstrap Icons |
| **Styling** | Custom CSS (Inter font, gradient cards, responsive) |
| **Server** | Apache (XAMPP / WAMP / LAMP) |

---

## 📂 File Structure

```
expense_tracker/
│
├── DB_SETUP.txt          → Full SQL script (Database + Tables + Sample Data)
├── config.php            → MySQL database connection
├── index.php             → Login page (Landing page)
├── register.php          → User registration (Student / Corporate)
├── dashboard.php         → Main dashboard with financial summary
├── add_transaction.php   → Form to add income or expense
├── history.php           → Transaction history with filters & delete
├── logout.php            → Secure logout
├── style.css             → Custom CSS styling
└── README.md             → This file
```

---

## 🗄️ Database Schema

### Database: `expense_tracker`

#### `users` Table
| Column | Type | Description |
|---|---|---|
| `id` | INT, PK, AUTO_INCREMENT | Unique user ID |
| `username` | VARCHAR(100) | Full name |
| `email` | VARCHAR(150), UNIQUE | Login email |
| `password` | VARCHAR(255) | BCrypt hashed password |
| `user_role` | ENUM('Student','Corporate') | User role |
| `created_at` | TIMESTAMP | Registration time |

#### `categories` Table
| Column | Type | Description |
|---|---|---|
| `id` | INT, PK, AUTO_INCREMENT | Unique category ID |
| `name` | VARCHAR(100), UNIQUE | Category name |
| `type` | ENUM('Income','Expense','Both') | Category type |

**Predefined Categories:** Salary, Freelance, Business Income, Scholarship, Part-time Job, Investment, Food & Dining, Rent, Transport, Office Supplies, Healthcare, Education, Entertainment, Utilities, Shopping, Other

#### `transactions` Table
| Column | Type | Description |
|---|---|---|
| `id` | INT, PK, AUTO_INCREMENT | Unique transaction ID |
| `user_id` | INT, FK → users.id | Owner of the transaction |
| `amount` | DECIMAL(12,2) | Transaction amount (> 0) |
| `type` | ENUM('Income','Expense') | Transaction type |
| `category_id` | INT, FK → categories.id | Category reference |
| `description` | VARCHAR(255) | Optional notes |
| `date` | DATE | Transaction date |
| `created_at` | TIMESTAMP | Record creation time |

---

## 🚀 How to Run the Project (Step-by-Step)

### ✅ Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed (includes Apache + MySQL + PHP)

---

### Step 1 — Start XAMPP

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show a **green** running status ✅

---

### Step 2 — Copy Project Files

Copy the entire project folder into XAMPP's web root directory:

```
Copy From:  E:\DPU MCA\SEM 2\DBMS\DBMS Project\
Copy To:    C:\xampp\htdocs\expense_tracker\
```

Your folder should now look like:
```
C:\xampp\htdocs\expense_tracker\
    ├── config.php
    ├── index.php
    ├── register.php
    ├── dashboard.php
    ├── add_transaction.php
    ├── history.php
    ├── logout.php
    ├── style.css
    └── DB_SETUP.txt
```

---

### Step 3 — Set Up the Database

1. Open your browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click the **SQL** tab at the top
3. Open `DB_SETUP.txt`, **select all** the text and **copy** it
4. **Paste** it into the SQL input box in phpMyAdmin
5. Click the **Go** button (bottom right)

You should see the `expense_tracker` database appear in the left sidebar ✅

---

### Step 4 — (If Needed) Update Database Credentials

Open `config.php` and update the values to match your MySQL setup:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // Your MySQL username (default: root)
define('DB_PASS', '');       // Your MySQL password (default: blank in XAMPP)
define('DB_NAME', 'expense_tracker');
```

---

### Step 5 — Open in Browser

Open your browser and go to:
```
http://localhost/expense_tracker/
```

You will see the **Login Page** 🎉

---

## 🔑 Demo Login Credentials

These accounts are pre-loaded by `DB_SETUP.txt` for testing:

| Role | Email | Password |
|---|---|---|
| 🎓 **Student** | `suraj@gmail.com` | `password` |
| 💼 **Corporate** | `corporate@company.com` | `password` |

---

## 📸 Pages Overview

| Page | URL | Description |
|---|---|---|
| Login | `/index.php` | Landing page, email + password login |
| Register | `/register.php` | Create account, choose Student or Corporate |
| Dashboard | `/dashboard.php` | Income, Expense, Balance summary + last 8 transactions |
| Add Transaction | `/add_transaction.php` | Add income or expense |
| History | `/history.php` | Filter & browse all transactions, delete entries |

---

## 🔒 Security Implementations

- **BCrypt Hashing** — Passwords stored using `password_hash()`, verified with `password_verify()`
- **Prepared Statements** — All database queries use `$stmt->bind_param()` to prevent SQL Injection
- **XSS Prevention** — All user output is wrapped with `htmlspecialchars()`
- **Session Security** — Session ID regenerated on login, full session destroy on logout
- **Auth Guards** — All protected pages redirect to login if session is absent
- **Ownership Check** — DELETE queries include `AND user_id = ?` to prevent cross-user deletions

---

## 🐞 Troubleshooting

| Problem | Solution |
|---|---|
| **Cannot connect to database** | Make sure MySQL service is running in XAMPP |
| **404 Not Found** | Verify folder is inside `C:\xampp\htdocs\` |
| **Table doesn't exist error** | Re-run the full `DB_SETUP.txt` in phpMyAdmin SQL tab |
| **Login not working with demo credentials** | Make sure the `INSERT INTO users` section of `DB_SETUP.txt` was executed |
| **Blank white page** | Enable PHP error display or check Apache error log in XAMPP |

---

## 👨‍💻 Developer Info

| Field | Details |
|---|---|
| **Developer** | Suraj Gupta |
| **Project** | Personal Expense Tracker |
| **Course** | MCA Semester 2 — Database Management Systems |
| **University** | Dr. D.Y. Patil Vidyapeeth (DPU) |
| **Tech Stack** | PHP, MySQL, Bootstrap 5 |
| **Year** | 2026 |

---

> 📝 *This project was built for academic purposes as part of the DBMS course.*
