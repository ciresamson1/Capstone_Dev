# CapstoneV3 UML Use Case Diagram

```mermaid
%%{init: {"theme": "base", "themeVariables": {"actorBorder": "#333", "primaryColor": "#f3f4f6"}}}%%
usecaseDiagram
    actor Guest
    actor Admin
    actor PM
    actor DM
    actor Client

    Guest --> (Register)
    Guest --> (Login)

    Admin --> (Manage Users)
    Admin --> (View Admin Dashboard)
    Admin --> (View Reports)
    Admin --> (View Activity Log)
    Admin --> (Manage Projects)
    Admin --> (Manage Tasks)

    PM --> (View PM Dashboard)
    PM --> (View PM Reports)
    PM --> (View PM Activity Log)
    PM --> (Manage Projects)
    PM --> (Manage Tasks)
    PM --> (View Project Details)
    PM --> (Create Task)
    PM --> (Update Task)

    DM --> (View DM Dashboard)
    DM --> (View DM Projects)
    DM --> (View DM Tasks)
    DM --> (View DM Reports)
    DM --> (View Project Details)
    DM --> (Update Task)

    Client --> (View Client Dashboard)
    Client --> (View Client Projects)
    Client --> (View Client Tasks)
    Client --> (View Project Details)

    Admin --> (Mark Notifications Read)
    PM --> (Mark Notifications Read)
    DM --> (Mark Notifications Read)
    Client --> (Mark Notifications Read)

    Admin --> (Add Task Comment)
    PM --> (Add Task Comment)
    DM --> (Add Task Comment)
    Client --> (Add Task Comment)

    Admin --> (React to Task Comment)
    PM --> (React to Task Comment)
    DM --> (React to Task Comment)
    Client --> (React to Task Comment)

    Admin --> (Download Comment Attachment)
    PM --> (Download Comment Attachment)
    DM --> (Download Comment Attachment)
    Client --> (Download Comment Attachment)

    (Manage Tasks) ..> (Create Task) : includes
    (Manage Tasks) ..> (Update Task) : includes
    (Manage Projects) ..> (View Project Details) : includes
    (View Reports) ..> (View PDF Report) : includes
    (View Activity Log) ..> (View Project Details) : extends

    Guest --> (Logout)
    Admin --> (Logout)
    PM --> (Logout)
    DM --> (Logout)
    Client --> (Logout)
```