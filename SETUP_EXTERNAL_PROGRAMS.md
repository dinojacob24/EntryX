# Quick Setup Guide - External Programs Feature

## ✅ Installation Complete!

The External Programs feature has been successfully installed in your EntryX system.

## What Was Added

### 1. Database Tables
- ✅ `system_settings` - System configuration storage
- ✅ `external_programs` - Program management
- ✅ `admin_activity_log` - Admin action tracking
- ✅ Modified `users` table with external program tracking
- ✅ Modified `events` table with program type support

### 2. New Files
- ✅ `api/external_programs.php` - API endpoint for program management
- ✅ `database/external_programs_migration.sql` - Database schema
- ✅ `EXTERNAL_PROGRAMS_FEATURE.md` - Complete documentation

### 3. Updated Files
- ✅ `index.php` - Dynamic registration button
- ✅ `pages/admin_dashboard.php` - External Programs management UI

## 🚀 How to Use

### Step 1: Login as Super Admin
```
URL: http://localhost/Project/EntryX/pages/admin_login.php
Email: admin@entryx.system
Password: Admin@123
```

### Step 2: Navigate to External Programs Section
- Scroll down in the Admin Dashboard
- Find the "External Programs" section (green theme)
- You'll see the current status (DISABLED by default)

### Step 3: Create Your First Program
1. Click "Create Program" button
2. Fill in the form:
   - **Program Name**: e.g., "Tech Fest 2026"
   - **Description**: Brief description for participants
   - **Start Date**: When the program begins
   - **End Date**: When the program ends
   - **Max Participants**: Capacity limit (default: 500)
   - **Status**: Keep "Active" checked
3. Click "Create Program"

### Step 4: Enable Public Registration
1. Find your program in the list
2. Click the green "Enable Public Reg" button
3. Confirm the action
4. The status banner will turn green showing "ENABLED"

### Step 5: Verify on Landing Page
1. Open a new browser tab (or logout)
2. Visit: `http://localhost/Project/EntryX/`
3. You should now see the registration button with your program name!

## 🎯 Current System State

### Landing Page Buttons (When Logged Out)
- **Access Portal** - Always visible (for internal students and registered externals)
- **[Your Program Name]** - Only visible when you enable a program
- **Security Terminal** - Always visible
- **Admin Console** - Always visible

### Access Portal Purpose
The Access Portal is where:
- ✅ Internal students login to register for events
- ✅ External participants (who registered through your program) login
- ✅ Users access their dashboard to view and register for events

### Dashboard Access After Login
After logging in through Access Portal:
- Internal students → Student Dashboard
- External participants → Student Dashboard
- They can browse and register for available events

## 📊 Managing Programs

### View All Programs
- See list of all created programs
- Monitor participant counts
- Check active/inactive status

### Edit Program
- Click the edit (pen) icon
- Modify program details
- Save changes

### Delete Program
- Click the delete (trash) icon
- Only works if no participants registered
- Confirms before deletion

### Enable/Disable Registration
- **Enable**: Makes registration visible on landing page
- **Disable**: Hides registration button (click Disable in status banner)
- Only one program can be enabled at a time

## 🔒 Security Features

### Role-Based Access
- Only Super Admins can manage external programs
- API validates permissions on every request

### Activity Logging
- All actions are logged in `admin_activity_log` table
- Tracks: who, what, when, and from where (IP)

### Data Protection
- Cannot delete programs with registered participants
- Input validation on all forms
- SQL injection protection

## 🎨 User Experience Flow

### For External Participants
1. See custom program name on landing page
2. Click to register
3. Fill registration form
4. Receive account credentials
5. Login through Access Portal
6. Access Student Dashboard
7. Browse and register for events

### For Internal Students
1. Always see Access Portal button
2. Login with college credentials
3. Access Student Dashboard
4. Register for events
5. Not affected by external program settings

## 📝 Example Scenarios

### Scenario 1: Opening Registration for Tech Fest
```
1. Create program: "Tech Fest 2026 - External Registration"
2. Set dates: March 1-5, 2026
3. Max participants: 300
4. Enable public registration
5. Monitor registrations in real-time
6. When full or event starts, disable registration
```

### Scenario 2: Multiple Programs
```
1. Create "Workshop Series - January"
2. Create "Workshop Series - February"
3. Enable January program first
4. After January ends, disable it
5. Enable February program
6. Track participants separately for each
```

### Scenario 3: Emergency Closure
```
1. Unexpected issue requires closing registration
2. Click "Disable" in status banner
3. Registration immediately hidden
4. Existing registrations remain valid
5. Re-enable when ready
```

## 🔍 Troubleshooting

### Registration Button Not Showing
**Check:**
- Is a program enabled? (Green status banner)
- Are you logged out? (Button only shows when not logged in)
- Clear browser cache and refresh

### Cannot Create Program
**Check:**
- Are you logged in as Super Admin?
- Is the program name unique?
- Are all required fields filled?

### Participants Not Counted
**Check:**
- Did they complete registration?
- Check `users` table for `external_program_id` field
- Verify registration process is working

## 📚 Additional Resources

- **Full Documentation**: `EXTERNAL_PROGRAMS_FEATURE.md`
- **API Reference**: See `api/external_programs.php` comments
- **Database Schema**: `database/external_programs_migration.sql`

## 🎉 You're All Set!

The External Programs feature is now fully operational. You have complete control over:
- ✅ When external registration is visible
- ✅ What program name is displayed
- ✅ How many participants can register
- ✅ Tracking and managing all registrations

**Next Steps:**
1. Create your first external program
2. Enable public registration
3. Test the registration flow
4. Monitor participant registrations
5. Manage events through the admin dashboard

---

**Need Help?**
- Review the full documentation in `EXTERNAL_PROGRAMS_FEATURE.md`
- Check the admin activity log for detailed action history
- Verify database tables were created correctly

**Happy Managing! 🚀**
