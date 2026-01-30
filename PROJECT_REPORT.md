# College Event Management System - Project Report
## MCA Mini Project Submission

### 1. System Architecture
The system follows a modular MVC-like architecture using PHP, MySQL, and vanilla JavaScript (no heavy frontend frameworks to ensure ease of deployment and explanation).

**Modules:**
- **Authentication Layer**: Handles Role-Based Access Control (RBAC) data sanitization.
- **Core Logic Layer (`classes/`)**: Encapsulates business logic for Users, Events, and Registrations.
- **Presentation Layer (`pages/`)**: Responsive UI built with CSS Variables and Glass-morphism design.
- **API Layer (`api/`)**: JSON-based endpoints for AJAX requests (Scanner, Registration, Auth).

### 2. Database Schema
The database `entryx` consists of 5 main tables:
1. `users`: Stores participants, admins, and student IDs.
2. `events`: Stores event details, capacity, and payment settings (GST).
3. `registrations`: Links Users to Events, stores `qr_token` and payment stats.
4. `attendance_logs`: Tracks entry/exit timestamps to calculate "Current Inside".
5. `results`: Stores event winners and published results.

*(See `database/mca_schema.sql` for full SQL)*

### 3. API Flow Explanation
**QR Scanning Flow (`scanner.php` -> `api/attendance.php`):**
1. **Scan**: Frontend uses `html5-qrcode` to read the QR Token.
2. **Request**: POST `{qr_token, event_id}` to API.
3. **Validation**: API checks if token exists and belongs to the correct event.
4. **State Check**: API checks `attendance_logs` for this user:
   - If *No Log* OR *Last Log Exited* -> Create NEW Entry Log (`status='inside'`).
   - If *Last Log Inside* -> Update Log with `exit_time` (`status='exited'`).
5. **Response**: Returns JSON with entry/exit type and User Name.

### 4. Project Uniqueness & Key Features
1. **Dynamic entry/exit tracking**: Unlike simple check-in systems, this tracks *duration* and current occupancy using paired Entry/Exit timestamps.
2. **Hybrid Registration System**:
   - **Internal**: Validates using College ID.
   - **External**: Requires ID Proof upload and separate phone validation.
3. **Smart GST Calculation**: Events can be flagged as "Paid" with optional GST, automatically calculating the breakdown during registration.
4. **Live Dashboard**: Coordinators see real-time "Inside vs Registered" counts to manage venue capacity.

### 5. Future Enhancements
- Payment Gateway Integration (Razorpay/Stripe).
- Email/SMS Notifications for results.
- Certificate Generation.

---
### Setup Instructions
1. Import `database/mca_schema.sql` or run `php setup/install_mca.php`.
2. Configure `config/db.php`.
3. Login using:
   - **Super Admin**: `admin@entryx.com` / `password`
   - **Student/External**: Register via `register.php`.
