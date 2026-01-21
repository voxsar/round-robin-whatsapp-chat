# Round Robin WhatsApp Chat

A full-stack application built with Laravel 12, Vue.js 3, and Filament admin panel for managing round-robin WhatsApp chat distribution.

## Features

- 🎨 **Vue.js 3 Frontend** - Modern, reactive user interface
- ⚡ **Laravel 12 Backend** - Robust PHP framework with excellent developer experience
- 🛠️ **Filament Admin Panel** - Beautiful admin interface for managing the application
- 💬 **WhatsApp Integration Ready** - Built to handle WhatsApp chat distribution
- 🎯 **Round-Robin Logic** - Efficient chat assignment system

## Tech Stack

- **Frontend**: Vue.js 3 with Vite
- **Backend**: Laravel 12
- **Admin Panel**: Filament v5
- **Styling**: Tailwind CSS
- **Database**: MySQL/PostgreSQL/SQLite (configurable)

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 20.x or higher
- npm or yarn
- Database (MySQL, PostgreSQL, or SQLite)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/voxsar/round-robin-whatsapp-chat.git
   cd round-robin-whatsapp-chat
   ```

2. **Navigate to the backend directory**
   ```bash
   cd backend
   ```

3. **Install PHP dependencies**
   ```bash
   composer install
   ```

4. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

5. **Set up environment file**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. **Configure your database**
   
   Edit the `.env` file and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Create an admin user for Filament**
   ```bash
   php artisan make:filament-user
   ```

9. **Build frontend assets**
   ```bash
   npm run build
   ```

## Development

To run the application in development mode:

1. **Start the Laravel development server**
   ```bash
   php artisan serve
   ```

2. **In a separate terminal, start Vite dev server**
   ```bash
   npm run dev
   ```

3. **Access the application**
   - Frontend: http://localhost:8000
   - Admin Panel: http://localhost:8000/admin

## Production

To prepare for production:

1. **Build optimized frontend assets**
   ```bash
   npm run build
   ```

2. **Optimize Laravel**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Set up your web server** (Apache/Nginx) to point to the `public` directory

## Project Structure

```
backend/
├── app/                    # Application code
│   ├── Http/              # Controllers, Middleware
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers (including Filament)
├── config/                # Configuration files
├── database/              # Migrations, seeders, factories
├── public/                # Public assets
├── resources/             # Views, JavaScript, CSS
│   ├── css/              # Styles
│   ├── js/               # Vue.js components and app
│   │   ├── components/   # Vue components
│   │   └── app.js        # Main Vue app
│   └── views/            # Blade templates
├── routes/                # Application routes
├── storage/               # Logs, cache, uploads
└── tests/                 # Tests
```

## Available Commands

### Laravel Commands
```bash
php artisan serve              # Start development server
php artisan migrate            # Run database migrations
php artisan make:filament-user # Create Filament admin user
php artisan tinker             # Laravel REPL
```

### NPM Commands
```bash
npm run dev      # Start Vite development server
npm run build    # Build for production
npm run lint     # Run linter (if configured)
```

## License

This project is open-sourced software licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, please open an issue in the GitHub repository.