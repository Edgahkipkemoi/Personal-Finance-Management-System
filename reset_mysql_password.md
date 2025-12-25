# 🔐 MySQL Root Password Reset Guide

## Personal Finance Management System
**Student:** Edgah Kipkemoi (22/06846)

---

## 🚀 **Quick Reset (Ubuntu/Linux)**

### Method 1: Reset to No Password (Easiest)

```bash
# Step 1: Stop MySQL
sudo systemctl stop mysql

# Step 2: Start MySQL in safe mode
sudo mysqld_safe --skip-grant-tables --skip-networking &

# Step 3: Connect without password
mysql -u root

# Step 4: Reset password (in MySQL prompt)
USE mysql;
UPDATE user SET authentication_string = '' WHERE user = 'root';
UPDATE user SET plugin = 'mysql_native_password' WHERE user = 'root';
FLUSH PRIVILEGES;
EXIT;

# Step 5: Kill safe mode and restart MySQL
sudo pkill mysqld
sudo systemctl start mysql

# Step 6: Test connection
mysql -u root
```

### Method 2: Set New Password

```bash
# Step 1: Stop MySQL
sudo systemctl stop mysql

# Step 2: Start MySQL in safe mode
sudo mysqld_safe --skip-grant-tables --skip-networking &

# Step 3: Connect and set new password
mysql -u root

# In MySQL prompt:
USE mysql;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';
FLUSH PRIVILEGES;
EXIT;

# Step 4: Restart MySQL normally
sudo pkill mysqld
sudo systemctl start mysql

# Step 5: Test with new password
mysql -u root -p
```

---

## 🛠️ **Alternative: Use sudo mysql**

If the above doesn't work, try:

```bash
# This often works on Ubuntu
sudo mysql

# Then in MySQL:
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
EXIT;
```

---

## ✅ **Verify Reset Worked**

```bash
# Try connecting without password
mysql -u root

# You should see:
# Welcome to the MySQL monitor...
# mysql>
```

---

## 🚀 **After Password Reset**

Once you can connect to MySQL, run our setup:

```bash
php setup_local_mysql.php
```

This will:
- ✅ Create the database
- ✅ Create all tables
- ✅ Add default categories
- ✅ Configure the system

---

## 🆘 **If Still Having Issues**

Try this complete reset:

```bash
# Remove MySQL completely
sudo apt remove --purge mysql-server mysql-client mysql-common mysql-server-core-* mysql-client-core-*
sudo rm -rf /etc/mysql /var/lib/mysql
sudo apt autoremove
sudo apt autoclean

# Reinstall MySQL
sudo apt update
sudo apt install mysql-server

# During installation, you can set a new root password
# Or leave it empty for no password
```