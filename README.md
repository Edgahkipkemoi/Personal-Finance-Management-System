# Personal Finance Management System

A comprehensive web-based application for tracking expenses, managing budgets, and analyzing spending patterns.

**Student:** Edgah Kipkemoi  
**Registration No:** 22/06846  
**Course:** BSD 3106 - Bachelor of Software Development  
**Supervisor:** Griffin Kenga

## Features

- 💰 **Expense Tracking** - Add, view, and delete expenses with categories
- 📊 **Budget Management** - Set monthly budgets and monitor spending
- 📈 **Financial Reports** - Interactive charts and detailed analytics
- 📁 **Data Export** - Export expenses to CSV format
- 🔐 **User Authentication** - Secure login and registration
- 🔄 **Password Reset** - Forgot password functionality
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3
- **Backend:** PHP 8.3
- **Database:** MySQL 8.0
- **Charts:** Chart.js
- **Server:** PHP Built-in Server / Apache

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Setup Steps

1. **Clone or download the project**
   ```bash
   cd /path/to/your/projects
   ```

2. **Database is already configured**
   - Database: `personal_finance`
   - User: `pfm_user`
   - Password: `Pfm@2025Pass`
   - All tables created and ready

3. **Start the PHP server**
   ```bash
   php -S localhost:8000
   ```

4. **Access the application**
   ```
   http://localhost:8000/
   ```

5. **Register your account**
   - Click "Login / Register"
   - Fill in your details
   - Start tracking expenses!

## Project Structure

```
personal-finance-system/
├── frontend/              # HTML pages
│   ├── login.php         # Login/Register page
│   ├── dashboard.html    # Main dashboard
│   ├── expenses.html     # Expense management
│   ├── budgets.php       # Budget management
│   ├── reports.html      # Reports and analytics
│   ├── categories.php    # Category management
│   └── profile.html      # User profile
├── backend/              # PHP backend
│   ├── auth/            # Authentication
│   ├── api/             # JSON APIs
│   ├── expenses/        # Expense operations
│   ├── budgets/         # Budget operations
│   ├── categories/      # Category operations
│   └── config/          # Database configuration
├── assets/              # Static files
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── database/           # Database files
│   ├── schema.sql      # Database structure
│   └── demo_data.sql   # Sample data (optional)
├── documentation/      # Project documentation
│   ├── USER_MANUAL.md
│   └── TECHNICAL_DOCUMENTATION.md
└── index.html         # Landing page
```

## Usage

### Register/Login
1. Navigate to `http://localhost:8000/`
2. Click "Login / Register"
3. Create your account or login

### Add Expenses
1. Go to "Expenses" page
2. Fill in amount, description, date, and category
3. Click "Add Expense"

### Set Budget
1. Go to "Budgets" page
2. Enter budget amount and select month/year
3. Click "Set Budget"

### View Reports
1. Go to "Reports" page
2. Select period (monthly/weekly)
3. View charts and statistics

### Export Data
1. Go to "Expenses" page
2. Click "Export CSV"
3. File downloads automatically

## Database Configuration

Current configuration in `backend/config/database.php`:
- Host: `localhost`
- Database: `personal_finance`
- Username: `pfm_user`
- Password: `Pfm@2025Pass`

To change credentials, edit `backend/config/database.php`

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- Session-based authentication
- Input validation and sanitization

## API Endpoints

- `GET /backend/api/dashboard.php` - Dashboard data
- `GET /backend/api/expenses.php` - List expenses
- `GET /backend/api/categories.php` - List categories
- `GET /backend/api/budgets.php` - List budgets
- `POST /backend/auth/login.php` - User login
- `POST /backend/auth/register.php` - User registration
- `POST /backend/expenses/add.php` - Add expense
- `POST /backend/expenses/delete.php` - Delete expense

## Troubleshooting

### Can't connect to database
- Check MySQL is running: `sudo systemctl status mysql`
- Verify credentials in `backend/config/database.php`

### Registration not working
- Ensure database is set up correctly
- Check PHP error logs

### Pages not loading
- Make sure PHP server is running
- Check the correct port (8000)

## Future Enhancements

- Mobile application
- Recurring expenses
- Multiple currency support
- Budget alerts via email
- Data visualization improvements
- Export to PDF
- Multi-user support with roles

## License

This project is developed for academic purposes as part of BSD 3106 coursework.

## Contact

**Developer:** Edgah Kipkemoi  
**Registration:** 22/06846  
**Course:** BSD 3106  
**Supervisor:** Griffin Kenga

---

**© 2025 Personal Finance Management System**