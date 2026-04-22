# Compact Black-Box Test Cases - All Roles

## Objective
Validate the core workflow across all roles (Admin, PM, DM, Client) using the fewest high-value test instructions.

## Roles Covered
- Admin
- Project Manager (PM)
- Digital Marketer (DM)
- Client

## Minimal Workflow Test Matrix

| TC ID | Scenario | Preconditions | Steps (short) | Expected Result |
|---|---|---|---|---|
| BB-ROLE-001 | End-to-end happy path (all roles) | 1) Admin account is active. 2) Test emails for PM, DM, Client available. | 1) Admin invites PM, DM, Client. 2) PM creates project with Client assigned. 3) PM creates task and assigns DM. 4) DM updates task to in progress or completed. 5) Client opens own dashboard/tasks and posts comment on same task. | All actions succeed without errors; each role sees the same project/task in their permitted views; task update and client comment are visible to PM/DM/Admin. |
| BB-ROLE-002 | Role-based dashboard redirect | One active account per role exists. | 1) Log in as Admin, PM, DM, Client (one by one). 2) Open /dashboard after each login. | Each user is redirected to the correct dashboard for their role. |
| BB-ROLE-003 | Access restriction by role | PM, DM, Client accounts exist and are logged in separately. | 1) As PM/DM/Client, attempt to open Admin users page (/admin/users). | Non-admin users are blocked (403/unauthorized or redirected); only Admin can access user management. |
| BB-ROLE-004 | Data scoping for DM and Client | At least one task assigned to DM under a Client-linked project. | 1) Log in as DM and open DM tasks/projects. 2) Log in as Client and open Client tasks/projects. | DM sees only assigned tasks and related projects; Client sees only tasks/projects linked to their own client account. |
| BB-ROLE-005 | Negative input validation (critical) | Admin logged in at users module. | 1) Submit Invite with invalid email format. 2) Submit Invite with unsupported role value (request tampering). | Validation errors appear; invite is not sent; no user account is created. |

## Fast Execution Order (Recommended)
1. Run BB-ROLE-001 first (covers main business flow).
2. Run BB-ROLE-002 and BB-ROLE-003 (authorization and routing).
3. Run BB-ROLE-004 (data visibility boundaries).
4. Run BB-ROLE-005 (input safety regression).

## Pass Criteria
- All 5 test cases pass.
- No cross-role data leak is observed.
- No unauthorized page access is possible.
- Core workflow (invite -> create -> assign -> update -> comment) is stable.
