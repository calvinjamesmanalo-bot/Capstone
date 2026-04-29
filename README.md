# regHub

regHub is a Laravel-based student information and document management system designed to streamline school registration processes and document requests.

## Features

- **Dashboard**: Overview of system activities and statistics.
- **Student Management**: Manage student profiles and information.
- **Document Requests**: Handle requests for various school documents:
    - Diplomas
    - Form 137 (Permanent Record)
    - Good Moral Certificates
- **Grade Management**: Record and track student grades.
- **Activity Logs**: Track system usage and administrative actions.
- **User Management**: Role-based access control for administrators and staff.
- **Settings**: Configure system-wide parameters.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/MariaDB

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/regHub.git
   cd regHub
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**:
   ```bash
   npm install
   npm run build
   ```

4. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Update the database credentials in your `.env` file.*

5. **Run migrations and seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Start the development server**:
   ```bash
   php artisan serve
   ```

## License

The regHub system is open-sourced software licensed under the [MIT license](LICENSE).
