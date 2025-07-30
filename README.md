# Laravel Chatbot Agent Application

A real-time chatbot agent application built with Laravel 12, Laravel Reverb WebSocket server, and Laravel Echo for instant messaging between users and agents.

## 🚀 Features

- **Real-time messaging** between users and agents via WebSocket
- **Agent notifications** for new incoming messages
- **Multiple conversation support** with proper routing
- **User authentication** with Laravel Breeze
- **Agent dashboard** with conversation management
- **WebSocket connectivity** with Laravel Reverb
- **Message persistence** in SQLite database
- **Responsive UI** with Alpine.js and Tailwind CSS

## 🛠 Technology Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: SQLite
- **Real-time**: Laravel Reverb (WebSocket server)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS
- **Asset Building**: Vite
- **Authentication**: Laravel Breeze
- **WebSocket Client**: Laravel Echo + Pusher.js

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- SQLite

## ⚡ Quick Start

### 1. Clone the repository
```bash
git clone <your-repo-url>
cd chatbot
```

### 2. Install dependencies
```bash
composer install
npm install
```

### 3. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Create test users
```bash
php artisan tinker
```
Then run:
```php
// Create a test user
User::create(['name' => 'Test User', 'email' => 'user@example.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]);

// Create a test agent
App\Models\Agent::create(['name' => 'Agent Smith', 'email' => 'agent@example.com', 'status' => 'online', 'last_seen' => now()]);
```

### 6. Build assets and start servers
```bash
# Terminal 1: Build assets
npm run dev

# Terminal 2: Start Laravel server
php artisan serve

# Terminal 3: Start Reverb WebSocket server
php artisan reverb:start
```

## 🎯 Usage

### User Chat Interface
- Navigate to: `http://localhost:8000/chat`
- Login with: `user@example.com` / `password`
- Create conversations and send messages in real-time

### Agent Dashboard
- Navigate to: `http://localhost:8000/agent/dashboard`
- Login with: `agent@example.com` / `password`
- View and respond to user conversations in real-time
- Receive instant notifications for new messages

## 🔧 Configuration

### Environment Variables
Key environment variables in `.env`:

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Broadcasting
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync

# Reverb WebSocket
REVERB_APP_ID=chatbot-app
REVERB_APP_KEY=reverb-app-key
REVERB_APP_SECRET=reverb-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite (for frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 🧪 Testing Commands

```bash
# Test WebSocket broadcasting
php artisan broadcast:test

# Test real-time message flow
php artisan chat:test-realtime

# Test Laravel-Reverb connection
php artisan reverb:test-laravel-connection
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
