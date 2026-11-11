-- Run this in Supabase: Dashboard > SQL Editor > New query > paste > Run.
-- For existing installations, old tasks are assigned to the existing admin user.
-- New users can create accounts at register.php after the schema is applied.

create table if not exists users (
  user_id bigint generated always as identity primary key,
  username text not null unique,
  password text not null
);

create table if not exists tasks (
  task_id bigint generated always as identity primary key,
  user_id bigint references users(user_id) on delete cascade,
  task_title varchar(150) not null,
  description text,
  due_date date not null,
  status text not null default 'Pending' check (status in ('Pending','Completed')),
  created_at timestamptz default now()
);

-- Backfill tasks from the previous single-user schema, then require an owner.
alter table tasks add column if not exists user_id bigint references users(user_id) on delete cascade;
update tasks
set user_id = (select user_id from users where username = 'admin')
where user_id is null
  and exists (select 1 from users where username = 'admin');
alter table tasks alter column user_id set not null;

-- Lock the tables from public/anon access. The PHP app uses the
-- server-side Supabase secret key, which bypasses this.
alter table users enable row level security;
alter table tasks enable row level security;
