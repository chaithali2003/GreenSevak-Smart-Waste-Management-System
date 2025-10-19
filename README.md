# GreenSevak – Smart Waste Management System

**GreenSevak** is a web-based Waste Management System designed to streamline bulk waste collection and management processes. It provides a centralized platform where bulk waste generators, such as institutions, event organizers, and residential communities, can raise pickup requests, submit complaints, and provide feedback. Admins manage users and assign tasks to collectors, while collectors update task statuses in real-time, ensuring accountability, transparency, and efficiency in waste management operations.

---

## 🔧 Technologies Used

* **Frontend:** HTML, CSS, JavaScript, Bootstrap for responsive and interactive UI
* **Backend:** PHP for server-side logic and request handling
* **Database:** MySQL for storing user data, pickup requests, and system logs

---

## 🧩 Features

### User Roles and Functionalities

**Citizen:**

* Submit bulk waste pickup requests.
* View status of submitted requests.
* Submit complaints and feedback.

**Collector:**

* View assigned pickup requests.
* Update status of pickups in real-time.
* Maintain accountability of completed tasks.

**Admin:**

* Manage user accounts (add/delete/modify).
* Assign pickup requests to collectors.
* Monitor all ongoing pickups and activities.
* Handle complaints and feedback.

### Key Features

* Role-based dashboards for Admin, Collector, and Citizen.
* Real-time tracking of pickup requests and updates.
* Centralized complaint and feedback system.
* Secure login and authentication for all users.
* Clean, user-friendly interface with responsive design.
* Email integration using PHPMailer to send notifications, alerts, and confirmations to users.

### Email Integration Details

* **Purpose:** Notify users of pickup request confirmations, status updates, and important alerts.
* **Library Used:** PHPMailer
* **Configuration:**

  * SMTP Host: `smtp.example.com`
  * Port: 587
  * Encryption: TLS/SSL
  * Username: your email
  * Password: your email password or app-specific password
* **Functionality:** Sends automatic emails when a pickup is scheduled, assigned to a collector, or completed, and also for password recovery and system notifications.

---

## 📂 Project Structure

```
GreenSevak/
├── admin/                   # Admin dashboard and management pages
├── assets/                  # Images, icons, and other static assets
├── auth/                    # Authentication scripts for login/registration
├── citizen/                 # Citizen dashboard and request pages
├── collector/               # Collector dashboard and status update pages
├── config/                  # Database configuration and connection scripts
├── includes/                # Reusable components and helper files
├── vendor/                  # External libraries (e.g., PHPMailer for email notifications)
├── about.php                # About page for the system
├── contact.php              # Contact page for support and queries
├── greensevak.sql           # Database schema and initial data
├── index.php                # Landing page / homepage
└── logo.png                 # Project logo
```

---

## ⚙️ Setup Instructions

### Prerequisites

* PHP 7.4 or higher
* MySQL 5.7 or higher
* Web server such as XAMPP, WAMP, or MAMP
* Browser: Chrome, Firefox, or Edge

### Installation Steps

1. **Clone the Repository:**

```bash
git clone https://github.com/chaithali2003/GreenSevak-Smart-Waste-Management-System.git
cd GreenSevak-Smart-Waste-Management-System
```

2. **Set Up Database:**

* Open your MySQL server.
* Import `greensevak.sql` to create the database and required tables.

3. **Configure PHP Files:**

* Open files in `config/` folder and update database credentials (`host`, `username`, `password`, `database`).
* Configure PHPMailer settings with your email credentials for sending notifications.

4. **Run the Application:**

* Place the project folder in the web server's root directory (e.g., `htdocs` in XAMPP).
* Open your browser and navigate to:
  `http://localhost/GreenSevak-Smart-Waste-Management-System/`

5. **Login as Different Users:**

* Use Admin, Collector, or Citizen credentials to access role-specific dashboards.

---

## 🚀 Future Enhancements

* GPS tracking for collectors to provide real-time location updates.
* AI-based auto-assignment of pickups based on collector availability and workload.
* Mobile application with push notifications for better accessibility.
* Multilingual support to serve a wider audience.
* Email/SMS alerts for notifications and updates.
* Analytics dashboards for Admin to monitor system efficiency.
* Emergency contact or help request button for urgent complaints.
* Integration with smart sensors for automatic waste level detection and scheduling.
