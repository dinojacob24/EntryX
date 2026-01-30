# ENTRY X - Quick Installation Guide

## 🚀 5-Minute Setup

### Step 1: Extract Project
```
Place the EntryX folder in:
C:\xampp\htdocs\Project\EntryX\
```

### Step 2: Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** 
- Start **MySQL**

### Step 3: Setup Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose file: `database_setup.sql`
4. Click **Go** button
5. Wait for success message

### Step 4: Test Application
1. Open browser: `http://localhost/Project/EntryX/`
2. You should see the professional landing page

### Step 5: Login as Admin
```
URL: http://localhost/Project/EntryX/pages/admin_login.php
Email: admin@entryx.system
Password: Admin@123
```

---

## 🎯 Default Credentials

### Super Admin
- **Email**: `admin@entryx.system`
- **Password**: `Admin@123`
- **Access**: Full system control

### Event Admin
- **Email**: `coordinator@entryx.system`
- **Password**: `EventAdmin@123`
- **Access**: Event management only

---

## 📝 Creating Test Student Account

1. Go to: `http://localhost/Project/EntryX/pages/register.php`
2. Select: **College Student / Staff**
3. Fill details:
   - Name: John Doe
   - Email: john@college.edu
   - Password: Student@123
   - College ID: 23MCA001
4. Click **Finalize Initialization**
5. Auto-login to student dashboard

---

## ⚡ Quick Actions

### Register for an Event (Student)
1. Login as student
2. Browse available events
3. Click **Register Now**
4. View QR ticket in **My Registrations**

### Create New Event (Admin)
1. Login as admin
2. Click **Create New Event**
3. Fill event details
4. Submit → Event goes live

### Publish Results (Admin)
1. Login as admin
2. Find event in table
3. Click **Publish Results**
4. Enter winners
5. Submit → Appears in Hall of Fame

---

## 🔧 Troubleshooting

### Database Connection Error
```php
// Check config/db_connect.php
$host = 'localhost';
$dbname = 'entryx';     // Must match database name
$username = 'root';
$password = '';         // Default XAMPP is empty
```

### Page Not Found (404)
```
Ensure URL structure:
✅ http://localhost/Project/EntryX/
❌ http://localhost/EntryX/
```

### Admin Login Not Working
- Verify you're using: `/pages/admin_login.php`
- NOT: `/pages/user_login.php` (student portal)
- Check database has admin user with role `super_admin`

### Forgot Password Not Sending Email
- **Expected on localhost** - Email requires mail server
- Click the **development mode link** shown in the alert
- Direct password reset link will appear

---

## 📊 Project Status

**Completion**: 60% (Submission Ready)

✅ **Implemented**:
- Multi-role authentication system
- Event CRUD operations
- QR-based registration & check-in
- Admin & student dashboards
- Results publishing (Hall of Fame)
- Password recovery system
- Google OAuth integration
- Professional UI/UX design
- Security features (SQL injection, XSS, CSRF protection)

🔄 **Future Enhancements** (40%):
- Email notifications
- Advanced analytics with charts
- Batch QR scanner
- Export reports (PDF/Excel)
- Mobile app integration
- SMS OTP verification

---

## 📞 Need Help?

1. Check `README.md` for detailed documentation
2. Review code comments in files
3. Inspect browser console for errors (F12)
4. Check XAMPP error logs

---

## ✅ Testing Checklist

Before submission, verify:
- [ ] Database setup successful
- [ ] Admin login works
- [ ] Student login works
- [ ] Event creation works
- [ ] Student can register for event
- [ ] QR code displays
- [ ] Results page accessible
- [ ] Forgot password flow works
- [ ] Google login configured (optional)

---

**Good luck with your project submission!** 🎓
