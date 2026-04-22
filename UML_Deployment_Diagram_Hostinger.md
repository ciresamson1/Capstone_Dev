# CapstoneV3 Deployment Diagram (Hostinger hPanel + GitHub)

This deployment view is designed for a typical Hostinger hPanel setup where code is deployed from GitHub and served through PHP.

## UML Deployment Diagram (Mermaid)

```mermaid
graph TD
    U[End Users\nBrowser / Mobile Browser]
    DNS[Domain + DNS\nHostinger]
    GH[GitHub Repository\nCapstoneV3]

    subgraph HP[Hostinger hPanel Environment]
        WEB[Web Server\nApache/Nginx + PHP-FPM]
        APP[Laravel Application\nCapstoneV3\npublic/index.php entrypoint]
        STG[Laravel Storage\nlogs, cache, uploads]
        DB[(MySQL / MariaDB\nHostinger Database)]
        CRON[Cron Jobs\nphp artisan schedule:run\nphp artisan queue:work --stop-when-empty]
    end

    subgraph EXT[External Services]
        MAIL[SMTP Provider\nHostinger Email / SMTP]
        WS[Realtime Service\nPusher (recommended on shared hosting)]
    end

    U -->|HTTPS| DNS
    DNS -->|HTTPS| WEB
    WEB --> APP
    APP --> STG
    APP -->|SQL 3306| DB
    APP -->|SMTP 587/465| MAIL
    APP -->|Events / Broadcast| WS

    GH -->|Git Deploy / Pull in hPanel| WEB
    CRON -->|Periodic execution| APP
```

## Node-to-Component Mapping

- End Users: Access the system dashboards, project pages, task pages, and reports via browser.
- GitHub Repository: Source of truth for code; hPanel pulls or syncs deployment from this repository.
- Web Server + PHP-FPM: Runs Laravel request lifecycle and serves public assets.
- Laravel Application: Handles routing, authentication, role authorization, business logic, and rendering.
- MySQL/MariaDB: Persists users, projects, tasks, comments, notifications, and logs.
- Storage: Persists uploaded files and runtime artifacts.
- Cron Jobs: Runs scheduler and queued tasks where supported.
- SMTP Provider: Sends invite emails and notification emails.
- Realtime Service: Delivers realtime updates; external provider is recommended for shared hosting.

## Hostinger Notes (Important)

1. Shared hosting usually does not allow long-running processes reliably.
2. For realtime broadcasting, use an external service (for example Pusher) instead of self-hosted Reverb.
3. Queue processing can be executed by scheduled cron commands if persistent workers are unavailable.
4. Ensure the web root points to Laravel public directory.
5. Keep .env secrets only on server and never commit them to GitHub.

## Optional Alternative (If Using Hostinger VPS)

If you deploy on VPS instead of shared hosting, you can host Laravel Reverb and a persistent queue worker inside the same server, then remove the external realtime dependency.
