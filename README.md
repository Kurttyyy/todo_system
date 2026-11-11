# To-Do List

A PHP task manager that stores accounts and tasks in Supabase. It includes registration and login, task CRUD, keyword search, input validation, and a responsive Bootstrap interface.

## Requirements

- PHP 8 or later with the `curl` and `mbstring` extensions enabled
- A Supabase project
- Apache/XAMPP, or PHP's built-in development server
- Internet access to load Bootstrap from its CDN

## Supabase setup

1. Create a Supabase project.
2. In the Supabase SQL Editor, run the contents of [`supabase.sql`](./supabase.sql). This creates the `users` and `tasks` tables and enables row-level security.
3. Configure the PHP server with:
   - `SUPABASE_URL`: the project URL, such as `https://your-project-ref.supabase.co`
   - `SUPABASE_SECRET_KEY`: a server-side Supabase secret key, or a legacy `service_role` key
4. Restart the PHP/Apache server after changing its environment.
5. Open `login.php` and create an account.

The app sends database requests from PHP and must use a server-side secret/service-role key because the tables have row-level security enabled. **Never put that key in HTML, JavaScript, or a public repository.** Do not use a publishable/anon key for this app.

## Deploy to Vercel

Vercel runs this PHP app in a Docker container using FrankenPHP. Its Container runtime is currently in beta. PHP sessions are stored in Supabase so logins continue working across container restarts and instances.

1. If this Supabase project already has the app tables, run the updated [`supabase.sql`](./supabase.sql) in the Supabase SQL Editor to create the shared `app_sessions` table.
2. In Vercel, import this GitHub repository. Vercel detects the root `Dockerfile` and uses the Container preset.
3. Add `SUPABASE_URL` and `SUPABASE_SECRET_KEY` as environment variables for the Vercel environments you plan to use. Use a server-side Supabase secret/service-role key; do not expose it in the repository or browser code.
4. Deploy or redeploy the project.

The app requires these variables at runtime. A deployment without them will display a configuration error.

### XAMPP on Windows

For Apache, add the following directives to the Apache server configuration (not to a public HTML or JavaScript file), replace the example values, and restart Apache:

```apache
SetEnv SUPABASE_URL "https://your-project-ref.supabase.co"
SetEnv SUPABASE_SECRET_KEY "your-server-side-secret-key"
```

Alternatively, start PHP's built-in development server from this project directory in PowerShell:

```powershell
$env:SUPABASE_URL = "https://your-project-ref.supabase.co"
$env:SUPABASE_SECRET_KEY = "your-server-side-secret-key"
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000`. Keep the PowerShell window open while using the development server.

## Features

- Register and log in with hashed passwords
- Create, list, search, update, complete, and delete tasks
- Per-account task ownership
- Server-side task validation and CSRF protection for forms that change data
- Manila-local dashboard date and overdue-task counts
- Responsive custom CSS with Bootstrap 5

## Manual smoke test

After applying the schema and configuring the server:

1. Register a new account and log in.
2. Add a task with a title and valid due date; confirm it appears in the dashboard.
3. Search for a unique word from its title or description.
4. Edit the task, then mark it complete and pending.
5. Delete it and confirm the browser asks for confirmation.
6. Try an empty title or invalid date and confirm validation prevents saving.
7. Create a second account and confirm it cannot see the first account's tasks.
