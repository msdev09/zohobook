# Zoho Books Financial Dashboard

A custom Laravel web application that integrates deeply with the Zoho Books API to provide a comprehensive financial reporting dashboard. It synchronizes sales and expense data (Invoices and Bills) from Zoho Books, calculates totals for previous and current periods, stores document attachments locally, and allows users to define monthly budgets.

## Technical Details

- **Framework:** Laravel (PHP)
- **Database:** MySQL
- **Frontend:** Blade templates, Tailwind CSS, Vanilla JavaScript
- **Integration:** Zoho Books API V3
- **Data Synchronization:** Incremental sync using last modified timestamps (`ZohoSyncTimestamp`)
- **Key Models:** `ZohoInvoice`, `ZohoBill`, `ZohoAttachment`, `MonthlyBudget`
- **File Storage:** Local public storage using polymorphic relationships for attachments (`attachable`).

---

## 1. Prerequisites
- PHP >= 8.2
- Composer
- Node.js & npm (for building frontend assets)
- MySQL Server

---

## 2. Clone Repo & Set Up Environment Variables

. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd zohobook
   ```

First, copy the example environment file and create a new `.env` file:
```bash
cp .env.example .env
```

Then, set up your `.env` file with the following variables. Ensure the database credentials match your local setup.

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Zoho Books Configuration
ZOHO_CLIENT_ID=your_client_id
ZOHO_CLIENT_SECRET=your_client_secret
ZOHO_ORGANIZATION_ID=your_organization_id
ZOHO_REFRESH_TOKEN=your_refresh_token
ZOHO_REDIRECT_URI=http://localhost:8000/zoho/callback
ZOHO_DATA_CENTER=com  # e.g., 'in', 'com', 'eu', 'au' depending on your Zoho account
```

---

## 3. Installation Steps

Follow these instructions to get the application running on a fresh environment.

1. **Install PHP dependencies:**
   ```bash
   composer install
   ```

2. **Install Node.js dependencies:**
   ```bash
   npm install
   ```

3. **Build frontend assets:**
   ```bash
   npm run build
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```


5. **Run Database Migrations:**
   This will create the necessary tables for invoices, bills, budgets, and attachments.
   ```bash
   php artisan migrate
   ```

6. **Create the Storage Link:**
   This is required for the application to serve the downloaded PDF/attachment files.
   ```bash
   php artisan storage:link
   ```

7. **Start the Development Server:**
    ```bash
    php artisan serve
    ```

---

## 4. How to Get a Refresh Token from Zoho Books

To interact with the Zoho Books API smoothly, you must obtain a long-lived **Refresh Token**. We have automated this process directly within the application!

1. **Create a Zoho API Client:**
   - Go to the [Zoho API Console](https://api-console.zoho.com/).
   - Click on **Add Client** and select **Server-based Applications**.
   - Fill in a Client Name and set the **Authorized Redirect URIs** to `http://localhost:8000/zoho/callback`.
   - Save the configuration. You will receive a **Client ID** and **Client Secret**.

2. **Prepare your credentials:**
   You will put the `ZOHO_CLIENT_ID` and `ZOHO_CLIENT_SECRET` into your `.env` file during the installation steps above. Also ensure `ZOHO_REDIRECT_URI` is set to `http://localhost:8000/zoho/callback`.
   
   *Note: You can find your Organization ID by logging into Zoho Books normally and looking at the URL or under Settings -> Organization Profile. Add this to `ZOHO_ORGANIZATION_ID`.*

3. **Generate the Refresh Token Automagically:**
   With your development server running (`php artisan serve`), simply navigate to the following URL in your browser:
   ```text
   http://localhost:8000/zoho/connect
   ```
   - You will be redirected to Zoho to authorize the application.
   - Once authorized, Zoho redirects back to your application.
   - The application automatically exchanges the authorization code for a Refresh Token and securely updates your `.env` file! After authorization, it will redirect you to the report dashboard and automatically begin the delta sync of your Zoho records.
