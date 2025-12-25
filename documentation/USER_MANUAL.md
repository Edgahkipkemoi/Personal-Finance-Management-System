# Personal Finance Management System - User Manual

**Created by:** Edgah Kipkemoi (22/06846)  
**Course:** BSD 3106 - Bachelor of Software Development  
**Supervisor:** Griffin Kenga  
**Date:** 6/12/2025

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Getting Started](#getting-started)
4. [Managing Expenses](#managing-expenses)
5. [Budget Management](#budget-management)
6. [Reports and Analytics](#reports-and-analytics)
7. [Categories](#categories)
8. [Data Export](#data-export)
9. [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements
- **Web Server:** Apache/Nginx with PHP 7.4+
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **Browser:** Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- **Internet Connection:** Required for initial setup and CDN resources

### Recommended Requirements
- **PHP:** 8.0+
- **MySQL:** 8.0+
- **RAM:** 512MB minimum
- **Storage:** 100MB for application files

## Installation

### Step 1: Download and Extract
1. Download the system files
2. Extract to your web server directory (e.g., `htdocs` for XAMPP)

### Step 2: Run Installer
1. Navigate to `http://localhost/personal-finance-system/install.php`
2. Follow the installation wizard:
   - Check system requirements
   - Configure database connection
   - Create admin user account
3. Delete `install.php` after successful installation

### Step 3: First Login
1. Go to `http://localhost/personal-finance-system/`
2. Login with your admin credentials
3. Start managing your finances!

## Getting Started

### Dashboard Overview
The dashboard provides a quick overview of your financial status:
- **Monthly Expenses:** Total spending for current month
- **Monthly Budget:** Your set budget limit
- **Remaining Budget:** Available budget (red if over budget)
- **Recent Expenses:** Last 5 transactions
- **Category Breakdown:** Visual spending distribution

### Navigation
- **Dashboard:** Main overview page
- **Expenses:** Add, view, and manage expenses
- **Budgets:** Set and monitor monthly budgets
- **Reports:** Detailed analytics and charts
- **Categories:** Manage expense categories

## Managing Expenses

### Adding an Expense
1. Go to **Expenses** page
2. Fill in the expense form:
   - **Amount:** Enter amount in KSh
   - **Description:** Brief description of expense
   - **Date:** Date of expense (defaults to today)
   - **Category:** Select appropriate category
3. Click **Add Expense**

### Viewing Expenses
- All expenses are listed in chronological order
- Use pagination to navigate through multiple pages
- Each expense shows date, category, description, and amount

### Deleting Expenses
1. Find the expense in the list
2. Click **Delete** button
3. Confirm deletion in the popup dialog

### Best Practices
- Add expenses immediately after spending
- Use descriptive but concise descriptions
- Choose the most appropriate category
- Review expenses regularly for accuracy

## Budget Management

### Setting a Monthly Budget
1. Go to **Budgets** page
2. Enter budget details:
   - **Budget Amount:** Monthly spending limit in KSh
   - **Month:** Select target month
   - **Year:** Select target year
3. Click **Set Budget**

### Budget Monitoring
- **Green Progress Bar:** Under 80% of budget used
- **Yellow Progress Bar:** 80-99% of budget used
- **Red Progress Bar:** Over budget
- **Budget Status:** Shows remaining amount or overspend

### Budget Alerts
The system provides visual indicators when:
- You're approaching your budget limit (80%+)
- You've exceeded your budget (100%+)
- You have no budget set for the current month

## Reports and Analytics

### Report Types
- **Monthly Reports:** Default view showing current month
- **Weekly Reports:** Select specific week and year

### Available Charts
1. **Spending by Category:** Pie chart showing expense distribution
2. **Daily Spending Trend:** Line chart showing daily expenses

### Report Filters
- **Period:** Choose between monthly or weekly view
- **Month/Week:** Select specific time period
- **Year:** Choose target year

### Summary Statistics
- **Total Spent:** Sum of all expenses in period
- **Transactions:** Number of expense entries
- **Average:** Average expense amount
- **Highest:** Largest single expense

### Category Breakdown Table
Detailed table showing:
- Category name
- Number of transactions
- Total amount spent
- Average per transaction
- Percentage of total spending

## Categories

### Default Categories
The system includes 8 default categories:
- Food & Dining
- Transportation
- Shopping
- Entertainment
- Bills & Utilities
- Healthcare
- Education
- Other

### Custom Categories
1. Go to **Categories** page
2. Enter category name
3. Click **Add Category**

### Managing Categories
- View all your custom categories
- See expense count and total spent per category
- Delete unused categories (only if no expenses exist)

### Category Guidelines
- Create specific categories for frequent expenses
- Use general categories for occasional expenses
- Keep category names short and descriptive
- Avoid creating too many similar categories

## Data Export

### Exporting to CSV
1. Go to **Expenses** page
2. Click **Export CSV** button
3. File downloads automatically with format: `expenses_YYYY-MM-DD.csv`

### CSV File Contents
- All your expense records
- Columns: Date, Category, Description, Amount, Created At
- Summary section with totals and export date

### Using Exported Data
- Open in Excel, Google Sheets, or similar
- Use for external analysis or backup
- Import into other financial software

## Troubleshooting

### Common Issues

#### Cannot Login
- **Check credentials:** Ensure email and password are correct
- **Clear browser cache:** Try clearing cookies and cache
- **Check database:** Ensure database connection is working

#### Expenses Not Saving
- **Check required fields:** All fields must be filled
- **Validate amount:** Must be positive number
- **Check date format:** Use valid date
- **Database connection:** Ensure database is accessible

#### Reports Not Loading
- **Check date filters:** Ensure valid month/year selected
- **Browser compatibility:** Use supported browser
- **JavaScript enabled:** Ensure JavaScript is enabled

#### Budget Not Updating
- **Refresh page:** Try refreshing the browser
- **Check date range:** Ensure expenses are in budget month
- **Clear cache:** Clear browser cache and reload

### Error Messages

#### "Database Connection Failed"
- Check database server is running
- Verify database credentials in `config/database.php`
- Ensure database exists

#### "Session Expired"
- Login again to refresh session
- Check if cookies are enabled
- Clear browser data if persistent

#### "Permission Denied"
- Check file permissions on server
- Ensure web server can write to necessary directories
- Contact system administrator

### Getting Help

#### System Information
- **Version:** 1.0.0
- **Developer:** Edgah Kipkemoi
- **Course:** BSD 3106
- **Institution:** [Your Institution]

#### Support Resources
- Check this user manual first
- Review system requirements
- Verify installation steps
- Contact system administrator

#### Reporting Issues
When reporting issues, include:
- Browser type and version
- Error message (if any)
- Steps to reproduce the problem
- Screenshots (if helpful)

## Security Best Practices

### Password Security
- Use strong passwords (8+ characters)
- Include numbers and special characters
- Don't share login credentials
- Change password regularly

### Data Protection
- Regular backups of expense data
- Export data periodically
- Keep system updated
- Use secure network connections

### Privacy
- System stores data locally on your server
- No data is sent to external services
- Access is restricted to logged-in users
- Session timeout for security

## Maintenance

### Regular Tasks
- **Weekly:** Review and categorize expenses
- **Monthly:** Set new budget and review reports
- **Quarterly:** Export data for backup
- **Annually:** Review categories and clean up unused ones

### System Maintenance
- Keep PHP and MySQL updated
- Monitor disk space usage
- Regular database backups
- Check error logs periodically

---

**End of User Manual**

For additional support or questions about this system, please contact the development team or your system administrator.