# CapstoneV3 UML Activity Diagram

This activity diagram models the main operational flow of CapstoneV3 from authentication to project/task collaboration and reporting.

```mermaid
flowchart TD
    A([Start]) --> B[Open CapstoneV3 in Browser]
    B --> C{Has account?}

    C -- No --> D[Register Account]
    D --> E[Submit Registration]
    E --> F[Login]

    C -- Yes --> F[Login]
    F --> G[Validate Credentials]
    G --> H{Credentials valid?}

    H -- No --> I[Show Login Error]
    I --> F

    H -- Yes --> J[Create Session]
    J --> K{User Role?}

    K -- Admin --> L[Open Admin Dashboard]
    K -- PM --> M[Open PM Dashboard]
    K -- DM --> N[Open DM Dashboard]
    K -- Client --> O[Open Client Dashboard]

    L --> P[Access Allowed Modules]
    M --> P
    N --> P
    O --> P

    P --> Q{Select Action}

    Q -- Manage Projects --> R[Create / Edit / Delete Project]
    R --> R1[Save to Database]
    R1 --> R2[Write Activity Log]
    R2 --> R3[Clear Dashboard Cache]
    R3 --> R4[Broadcast Dashboard Update]
    R4 --> Q

    Q -- Manage Tasks --> S[Create / Update / Toggle Task]
    S --> S1[Validate Inputs]
    S1 --> S2[Save Task Changes]
    S2 --> S3{Progress changed?}
    S3 -- Yes --> S4[Create Progress Log]
    S3 -- No --> S5[Skip Progress Log]
    S4 --> S6[Write Activity Log]
    S5 --> S6
    S6 --> S7[Clear Dashboard Cache]
    S7 --> S8[Broadcast Task/Dashboard Events]
    S8 --> Q

    Q -- Post Comment --> T[Write Comment/Reply]
    T --> T1[Validate Message/Link]
    T1 --> T2[Save Comment]
    T2 --> T3[Broadcast Comment Event]
    T3 --> T4[Identify Recipients]
    T4 --> T5[Send Email Notifications]
    T5 --> T6[Write Activity Log]
    T6 --> T7[Clear Dashboard Cache]
    T7 --> T8[Broadcast Dashboard Update]
    T8 --> Q

    Q -- React to Comment --> U[Toggle Up/Down Reaction]
    U --> U1[Update Reaction in Database]
    U1 --> U2[Return Updated Counts]
    U2 --> Q

    Q -- View Reports --> V[Open Role-Based Reports]
    V --> V1[Compute KPIs and Metrics]
    V1 --> V2{Export PDF?}
    V2 -- Yes --> V3[Generate Report PDF]
    V2 -- No --> V4[Render Report Page]
    V3 --> Q
    V4 --> Q

    Q -- Logout --> W[Destroy Session]
    W --> X([End])
```

## Notes

- Authentication and role checks gate access to protected modules.
- Core operations write audit data using activity/progress logs.
- Cache invalidation and broadcast events keep dashboards updated.
- Comment workflows include notification dispatch to relevant users.
