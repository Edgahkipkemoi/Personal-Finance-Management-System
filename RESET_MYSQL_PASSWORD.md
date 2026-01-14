# 🔐 Reset MySQL Root Password - Step by Step Guide

## Method 1: Using sudo (Recommended for Ubuntu/Linux)

### Step 1: Stop MySQL Service
```bash
sudo systemctl stop mysql
```

### Step 2: Start MySQL in Safe Mode
```bash
sudo mysqld_safe --skip-grant-tables --skip-networking &
```

### Step 3: Connect to MySQL (No Password Needed)
```bash
mysql -u root
```

### Step 4: Reset the Password
Once you're in MySQL, run these commands:

```sql
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';
FLUSH PRIVILEGES;
EXIT;
```

**Replace `your_new_password` with your desired password, or leave it empty: `''`**

### Step 5: Stop Safe Mode and Restart MySQL
```bash
sudo killall mysqld
sudo systemctl start mysql
```

### Step 6: Test the New Password
```bash
mysql -u root -p
```
Enter your new password when prompted.

---

## Method 2: Simpler Alternative (Ubuntu/Debian)

### Step 1: Stop MySQL
```bash
sudo systemctl stop mysql
```

### Step 2: Create a Password Reset File
```bash
sudo nano /tmp/mysql-init.txt
```

Add this line (replace with your desired password):
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';
```

Save and exit (Ctrl+X, then Y, then Enter)

### Step 3: Start MySQL with Init File
```bash
sudo mysqld --init-file=/tmp/mysql-init.txt &
```

### Step 4: Wait a few seconds, then restart MySQL normally
```bash
sudo killall mysqld
sudo systemctl start mysql
```

### Step 5: Remove the temporary file
```bash
sudo rm /tmp/mysql-init.txt
```

### Step 6: Test
```bash
mysql -u root -p
```

---

## Method 3: Use Unix Socket Authentication (No Password Needed)

This is the easiest method for local development:

### Step 1: Login with sudo
```bash
sudo mysql
```

### Step 2: Change Authentication Method
```sql
USE mysql;
ALTER USER 'root'@'localhost' IDENTIFIED WITH auth_socket;
FLUSH PRIVILEGES;
EXIT;
```

Now you can always login with:
```bash
sudo mysql
```

No password needed!

---

## Method 4: Create a New User (If Root is Locked)

### Step 1: Login with sudo
```bash
sudo mysql
```

### Step 2: Create New Admin User
```sql
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Use the new user
```bash
mysql -u admin -p
```

---

## 🎯 Quick Fix for Your Project

After resetting your password, update your project's database config:

**File: `backend/config/database.php`**

```php
<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'personal_finance';
    private $username = 'root';  // or 'admin' if you created new user
    private $password = 'your_new_password';  // your new password here
    // ... rest of the code
}
?>
```

---

## ✅ Recommended Setup for Development

For local development, I recommend using **Method 3** (Unix Socket) because:
- ✅ No password to remember
- ✅ Secure (only works with sudo)
- ✅ Perfect for development

Then update your PHP config to use sudo access or create a specific user for your app.

---

## 🚀 After Resetting Password

Run this to set up your database:

```bash
# If using no password or socket auth
sudo mysql < database/schema.sql

# If using password
mysql -u root -p < database/schema.sql
```

---

## 💡 Common Issues

### "Access denied for user 'root'@'localhost'"
- Try: `sudo mysql` instead of `mysql -u root -p`
- Ubuntu uses socket authentication by default

### "Can't connect to MySQL server"
- Check if MySQL is running: `sudo systemctl status mysql`
- Start it: `sudo systemctl start mysql`

### "ERROR 1045 (28000)"
- Your password is wrong
- Follow Method 1 or 2 to reset it

---

## 📞 Need Help?

If you're still stuck, tell me:
1. Which method you tried
2. What error message you got
3. Your Ubuntu/Linux version

I'll help you fix it! 🛠️