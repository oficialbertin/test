# Umuganda Connect USSD System

A USSD-based system for managing Umuganda community service events in Rwanda.

## Features

- Citizen Registration via USSD
- Event Management
- Attendance Tracking
- Feedback Collection
- Admin Dashboard
- Reports Generation

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP 
- USSD Gateway Integration

## Installation

1. Clone the repository to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\Umuganda_Connect_USSD
   ```

2. Import the database:
   - Open phpMyAdmin
   - Create a new database named `umuganda_connect`
   - Import the `database.sql` file

3. Configure the database connection:
   - Open `includes/config.php`
   - Update database credentials if needed

4. Configure USSD Gateway:
   - Update `sms.php` with your USSD gateway credentials
   - Set the correct callback URL in your USSD gateway settings

## USSD Menu Structure

```
*384*5617# - Main Menu
├── 1. Register Citizen
├── 2. View Upcoming Events
├── 3. Confirm Attendance
├── 4. Provide Feedback
└── 99. Exit
```

## Admin Access

- URL: `http://localhost/Umuganda_Connect_USSD/admin`
- Default credentials:
  - Username: admin
  - Password: admin123

## Directory Structure

```
Umuganda_Connect_USSD/
├── admin/
│   ├── pages/
│   │   ├── dashboard.php
│   │   ├── events.php
│   │   ├── citizens.php
│   │   ├── attendance.php
│   │   └── reports.php
│   └── assets/
├── includes/
│   ├── config.php
│   ├── db.php
│   └── util.php
├── menu.php
├── sms.php
└── database.sql
```

## Security Notes

1. Change default admin credentials after first login
2. Keep your USSD gateway credentials secure
3. Regularly backup the database
4. Update PHP and MySQL to latest stable versions

## Authors

- 22RP01881  Yvette UWUMUKIZA
- 22RP03951  Bertin HAKIZAYEZU

## Support

For technical support or questions, please contact:

- Email: oficialbertin@gmail.com / yvetteuwumukiza99@gmail.com
- Phone: +250781065112 / +250790237325


