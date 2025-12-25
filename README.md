# Personal Finance Management System

**Student:** Edgah Kipkemoi  
**Registration No:** 22/06846  
**Course:** BSD 3106 - Bachelor of Software Development  
**Supervisor:** Griffin Kenga  
**Date:** 6/12/2025

## Overview
A comprehensive web-based Personal Finance Management System that helps users track expenses, manage budgets, and generate insightful financial reports. Built as part of the BSD 3106 coursework, this system addresses the need for accessible financial tracking tools for students, young professionals, and individuals seeking better financial discipline.

## Features
- **Expense Management:** Add, edit, delete, and categorize expenses
- **Budget Planning:** Set monthly budgets with visual progress tracking
- **Financial Reports:** Interactive charts and detailed analytics
- **Category Management:** Custom expense categories with default options
- **Data Export:** CSV export functionality for external analysis
- **User Authentication:** Secure login and registration system
- **Responsive Design:** Mobile-friendly interface using Bootstrap
- **Real-time Analytics:** Dashboard with spending insights

## Technology Stack
- **Frontend:** HTML5, CSS3, JavaScript ES6+, Bootstrap 5.3
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **Charts:** Chart.js for data visualization
- **Development Environment:** XAMPP/WAMP/LAMP

## Quick Start

### Option 1: Automated Installation (Recommended)
1. Download and extract the system files
2. Place in your web server directory (e.g., `htdocs` for XAMPP)
3. Navigate to `http://localhost/personal-finance-system/install.php`
4. Follow the installation wizard
5. Delete `install.php` after successful installation

### Option 2: Manual Installation
1. Install XAMPP/WAMP/LAMP stack
2. Create database named `personal_finance`
3. Import `database/schema.sql`
4. Configure database connection in `config/database.php`
5. Access via `http://localhost/personal-finance-system`

## System Requirements

### Minimum Requirements
- **Web Server:** Apache/Nginx
- **PHP:** 7.4+ (with PDO, PDO_MySQL extensions)
- **Database:** MySQL 5.7+ or MariaDB 10.2+
- **Browser:** Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- **Storage:** 100MB disk space
- **RAM:** 512MB minimum

### Recommended Requirements
- **PHP:** 8.0+
- **MySQL:** 8.0+
- **RAM:** 1GB+
- **SSL Certificate:** For production deployment

## Project Structure
```
personal-finance-system/
├── assets/                 # Static assets (CSS, JS)
├── auth/                   # Authentication scripts
├── config/                 # Configuration files
├── database/               # Database schema and migrations
├── documentation/          # Technical and user documentation
├── includes/               # Shared PHP includes
├── logs/                   # Application logs
├── uploads/                # File uploads directory
├── *.php                   # Main application pages
├── install.php             # Installation wizard
└── README.md              # This file
```

## Key Features Explained

### Dashboard
- Monthly expense overview
- Budget vs. actual spending comparison
- Recent transactions list
- Category-wise spending breakdown
- Visual progress indicators

### Expense Management
- Quick expense entry form
- Categorized expense tracking
- Date-based filtering
- Bulk operations support
- Expense history with pagination

### Budget Management
- Monthly budget setting
- Visual progress tracking
- Over-budget alerts
- Historical budget comparison
- Budget vs. actual analysis

### Reports & Analytics
- Interactive pie charts for category breakdown
- Line charts for spending trends
- Monthly and weekly report views
- Detailed statistical summaries
- Exportable data tables

### Data Export
- CSV format export
- Complete transaction history
- Summary statistics included
- Excel-compatible format

## Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- Session-based authentication
- Input validation and sanitization
- Error logging and monitoring

## Development Methodology
This project follows the Agile development methodology with:
- Iterative development cycles
- User feedback integration
- Continuous testing and refinement
- Modular code architecture
- Version control with Git

## Academic Context
This system was developed as part of the BSD 3106 coursework focusing on:
- Software requirement specification (SRS)
- System design specifications (SDS)
- Database design and implementation
- User interface design
- Testing and deployment strategies
- Technical documentation

## Documentation
- **User Manual:** `documentation/USER_MANUAL.md`
- **Technical Documentation:** `documentation/TECHNICAL_DOCUMENTATION.md`
- **Database Schema:** `database/schema.sql`
- **Installation Guide:** Built-in installation wizard

## Testing
The system has been tested for:
- Functionality across all modules
- Cross-browser compatibility
- Responsive design on various devices
- Data integrity and security
- Performance under normal load

## Future Enhancements
- Mobile application development
- API for third-party integrations
- Advanced reporting features
- Multi-currency support
- Automated backup systems
- Email notification system
- Recurring expense tracking
- Financial goal setting

## Support & Maintenance
- Regular security updates
- Bug fixes and improvements
- Feature enhancements based on user feedback
- Documentation updates
- Performance optimizations

## License
This project is developed for academic purposes as part of the BSD 3106 coursework.

## Contact Information
- **Developer:** Edgah Kipkemoi
- **Registration:** 22/06846
- **Course:** BSD 3106 - Bachelor of Software Development
- **Supervisor:** Griffin Kenga
- **Institution:** [Your Institution Name]

## Acknowledgments
- Supervisor Griffin Kenga for guidance and support
- Course instructors for technical knowledge
- Fellow students for feedback and testing
- Open source community for tools and libraries used

---

**Note:** This system is designed for educational purposes and demonstrates practical application of software development principles learned in the BSD 3106 course.