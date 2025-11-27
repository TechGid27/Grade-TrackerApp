
A web-based application designed to help students and teachers track, organize, and monitor grades and tasks efficiently.  
The project is divided into Front End (Vue.js) and Back End (Laravel API) for modular and scalable development.

------------------------------------------------------------
🚀 Features

- Student Grade Management – Track subjects, assessments, and scores.
- To-Do Tracker – Manage assignments or pending tasks per subject.
- User Authentication – Secure login and registration via Laravel Sanctum.
- RESTful API – Back-end built in Laravel with endpoints for subjects, todos, and assessments.
- Modern UI – Responsive interface built using Vue.js and Tailwind CSS.
- Database Integration – Uses MySQL for persistent storage.
- Real-Time Updates – Auto-refresh of tasks and grades for a seamless experience.

------------------------------------------------------------
🧩 Project Structure

Grade Tracker/
├── Front end/           # Vue.js app
│   ├── src/
│   ├── public/
│   └── package.json
│
├── back-end/            # Laravel API
│   ├── app/
│   ├── routes/
│   ├── config/
│   ├── database/
│   └── composer.json
│
└── README.txt

------------------------------------------------------------
⚙️ Installation Guide

1️⃣ Clone the Repository
    git clone https://github.com/TechGid27/Grade-TrackerApp.git
    cd "Grade Tracker"

2️⃣ Set Up the Laravel Back End
    cd back-end
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate
    php artisan serve

3️⃣ Set Up the Vue Front End
    cd "../Front end"
    npm install
    npm run dev

Then open your browser at the local URL printed in the terminal (usually http://localhost:5173/).

------------------------------------------------------------
🧠 Tech Stack

Front End: Vue.js 3, Vite, Tailwind CSS
Back End: Laravel 10 (PHP Framework)
Database: MySQL
API Auth: Laravel Sanctum
Version Control: Git + GitHub

------------------------------------------------------------
👤 Author

👨‍💻 Gideon Ayao
Web Developer • Passionate about full-stack web development and building smart tools for students.
🌐 https://gideon-26e30.web.app


------------------------------------------------------------
🧾 Notes

- Ensure that both front-end and back-end servers are running.
- Update API URLs in the Vue app if your Laravel backend runs on a different port.
- For deployment, configure environment variables properly in .env and .env.production.
"""
