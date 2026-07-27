# Fiat Lux Academe Document Request Hub - Functions of the Proposed System
## Screenshots and Descriptions for Documentation

---

## Figure 1. Records Management System Login Page

**What to Show:** The login interface with Fiat Lux Academe branding and role selection buttons.

**Description:** 
The login page presents the authentication interface for the Fiat Lux Academe Document Request Hub, the proposed records management system. Users enter their credentials (username/student ID and password) to access the system. The page displays role-based quick-login options (Admin, Student, Registrar, Records Officer) at the bottom for convenient access. The system authenticates user identity and directs them to role-specific dashboards. This interface ensures only authorized personnel can access sensitive student records while maintaining security.

---

## Figure 2. Student Dashboard - Main Portal

**What to Show:** The student home page showing welcome message, active requests, and quick stats.

**Description:**
The student dashboard serves as the main portal after login, displaying a personalized welcome message ("Mabuhay, Louisse!") and comprehensive request overview. The interface shows:
- **Active Requests section:** Lists all current document requests with status (e.g., "Form 137 - ready_to_release")
- **Quick Stats:** Shows pending (0), completed (0), and total requests (1)
- **History section:** Displays past completed requests
- **Quick-action button:** "Request New Document" for submitting new requests

This streamlined layout allows students to easily track their document requests and submissions.

---

## Figure 3. Student Request Form - Document Submission

**What to Show:** The document request form with student information and document type selection.

**Description:**
The Student Request Portal allows students to submit new document requests. The form displays:
- **Pre-filled fields:** Student ID (202610) and Full Name (Louisse Alcala Chua) automatically populated from the system
- **Document Type dropdown:** Options include Form 137 (Permanent Record), Form 138 (Report Card), Certificate of Good Moral, and Diploma
- **Submit Request button:** To finalize the submission
- **Important Note:** Warning that students cannot submit duplicate requests for the same document

This interface ensures accurate data collection and prevents multiple submissions of the same document type.

---

## Figure 4. Student My Requests - Request Tracking

**What to Show:** The request history page showing active and past requests with status badges.

**Description:**
The "My Requests" page allows students to track their document submissions with complete status visibility. The page displays:
- **Active Requests section:** Shows current requests with document type, ticket number (REQ-2026-C5D899), submission date, and status badge (READY TO RELEASE)
- **History of Requests section:** Archive of completed requests
- **New Request button:** Quick access to submit additional requests

This centralized tracking system provides transparency and helps students monitor their document request progress in real-time.

---

## Figure 5. Records Officer Dashboard - System Overview

**What to Show:** The admin/records officer dashboard with statistics and recent activities.

**Description:**
The Records Officer Dashboard displays comprehensive system control and monitoring capabilities. Key elements include:
- **Statistics cards:** Total Requests (1), Pending (0), Total Grades (8)
- **Welcome message:** Personalized greeting with system status summary
- **Recent Requests table:** Lists incoming requests with student info, document type, and status
- **System Activity log:** Shows recent actions (Updated Request Status, Submitted Request, etc.)
- **System Health section:** Displays storage usage (24%), database connection status, and system link status

This overview provides Records Officers with quick access to critical system information and pending tasks.

---

## Figure 6. Document Requests Queue - Active Requests Management

**What to Show:** The request management table showing all active requests with status update dropdown and action buttons.

**Description:**
The Active Requests page displays all incoming student document requests in a comprehensive table format. The interface shows:
- **Request table columns:** Ticket & Student info, Document Type, Current Status, Request Date, Actions
- **Status update dropdown:** Records Officers can change request status (Pending, Processing, Processed, Ready to Release, Released, Rejected)
- **Update Status button:** Confirms status changes
- **Remarks field:** For adding processing notes
- **Open Form Maker link:** Direct access to generate documents

This queue management system enables Records Officers to efficiently process requests and maintain workflow transparency.

---

## Figure 7. Form 137 Generator - Document Creation Interface

**What to Show:** The Form 137 Maker page with student search field and load button.

**Description:**
The Form 137 Generator allows Records Officers to create official student permanent records. The interface includes:
- **Search interface:** Student number lookup field with Load button
- **Instructions:** "Load a student record to begin" with search guidance
- **Document title:** "Form 137 Generator - Official Student Permanent Record Maker"
- **Purpose:** Generate official Form 137 documents for student records

Once a student record is loaded, Records Officers can review student information, make necessary edits, and generate the official PDF document.

---

## Figure 8. Form 138 Maker - Report Card Generation

**What to Show:** The Form 138 generation page with student name, school year, and subject ratings table.

**Description:**
The Form 138 Maker generates student report cards for specific school years. The interface displays:
- **Student Name field:** Student selection/entry
- **School Year field:** (e.g., 2023-2024)
- **Grade Level field:** Specifies the academic level
- **Subject Ratings table:** Lists subjects (Filipino, English, Mathematics, Science, etc.) with columns for Q1, Q2, Q3, Q4, Final, and Action
- **Add Subject button:** To include additional subjects
- **Generate button:** Creates the official report card

This organized layout ensures accurate grade entry and professional document formatting.

---

## Figure 9. Grade Portal Upload - Form 138 Management

**What to Show:** The Grade Portal with upload form and Grade Viewer search interface.

**Description:**
The Grade Portal is a dual-interface page for managing student academic records:

**Left side - Upload Form 138:**
- Student Number field
- Student Name field
- School Year input
- Reference File upload (Excel/PDF/Image)
- Add another School Year button
- Upload Academic Records button

**Right side - Grade Viewer:**
- Search Student Number field
- Search button
- Message: "Search for a student to view history"

This interface allows Registrars to upload new grade records and search for existing student academic history.

---

## Figure 10. Request History - Archive Management

**What to Show:** The request history page showing completed/rejected requests table structure and empty state.

**Description:**
The Request History page (Records Archive) displays all completed and rejected document requests. The interface includes:
- **Records Archive header:** "Total of 0 history record(s) found"
- **History table columns:** Ticket & Student, Document Type, Status, Date Processed, Remarks
- **Empty state message:** "No history yet" when no completed requests exist
- **Purpose:** Maintains audit trail of all processed requests for record-keeping and compliance

This archive helps track request processing completion and provides historical documentation.

---

## Figure 11. User Directory - User Management Interface

**What to Show:** The User Management page showing active accounts table with user roles and last login info.

**Description:**
The User Directory allows administrators to manage all system users. The interface displays:
- **Active Accounts table** with columns:
  - Identity (Avatar, Name, Email)
  - Role Assigned (Admin, Registrar, Records Officer, Student)
  - Status (Active/Inactive)
  - Last Login (date/time)
  - Actions (Edit, Delete buttons)
- **Add New User button:** For creating user accounts
- **Account information:** Shows 428 total registered users

The page provides comprehensive user account management and access control.

---

## Figure 12. System Audit Logs - Activity Tracking

**What to Show:** The System Audit Logs page with activity history table showing timestamp, user, action, module, and status.

**Description:**
The System Audit Logs page monitors all system activities and user actions. The interface displays:
- **Activity History header:** "Real-time system event monitoring"
- **Log table columns:**
  - Timestamp (Date and time of action)
  - User (Person who performed action - Registrar User, Records officer User, Admin User, Student)
  - Action (Updated Request Status, Submitted Request, Full Request System Reset, Updated User, Uploaded Reference File)
  - Module (Requests, Grades, User Management)
  - Status (Success/Failure indicators)
- **Filter button:** For searching/filtering logs
- **Clear Logs button:** For archiving old logs

This comprehensive logging system provides accountability and helps with troubleshooting and auditing.

---

## Figure 13. Data Analytics - System Insights and Reporting

**What to Show:** The Analytics dashboard with report filters, most requested documents, monthly trends chart, and request status distribution.

**Description:**
The Data Analytics page provides comprehensive system insights. The interface includes:

**Report Filters:**
- Month selection dropdown (May)
- Year selection dropdown (2026)
- Update Report button

**Analytics sections:**
- **Most Requested:** Shows Form 137 with count (1)
- **Monthly Request Volume (2026):** Line chart showing request trends across months with May peak
- **Request Status Distribution:** Status cards showing:
  - Pending: 0
  - Processing: 0
  - Processed: 0
  - Ready to Release: 1
  - Completed: 0

This visual analytics help administrators identify patterns and optimize system performance.

---

## Figure 14. System Settings - Configuration Management

**What to Show:** The Settings page with General Configuration fields and System Status controls.

**Description:**
The System Settings page allows administrators to configure system preferences. The interface includes:

**General Configuration section:**
- Institution Name field (Fiat Lux Academe)
- System Email field (admin@fiatlux.edu.ph)
- Contact Number field (+63 (046) 431 1234)
- Office Hours field (Mon-Fri 8:00 AM - 5:00 PM)
- Save Changes button

**System Status section:**
- Maintenance Mode toggle (for disabling public access)
- Update Status button

This centralized settings interface enables administrators to customize system configuration and control system availability.

---

## Summary

The Fiat Lux Academe Document Request Hub provides a comprehensive solution for managing student document requests with distinct user interfaces for:

1. **Students:** Document request submission and status tracking
2. **Records Officers:** Request processing and document generation
3. **Registrars:** Grade management and document submission
4. **Administrators:** Complete system control, user management, analytics, and settings

**Key Functions:**
- ✓ Student document request management
- ✓ Role-based access control and dashboards
- ✓ Automated document generation (Forms 137, 138, Good Moral, Diploma)
- ✓ Grade portal for academic record management
- ✓ Request workflow management with status tracking
- ✓ Comprehensive analytics and reporting
- ✓ System audit logging and activity tracking
- ✓ User management and role assignment
- ✓ System configuration and settings

The modern, intuitive interface improves operational efficiency, reduces manual processing time, and provides transparency throughout the document request workflow.

---

## Instructions for Documentation

1. **Take clear, well-lit screenshots** of each figure without cropping important elements
2. **Capture actual data** to show realistic system usage
3. **Include success states** showing completed forms and successful operations
4. **Show different user perspectives** - include student, records officer, and admin views
5. **Document any error messages or validation** that appears during operations
6. **Number all figures consecutively** as shown in this guide
7. **Reference figure numbers** when describing system workflows
8. **Include figure titles and descriptions** exactly as provided above

---
