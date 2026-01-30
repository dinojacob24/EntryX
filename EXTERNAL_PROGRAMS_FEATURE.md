# External Programs & Dynamic Registration Feature

## Overview
This feature allows **Super Admins** to control external participant registration visibility on the landing page. The system provides a comprehensive program management interface where admins can create, manage, and enable/disable external registration programs dynamically.

## Key Features

### 1. **Dynamic Registration Button**
- The "New Registration" button on the landing page (`index.php`) is now **conditionally displayed**
- Only visible when a Super Admin enables an external program
- Button text and tooltip are customizable based on the active program

### 2. **External Programs Management**
Super Admins can:
- Create multiple external programs with custom names and descriptions
- Set program duration (start/end dates)
- Define maximum participant limits
- Activate/deactivate programs
- Enable one program at a time for public registration
- Track participant counts in real-time

### 3. **Access Control**
- **Access Portal**: Available for all internal students and registered external users to login
- **Student Dashboard**: Accessible after login for event registration
- **External Registration**: Only visible when enabled by Super Admin

## Database Schema

### New Tables Created

#### `system_settings`
Stores global system configuration:
- `external_registration_enabled` - Controls landing page visibility
- `current_external_program_name` - Active program name
- `current_external_program_description` - Active program description

#### `external_programs`
Manages external registration programs:
- Program details (name, description, dates)
- Participant limits
- Active status
- Custom form fields (JSON)

#### `admin_activity_log`
Tracks all admin actions for audit purposes:
- Action type and description
- Affected tables and records
- IP address and timestamp

### Modified Tables

#### `users`
Added fields:
- `external_program_id` - Links user to the program they registered through
- `registration_source` - Tracks registration method (direct, external_program, google_oauth)

#### `events`
Added fields:
- `program_type` - Distinguishes regular events from external programs
- `is_external_registration_open` - Controls registration availability
- `external_program_details` - Additional program information

## How It Works

### For Super Admins

1. **Create an External Program**
   - Navigate to Admin Dashboard
   - Scroll to "External Programs" section
   - Click "Create Program"
   - Fill in program details:
     - Program Name (e.g., "Tech Fest 2026")
     - Description
     - Start/End Dates
     - Maximum Participants
   - Save the program

2. **Enable Public Registration**
   - Find the program in the list
   - Click "Enable Public Reg" button
   - Confirm the action
   - The "New Registration" button will now appear on the landing page

3. **Disable Public Registration**
   - Click the "Disable" button in the status banner
   - The registration button will be hidden from the landing page

4. **Monitor Registrations**
   - View participant counts in real-time
   - Track which program users registered through
   - Access admin activity logs

### For External Users

1. **When Registration is Enabled**
   - Visit the landing page
   - See the "New Registration" button (or custom program name)
   - Click to register for the active external program
   - Complete registration form
   - Receive access to the Access Portal

2. **After Registration**
   - Login through Access Portal
   - View and register for available events
   - Access student dashboard features

### For Internal Students

- Always have access to the Access Portal
- Can login and register for events anytime
- Not affected by external program settings

## API Endpoints

### `/api/external_programs.php`

**Actions:**
- `get_all` - Fetch all programs
- `get` - Get single program details
- `create` - Create new program
- `update` - Update existing program
- `delete` - Delete program (only if no participants)
- `toggle_status` - Activate/deactivate program
- `get_settings` - Get system settings
- `update_settings` - Update system settings
- `enable_external_registration` - Enable public registration for a program
- `disable_external_registration` - Disable public registration

**Authentication:** Super Admin only

## File Structure

```
EntryX/
├── api/
│   └── external_programs.php          # API for program management
├── database/
│   └── external_programs_migration.sql # Database migration script
├── pages/
│   └── admin_dashboard.php            # Enhanced with program management UI
└── index.php                          # Dynamic registration button
```

## Installation

1. **Run Database Migration**
   ```sql
   -- Execute in phpMyAdmin or MySQL client
   source database/external_programs_migration.sql;
   ```

2. **Verify Tables Created**
   - system_settings
   - external_programs
   - admin_activity_log

3. **Test the Feature**
   - Login as Super Admin
   - Create a test program
   - Enable public registration
   - Check landing page for registration button

## Security Features

1. **Role-Based Access Control**
   - Only Super Admins can manage external programs
   - API endpoints validate user role before processing

2. **Activity Logging**
   - All admin actions are logged with:
     - Admin ID and name
     - Action type and description
     - IP address
     - Timestamp

3. **Data Validation**
   - Program deletion blocked if participants exist
   - Only one program can be active for public registration
   - Input sanitization on all forms

## Use Cases

### Scenario 1: Annual Tech Fest
1. Admin creates "Tech Fest 2026" program
2. Sets dates: March 1-5, 2026
3. Max participants: 500
4. Enables public registration 2 months before event
5. External students register through landing page
6. Admin monitors registrations in real-time
7. Disables registration when capacity reached

### Scenario 2: Workshop Series
1. Admin creates multiple workshop programs
2. Enables one workshop at a time
3. After workshop ends, disables and enables next
4. Tracks participants per workshop
5. Analyzes attendance patterns

### Scenario 3: Emergency Closure
1. Unexpected situation requires closing registration
2. Admin clicks "Disable" button
3. Registration immediately hidden from landing page
4. Existing registrations remain valid
5. Can re-enable when situation resolved

## Benefits

### For Administration
- **Full Control**: Enable/disable registration instantly
- **Flexibility**: Create custom programs for different events
- **Tracking**: Monitor participant counts and sources
- **Audit Trail**: Complete log of all admin actions

### For External Participants
- **Clear Communication**: See program name and description
- **Easy Access**: Single button on landing page
- **Seamless Experience**: Register and access portal immediately

### For Internal Students
- **Unaffected Access**: Always can login and register
- **Consistent Experience**: No changes to existing workflow

## Troubleshooting

### Registration Button Not Showing
1. Check if external registration is enabled in admin dashboard
2. Verify system_settings table has correct values
3. Clear browser cache
4. Check database connection

### Cannot Enable Program
1. Ensure program status is "Active"
2. Check Super Admin permissions
3. Review browser console for errors
4. Verify API endpoint is accessible

### Participants Not Counted
1. Check users table for external_program_id field
2. Verify registration process sets program ID
3. Review database foreign key constraints

## Future Enhancements

Potential additions:
- Custom registration form fields per program
- Email notifications when registration opens
- Waiting list functionality
- Program-specific event access control
- Analytics dashboard for program performance
- Bulk participant management
- Export participant data

## Support

For issues or questions:
1. Check admin activity log for error details
2. Review database migration status
3. Verify API endpoint responses
4. Contact system administrator

---

**Version:** 1.0  
**Last Updated:** January 29, 2026  
**Author:** EntryX Development Team
