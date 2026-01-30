# ENTRY X - Professional Event Management System

## 🎓 Project Overview
ENTRY X is an **Ultra-Modern, Enterprise-Grade Event Management System** designed for educational institutions. The platform provides seamless event creation, student registration, QR-based attendance tracking, and comprehensive analytics dashboards.

---

## 🏗️ System Architecture

### Technology Stack
- **Backend**: PHP 8.x with OOP (Object-Oriented Programming)
- **Database**: MySQL with PDO (Prepared Statements)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Authentication**: Session-based + Google OAuth 2.0
- **Security**: Password hashing (bcrypt), CSRF protection, SQL injection prevention
- **UI Framework**: Custom "Void & Crimson" design system with glassmorphism

### Key Features
1. **Multi-Tier User System**
   - Super Admin (Full system control)
   - Event Admin (Event-specific management)
   - Internal Students (College students/staff)
   - External Participants (Guests with ID verification)

2. **QR-Based Check-In System**
   - Dynamic QR code generation per registration
   - Real-time attendance tracking
   - Secure token-based validation

3. **Event Management**
   - Create and manage multiple events
   - Set capacity limits and monetization
   - Track registrations and attendance in real-time

4. **Results & Hall of Fame**
   - Publish event winners
   - Display runner-ups and special mentions
   - Public results showcase

5. **Password Recovery**
   - Email-based reset system
   - Time-limited reset tokens
   - Localhost development mode with direct links

---

## 📂 Project Structure

```
EntryX/
├── api/
│   └── auth.php                 # Authentication API endpoints
├── assets/
│   ├── css/
│   │   └── style.css           # Main styling (Void & Crimson theme)
│   └── uploads/                # User-uploaded files
├── classes/
│   ├── Database.php            # Database connection handler
│   ├── User.php               # User management
│   └── Event.php              # Event operations
├── config/
│   ├── db_connect.php         # Database configuration
│   └── google_config.php      # Google OAuth configuration
├── includes/
│   ├── header.php             # Global header & navigation
│   └── footer.php             # Global footer
├── pages/
│   ├── admin_login.php        # Administrator login portal
│   ├── user_login.php         # Student/external login
│   ├── register.php           # User registration
│   ├── forgot_password.php    # Password recovery
│   ├── admin_dashboard.php    # Admin control panel
│   ├── student_dashboard.php  # Student event browser
│   └── results.php            # Hall of Fame
└── index.php                   # Landing page

```

---

## 🔐 User Roles & Permissions

### 1. Super Admin
- **Access**: Complete system control
- **Capabilities**:
  - Create/delete event administrators
  - Manage all events across the system
  - View global analytics
  - Publish results and winners

### 2. Event Admin
- **Access**: Event-specific management
- **Capabilities**:
  - Create and manage assigned events
  - Track registrations and attendance
  - Publish event results

### 3. Internal Users (Students/Staff)
- **Access**: College community
- **Capabilities**:
  - Register for events using College ID
  - View registered events
  - Generate QR codes for check-in
  - View event history

### 4. External Participants
- **Access**: Guest users
- **Capabilities**:
  - Register with phone number and ID proof
  - Participate in public events
  - Same QR check-in system as internal users

---

## 🚀 Getting Started

### Prerequisites
- XAMPP/WAMP/LAMP (PHP 8.x + MySQL)
- Modern web browser (Chrome/Firefox/Edge)

### Installation Steps

1. **Clone/Download Project**
   ```bash
   # Place the EntryX folder in your htdocs directory
   C:/xampp/htdocs/Project/EntryX/
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `entryx`
   - Import the schema (tables will auto-create on first run)

3. **Configuration**
   ```php
   // config/db_connect.php
   $host = 'localhost';
   $dbname = 'entryx';
   $username = 'root';
   $password = '';  // Default XAMPP password is empty
   ```

4. **Google OAuth (Optional)**
   - Copy `config/google_config.example.php` to `google_config.php`
   - Add your Google Client ID and Secret
   - Configure redirect URI in Google Console

5. **Start Application**
   - Start XAMPP Apache & MySQL
   - Navigate to: `http://localhost/Project/EntryX/`

---

## 🎨 Design System

### Color Palette: "Void & Crimson"
- **Primary Brand**: `#ff1f1f` (Crimson Red)
- **Background**: `#030303` (Deep Void)
- **Surface**: `#0a0a0a` (Elevated Surface)
- **Text Primary**: `#ffffff` (Pure White)
- **Text Muted**: `#94a3b8` (Slate Gray)

### Design Principles
1. **Glassmorphism**: Frosted glass panels with backdrop blur
2. **Deep Shadows**: Multi-layer shadows for depth perception
3. **Micro-Animations**: Smooth transitions and hover effects
4. **Scroll Reveals**: IntersectionObserver-based animations
5. **Responsive Grid**: Mobile-first adaptive layouts

---

## 🔒 Security Features

### Implemented Protections
1. **SQL Injection Prevention**
   - All queries use PDO prepared statements
   - Parameterized inputs throughout

2. **Password Security**
   - Bcrypt hashing (cost factor: 12)
   - Minimum complexity requirements
   - Server-side validation

3. **Session Management**
   - Secure session handling
   - Role-based access control (RBAC)
   - Session timeout implementation

4. **XSS Prevention**
   - `htmlspecialchars()` on all user outputs
   - Content Security Policy headers

5. **File Upload Security**
   - Type validation (images, PDFs only)
   - Size limits (2MB max)
   - Secure filename sanitization

6. **Admin-Only Google OAuth**
   - Administrators MUST use password login
   - Google OAuth restricted to students/externals only
   - Prevents unauthorized admin access

---

## 📊 Database Schema

### Main Tables

#### `users`
```sql
id, name, email, password, role, college_id, phone, 
id_proof, google_id, created_at
```

#### `events`
```sql
id, name, description, event_date, venue, capacity, 
is_paid, price, created_by, created_at
```

#### `registrations`
```sql
id, event_id, user_id, qr_token, attended, 
registered_at, attended_at
```

#### `results`
```sql
id, event_id, winner_name, runner_up_name, 
consolation_prize, description, published_at
```

#### `password_resets`
```sql
id, email, token, expires_at, created_at
```

---

## 🎯 API Endpoints

### Authentication (`/api/auth.php`)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `?action=register` | POST | User registration |
| `?action=login` | POST | User login (all roles) |
| `?action=logout` | GET | Session termination |
| `?action=forgot_password` | POST | Password reset request |
| `?action=reset_password` | POST | Password reset execution |
| `?action=google_login` | GET | Google OAuth initiation |
| `?action=google_callback` | GET | Google OAuth callback |

---

## 📱 User Flows

### Student Registration Flow
1. Navigate to `/pages/register.php`
2. Select "College Student / Staff"
3. Fill form (Name, Email, Password, College ID)
4. Submit → Auto-login → Redirect to dashboard

### Event Registration Flow
1. Login as student
2. Browse available events on dashboard
3. Click "Register Now"
4. Confirm registration via SweetAlert2
5. QR code generated instantly
6. View ticket in "My Registrations"

### Admin Event Creation Flow
1. Login as admin via `/pages/admin_login.php`
2. Navigate to dashboard
3. Click "Create New Event"
4. Fill event details (Name, Date, Venue, Capacity, Price)
5. Submit → Event goes live immediately
6. Students can now register

### Result Publishing Flow
1. Admin selects event from dashboard table
2. Click "Publish Results"
3. Enter winner, runner-up, special mentions
4. Submit → Results appear in Hall of Fame
5. Public can view at `/pages/results.php`

---

## 🛠️ Key Files Explained

### `assets/css/style.css`
- **Purpose**: Centralized styling system
- **Contains**: CSS variables, component styles, animations
- **Design**: Void & Crimson theme with glassmorphism

### `classes/User.php`
- **Purpose**: User management logic
- **Methods**:
  - `register()`: Create new user
  - `login()`: Authenticate user
  - `createOrLoginGoogleUser()`: Google OAuth handler
  - `updatePassword()`: Password reset
  - `getAllUsers()`: Admin user listing

### `classes/Event.php`
- **Purpose**: Event operations
- **Methods**:
  - `createEvent()`: Add new event
  - `getAllEvents()`: List all events
  - `registerUser()`: User registration for event
  - `generateQR()`: Create QR token
  - `markAttendance()`: Check-in user

### `includes/header.php`
- **Purpose**: Global navigation & layout
- **Features**:
  - Sticky navigation bar
  - Dynamic user info display
  - Role-based menu items
  - Scroll reveal script initialization

---

## 🐛 Known Limitations & Future Enhancements

### Current Limitations
1. **Email Sending**: Requires mail server configuration (disabled on localhost)
2. **File Storage**: Local uploads (consider cloud storage for production)
3. **Mobile Optimization**: Partially responsive (desktop-first design)

### Planned Features
- [ ] Email notifications for event updates
- [ ] SMS integration for OTP verification
- [ ] Advanced analytics dashboard with charts
- [ ] Event calendar with filters
- [ ] Batch QR code scanner for admins
- [ ] Export attendance reports (Excel/PDF)
- [ ] Multi-language support
- [ ] Dark/Light theme toggle

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue**: "Cannot connect to database"
- **Fix**: Ensure MySQL is running in XAMPP
- Verify `config/db_connect.php` credentials
- Check if database `entryx` exists

**Issue**: "Google Sign-In not working"
- **Fix**: Configure `google_config.php` with valid credentials
- Verify redirect URI in Google Console
- Ensure HTTPS (or localhost exception)

**Issue**: "Admin login failing"
- **Fix**: Ensure you're using the correct admin URL: `/pages/admin_login.php`
- Verify admin account exists in database with role `super_admin` or `event_admin`
- Reset password if needed

**Issue**: "QR code not displaying"
- **Fix**: Check if registration exists in database
- Verify `qr_token` is generated
- Ensure session is active

---

## 📜 License & Credits

### Development
- **Framework**: Custom PHP MVC-inspired architecture
- **UI Design**: Original "Void & Crimson" design system
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Outfit, Plus Jakarta Sans)
- **Alerts**: SweetAlert2

### License
Educational project - Free to use and modify for academic purposes.

---

## 🎓 Submission Checklist

### Project Completeness (60% Target Achieved)

✅ **Core Functionality (20%)**
- [x] User registration and authentication
- [x] Multi-role system (Super Admin, Event Admin, Students, External)
- [x] Event CRUD operations
- [x] QR-based registration and check-in

✅ **Security (10%)**
- [x] Password hashing and validation
- [x] SQL injection prevention (PDO)
- [x] XSS protection
- [x] Session management
- [x] Admin-only restrictions

✅ **User Experience (15%)**
- [x] Professional UI/UX design
- [x] Responsive navigation
- [x] Form validation (client & server)
- [x] Real-time feedback (SweetAlert2)
- [x] Smooth animations

✅ **Admin Features (10%)**
- [x] Dedicated admin login portal
- [x] Event management dashboard
- [x] User management tools
- [x] Results publishing system
- [x] Attendance tracking

✅ **Documentation (5%)**
- [x] README with installation guide
- [x] Code comments
- [x] Database schema
- [x] API documentation

### Recommended Additions for 100%
- [ ] Unit tests
- [ ] API rate limiting
- [ ] Advanced search/filters
- [ ] Export functionality
- [ ] Email integration
- [ ] Performance optimization

---

## 📸 Screenshots

### Landing Page
Premium hero section with dynamic call-to-action buttons and feature highlights.

### Admin Terminal
High-security login portal with elevated design for administrators.

### Student Dashboard
Event browser with registration cards and QR ticket display.

### Hall of Fame
Results showcase with golden record badges and winner profiles.

---

**Version**: 1.0.0  
**Last Updated**: January 2026  
**Status**: Production-Ready (Academic Submission)

---

## Quick Start Commands

```bash
# Start XAMPP services
sudo /opt/lampp/lampp start  # Linux
# OR use XAMPP Control Panel on Windows

# Access application
http://localhost/Project/EntryX/

# Admin Login
http://localhost/Project/EntryX/pages/admin_login.php

# Default Admin Credentials (Create manually in DB)
Email: admin@entryx.system
Password: [Set in database with bcrypt hash]
```

---

**For any queries, refer to the inline code comments or contact the development team.**
