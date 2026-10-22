# AeroFocus — Assignment Manager (PHP OOP + PDO)

A CRUD web application for the **Assignment** entity of the AeroFocus system,
built with PHP (OOP), PDO prepared statements, and MySQL. No JavaScript is
used anywhere — every interaction (add, view, update, delete, search) is a
native HTML form submitted over standard HTTP.

## Folder Structure
```
aerofocus/
├── config/
│   └── database.php        Database connection class (PDO)
├── classes/
│   ├── Assignment.php       Main entity class (CRUD + search)
│   └── Subject.php          Helper class for the subject dropdown
├── includes/
│   ├── header.php
│   └── footer.php
├── css/
│   └── style.css            Neon theme (orange, sky blue, green, grey, red)
├── sql/
│   └── aerofocus.sql        Database schema + seed data
├── index.php                 Dashboard: list + search
├── add_assignment.php        Create
├── edit_assignment.php       Update
└── delete_assignment.php     Delete (with confirmation step)
```

## Setup on XAMPP (Windows/Mac)

1. **Start XAMPP.** Open the XAMPP Control Panel and start **Apache** and **MySQL**.

2. **Copy the project folder.**
   Place the whole `aerofocus` folder inside your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\aerofocus`
   - Mac: `/Applications/XAMPP/htdocs/aerofocus`

3. **Create the database.**
   - Open `http://localhost/phpmyadmin` in your browser.
   - Click **Import**, choose the file `sql/aerofocus.sql`, and click **Go**.
   - This creates the `aerofocus_db` database with the `users`, `subjects`,
     and `assignments` tables, plus sample data.

4. **Check the database credentials.**
   Open `config/database.php`. The defaults match a stock XAMPP install:
   ```php
   private $host = "localhost";
   private $dbName = "aerofocus_db";
   private $username = "root";
   private $password = "";
   ```
   Change these only if your MySQL setup uses a different username/password.

5. **Open the app.**
   Visit `http://localhost/aerofocus/index.php` in your browser.

6. **Try it out.**
   - **View** — the dashboard lists every assignment, soonest due date first.
   - **Add** — click "Add Assignment", fill out the form, and submit.
   - **Search** — type a keyword in the search bar and press Search; it
     matches title, description, subject, priority, or status.
   - **Update** — click "Edit" on any row, change the details, and submit.
   - **Delete** — click "Delete", confirm on the next screen, and the
     record is removed.

## Notes
- The app is built around a single demo user (`user_id = 1`, seeded in the
  SQL file) since the midterm scope is the **Assignment** entity itself,
  not full authentication.
- All SQL statements use PDO prepared statements with bound parameters to
  prevent SQL injection.
- Errors from the database layer are caught with try/catch blocks and
  logged via `error_log()` instead of being shown directly to the user.
