# Black Box Test Cases - Admin Users Table

## Scope
This test set covers the Admin Manage Users module (Users table, Edit user modal, Delete action, and Invite user form).

## Assumptions
- Tester is logged in as a user with Admin role.
- Admin page is accessible at the Manage Users screen.
- At least two user records exist (including the logged-in admin account).

## Test Case Matrix

| TC ID | Feature | Preconditions | Test Steps | Test Data | Expected Result |
|---|---|---|---|---|---|
| BB-ADMIN-001 | View Users Table | Admin logged in | 1) Open Manage Users page. | N/A | Users table is displayed with columns: Username, Name, Email, Role, Position, Company, Joined, Actions. |
| BB-ADMIN-002 | List Ordering | At least 3 users exist with different created dates | 1) Open Manage Users page. 2) Observe row order. | N/A | Users are listed in descending order of creation date (newest first). |
| BB-ADMIN-003 | Role Display Format | Users with roles admin/pm/dm/client exist | 1) Open Manage Users page. | N/A | Role values display as human-readable capitalized text (e.g., Admin, Pm, Dm, Client). |
| BB-ADMIN-004 | Open Edit Modal | Any user row exists | 1) Click Edit on a user row. | Target user row | Edit modal opens and fields are pre-filled with selected user details. |
| BB-ADMIN-005 | Update User Valid Data | Edit modal open | 1) Update fields. 2) Click Save Changes. | name=testadmin2, first_name=Test, last_name=Admin, email=admin2@example.com, role=pm, position=Manager, company=SGPro | Redirects to Manage Users page with success message and updated row values. |
| BB-ADMIN-006 | Update User Duplicate Email | Another user already uses existing email | 1) Open Edit for User A. 2) Set email equal to User B email. 3) Save. | email=existing_user@example.com | Validation error shown for email uniqueness; record is not updated. |
| BB-ADMIN-007 | Update User Invalid Role | Edit modal open | 1) Manipulate request role value. 2) Submit form. | role=superadmin | Validation error shown for role; record is not updated. |
| BB-ADMIN-008 | Update User Password Success | Edit modal open | 1) Enter password and matching confirmation. 2) Save. | password=StrongPass123, password_confirmation=StrongPass123 | User update succeeds; no validation error for password fields. |
| BB-ADMIN-009 | Update User Password Mismatch | Edit modal open | 1) Enter password and non-matching confirmation. 2) Save. | password=StrongPass123, password_confirmation=Mismatch123 | Validation error shown; record is not updated. |
| BB-ADMIN-010 | Update User Password Too Short | Edit modal open | 1) Enter short password and matching confirmation. 2) Save. | password=abc123, password_confirmation=abc123 | Validation error shown (minimum 8 chars); record is not updated. |
| BB-ADMIN-011 | Delete Other User | Target non-logged-in user exists | 1) Click Delete on another user row. 2) Confirm dialog. | Target user id != current admin id | User is deleted; success message displayed; row removed from table. |
| BB-ADMIN-012 | Self-Delete Protection | Logged-in admin row visible | 1) Inspect Actions on own row. 2) Attempt to delete own account. | current admin user | Delete button is not shown for own row; self-delete is prevented by UI and backend protection. |
| BB-ADMIN-013 | Invite User Success | Invite form visible | 1) Fill valid email and role. 2) Click Send Invite. | email=newuser@example.com, role=client | Success message shown: invitation sent; no validation errors. |
| BB-ADMIN-014 | Invite Duplicate Email | Existing user email present in system | 1) Enter existing email. 2) Select any role. 3) Send Invite. | email=existing_user@example.com, role=dm | Validation error displayed for email uniqueness; invite is not sent. |
| BB-ADMIN-015 | Invite Invalid Email Format | Invite form visible | 1) Enter invalid email. 2) Select role. 3) Send Invite. | email=not-an-email, role=pm | Validation error displayed for email format; invite is not sent. |
| BB-ADMIN-016 | Invite Missing Role | Invite form visible | 1) Submit invite without role (request manipulation). | email=userx@example.com, role=(blank) | Validation error displayed for required role; invite is not sent. |
| BB-ADMIN-017 | Invite Invalid Role Value | Invite form visible | 1) Manipulate role value in request. 2) Submit. | role=owner | Validation error displayed for role in allowed set only; invite is not sent. |
| BB-ADMIN-018 | Session Status Message Visibility | Any successful action completed | 1) Complete successful update/delete/invite. 2) Return to Manage Users page. | N/A | Success alert banner appears and contains the corresponding operation message. |

## Suggested Execution Log Template

| TC ID | Tester | Date | Actual Result | Status (Pass/Fail) | Defect ID | Remarks |
|---|---|---|---|---|---|---|
| BB-ADMIN-001 |  |  |  |  |  |  |
| BB-ADMIN-002 |  |  |  |  |  |  |
| BB-ADMIN-003 |  |  |  |  |  |  |
| BB-ADMIN-004 |  |  |  |  |  |  |
| BB-ADMIN-005 |  |  |  |  |  |  |
| BB-ADMIN-006 |  |  |  |  |  |  |
| BB-ADMIN-007 |  |  |  |  |  |  |
| BB-ADMIN-008 |  |  |  |  |  |  |
| BB-ADMIN-009 |  |  |  |  |  |  |
| BB-ADMIN-010 |  |  |  |  |  |  |
| BB-ADMIN-011 |  |  |  |  |  |  |
| BB-ADMIN-012 |  |  |  |  |  |  |
| BB-ADMIN-013 |  |  |  |  |  |  |
| BB-ADMIN-014 |  |  |  |  |  |  |
| BB-ADMIN-015 |  |  |  |  |  |  |
| BB-ADMIN-016 |  |  |  |  |  |  |
| BB-ADMIN-017 |  |  |  |  |  |  |
| BB-ADMIN-018 |  |  |  |  |  |  |
