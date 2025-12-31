# Expiry Alert - Expiry Date Management System

## Project Description
**Expiry Alert** is a comprehensive web application designed to help individuals and families track the expiration dates of various household items. From important documents and medications to food and cosmetics, this system centralizes all expiry information to prevent waste, ensure safety, and avoid missed deadlines.

The application features a user-friendly dashboard for regular users to manage their items and an admin panel for overall system oversight.

## Objectives of the Project
-   **Waste Reduction:** Minimize food and medicine waste by alerting users before items expire.
-   **Safety:** Prevent the use of expired medications and beauty products.
-   **Organization:** Centralize tracking for documents (passports, licenses) to avoid penalties.
-   **Simplicity:** Provide an intuitive interface that makes tracking dates effortless.
-   **Automation:** Send automated notifications to users as expiry dates approach.

## System Architecture
The system follows a standard **MVC (Model-View-Controller)** pattern implemented in core PHP:
-   **Frontend:** HTML5, CSS3, JavaScript, and Bootstrap for a responsive user interface.
-   **Backend:** Core PHP handling business logic, session management, and database interactions.
-   **Database:** MySQL for storing user data, item details, and activity logs.
-   **Notification Service:** A background service (cron job) that checks for upcoming expiry dates and triggers email alerts via PHPMailer.

## Project Structure
The repository is organized as follows:

-   **`admin/`**: Contains administrative scripts (`dashboard.php`, `users.php`, `reports.php`, `logs.php`) for managing the platform.
-   **`config/`**: Configuration files for database connections (`database.php`) and email settings.
-   **`pending_emails/` & `saved_emails/`**: Directories used by the email notification system to queue and archive sent emails.
-   **`services/`**: specialized service logic.
-   **`index.php`**: The main user dashboard showing an overview of tracked items.
-   **`login.php` / `register.php`**: User authentication modules.
-   **`[category].php`**: Dedicated pages for each category (`medicine.php`, `food.php`, `documents.php`, etc.).
-   **`cron_notifications.php`**: Script meant to be run periodically to check for expiring items and queue emails.
-   **`about.php`**: Information about the application and its mission.

## Technologies Used
-   **Language:** PHP (7.4+)
-   **Database:** MySQL
-   **Frontend:** HTML, CSS, JavaScript, Bootstrap
-   **Server:** Apache (via XAMPP/WAMP or similar)
-   **Libraries:** PHPMailer (for email notifications)

## How the System Works
1.  **User Registration:** Users sign up to create a private account.
2.  **Item Entry:** Users add items to specific categories (e.g., "Tylenol" to Medicines), setting an expiry date.
3.  **Monitoring:** The system's dashboard classifies items into "Safe", "Expiring Soon" (e.g., within 7 days), and "Expired".
4.  **Notification:**
    -   On the dashboard, visual indicators warn the user.
    -   The background cron script checks the database daily.
    -   If an item is nearing expiry, an email is generated and placed in `pending_emails`.
    -   The email service delivers the alert to the user.
5.  **Reporting:** Users (and Admins) can generate reports to view history and usage trends.

## Installation and Setup

### Prerequisites
-   A local web server environment like **XAMPP**, **WAMP**, or **MAMP**.
-   PHP 7.4 or higher.
-   MySQL Database.

### Steps
1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/yourusername/expiry-alert.git
    ```
2.  **Move to Web Root:**
    -   Copy the project folder to `htdocs` (XAMPP) or `www` (WAMP).
3.  **Database Setup:**
    -   Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
    -   Create a new database named `expiry_alert_db` (or check `config/database.php` for the configured name).
    -   Import the provided SQL schema file if available (or recreate tables for `users`, `medicines`, `documents`, etc.).
4.  **Configuration:**
    -   Edit `config/database.php` to match your local database credentials.
    -   Edit `config/email-phpmailer-manual.php` with your SMTP details if you want email alerts to work.
5.  **Run the Application:**
    -   Open your browser and navigate to `http://localhost/EDR`.

## How to Run the Project
-   **Start Apache & MySQL:** Open your XAMPP/WAMP control panel and start both services.
-   **Access the App:** Go to `http://localhost/EDR`.
-   **Login:** Use your credentials. If new, register an account.
-   **Test Alerts:** Add an item with an expiry date of tomorrow to see the "Expiring Soon" status.

## Sample Use Case
**Scenario:**
-   **User:** Sarah, a frequent traveler.
-   **Action:** Sarah logs in and goes to the "Documents" section.
-   **Input:** She adds her Passport, setting the expiry date to "2025-10-15".
-   **Result:** The system tracks this date. Two months before expiry, Sarah receives an email reminding her to renew her passport, saving her from a last-minute travel crisis.

## Future Enhancements
-   **Mobile App:** Develop a native Android/iOS app for easier scanning of barcodes.
-   **Barcode/QR Scanner:** Integrate camera functionality to scan product barcodes and auto-fill details.
-   **Crowdsourced Data:** Build a database of common product shelf lives.
-   **Cloud Integration:** Fully deploy to a cloud environment with managed databases.

## Conclusion
Expiry Alert transforms the mundane task of tracking dates into an automated, worry-free process. By leveraging this tool, users can save money, reduce waste, and manage their responsibilities more effectively.
