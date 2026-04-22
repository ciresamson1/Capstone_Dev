# CapstoneV3 UML Sequence Diagram

This document provides sequence diagrams for the core workflows of CapstoneV3.

## 1. User Login and Role-Based Redirect

```mermaid
sequenceDiagram
    autonumber
    actor User as User (Admin/PM/DM/Client)
    participant Browser as Browser UI
    participant AuthRoute as Auth Route (/login)
    participant Auth as Laravel Auth
    participant DB as MySQL (users)

    User->>Browser: Enter email and password
    Browser->>AuthRoute: POST /login
    AuthRoute->>Auth: attempt(credentials)
    Auth->>DB: Validate user credentials
    DB-->>Auth: User record / failure

    alt Credentials valid
        Auth-->>AuthRoute: Authenticated session
        AuthRoute-->>Browser: Redirect by role
        Note over AuthRoute,Browser: admin -> /admin/dashboard\npm -> /pm/dashboard\nelse -> /projects.index
    else Credentials invalid
        Auth-->>AuthRoute: Authentication failed
        AuthRoute-->>Browser: Return with error message
    end
```

## 2. Create/Update Task with Logging and Broadcast

```mermaid
sequenceDiagram
    autonumber
    actor PM as PM/Admin User
    participant UI as Project/Task UI
    participant TaskController as TaskController
    participant TaskModel as Task Model
    participant ProjectModel as Project Model
    participant ActivityLog as ActivityLog
    participant ProgressLog as ProgressLog
    participant Cache as Cache
    participant Events as Broadcast Events
    participant DB as MySQL

    PM->>UI: Submit task form (create/update)
    UI->>TaskController: HTTP request
    TaskController->>TaskController: Validate inputs

    alt Create Task
        TaskController->>TaskModel: create(task data)
        TaskModel->>DB: INSERT task
        TaskController->>ProjectModel: find(project)
        ProjectModel->>DB: SELECT project
        TaskController->>ActivityLog: record(created_task)
        ActivityLog->>DB: INSERT activity log
    else Update Task
        TaskController->>TaskModel: find(task)
        TaskModel->>DB: SELECT task
        TaskController->>TaskModel: update(task data)
        TaskModel->>DB: UPDATE task
        opt Progress changed
            TaskController->>ProgressLog: create(progress delta)
            ProgressLog->>DB: INSERT progress log
        end
        TaskController->>ActivityLog: record(updated_task)
        ActivityLog->>DB: INSERT activity log
    end

    TaskController->>Cache: forget(dashboard caches)
    TaskController->>Events: broadcast(TaskChanged, DashboardUpdated)
    TaskController-->>UI: Success response/redirect
```

## 3. Post Task Comment, Notify Team, and Realtime Update

```mermaid
sequenceDiagram
    autonumber
    actor Actor as Authenticated User
    participant UI as Task Card / Comment UI
    participant CommentController as TaskCommentController
    participant CommentModel as TaskComment Model
    participant TaskModel as Task Model
    participant UserModel as User Model
    participant Mail as Mailer (SMTP)
    participant ActivityLog as ActivityLog
    participant Cache as Cache
    participant Events as Broadcast Events
    participant DB as MySQL

    Actor->>UI: Enter comment/reply and submit
    UI->>CommentController: POST /tasks/{task}/comments
    CommentController->>CommentController: Validate message/link/parent
    CommentController->>TaskModel: resolve task + parent comment
    TaskModel->>DB: SELECT task/comment context
    CommentController->>CommentModel: create(comment)
    CommentModel->>DB: INSERT task_comments

    CommentController->>Events: broadcast(TaskCommentCreated)
    CommentController->>Cache: forget(dashboard caches)
    CommentController->>Events: broadcast(DashboardUpdated)

    CommentController->>UserModel: Collect recipients (team + admins)
    UserModel->>DB: SELECT recipient users
    loop For each recipient
        CommentController->>Mail: send(TaskCommentMail)
        Mail-->>CommentController: sent/failed (non-fatal)
    end

    CommentController->>ActivityLog: record(posted_comment)
    ActivityLog->>DB: INSERT activity log
    CommentController-->>UI: Return JSON or redirect back
```

## Notes

- Role-based access is enforced before users reach protected routes.
- Logging (`ActivityLog`, `ProgressLog`) and cache invalidation are part of the normal flow.
- Broadcast failures are handled safely; the main transaction still completes.
