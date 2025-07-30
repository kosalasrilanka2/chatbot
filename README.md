# Laravel Chatbot Agent Application

A real-time chatbot agent application built with Laravel 12, Laravel Reverb WebSocket server, and Laravel Echo for instant messaging between users and agents.

## 🚀 Features

- **Real-time messaging** between users and agents via WebSocket
- **Intelligent auto-assignment** of conversations to available agents
- **Agent workload balancing** with fair distribution algorithms
- **Agent notifications** for new incoming messages
- **Multiple conversation support** with proper routing
- **User authentication** with Laravel Breeze
- **Agent dashboard** with conversation management
- **Admin dashboard** for monitoring system activity (no auth required)
- **WebSocket connectivity** with Laravel Reverb
- **Message persistence** in MySQL database
- **Responsive UI** with Alpine.js and Tailwind CSS

### 🤖 **Automatic Assignment System**
- **Smart Distribution**: Assigns new conversations to least busy online agents
- **Fallback to Manual**: When no agents online, conversations wait for manual pickup
- **Load Balancing**: Uses conversation count + last activity time for fair distribution
- **Auto-redistribution**: Redistributes conversations when agents go offline
- **Workload Monitoring**: Real-time agent workload tracking in admin dashboard

## 🛠 Technology Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL
- **Real-time**: Laravel Reverb (WebSocket server)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS
- **Asset Building**: Vite
- **Authentication**: Laravel Breeze
- **WebSocket Client**: Laravel Echo + Pusher.js

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- MySQL server

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

#### MySQL Configuration
Update your `.env` file with your MySQL database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatbot
DB_USERNAME=chatbotdbuser
DB_PASSWORD=abc123
```

#### Run Migrations and Setup
```bash
# Option 1: Quick setup (recommended)
php artisan chatbot:setup

# Option 2: Manual setup
php artisan migrate
php artisan users:create
php artisan data:sample
```

This will create all necessary tables and populate them with:
- 4 test users (2 regular users + 2 agent users)
- 2 agents with proper authentication
- 3 sample conversations with 8 messages
- Mix of read/unread messages for testing

### 5. Build assets and start servers
```bash
# Terminal 1: Build assets
npm run dev

# Terminal 2: Start Laravel server
php artisan serve

# Terminal 3: Start Reverb WebSocket server
php artisan reverb:start
```

## 🎯 Usage

### User Chat Interface (Simple)
- Navigate to: `http://localhost:8000/chat`
- Login with: `user@test.com` / `password` or `john@test.com` / `password`
- **Simplified navigation** - Only shows "Support Chat" tab (no confusing agent/admin links)
- **Clean interface** with dedicated user layout template
- Simple chat window that automatically creates conversations and assigns to available agents
- No conversation management - just type and start chatting

### Agent Dashboard (Full Management)
- Navigate to: `http://localhost:8000/agent` (simplified URL!)
- Login with: `agent@test.com` / `password` or `senior@test.com` / `password`
- **Clean navigation** - Only shows "Agent Dashboard" (no confusing user links)
- Manage multiple conversations simultaneously
- View and respond to user conversations in real-time
- Receive instant notifications for new messages
- Full conversation management interface

### Chat Management Interface (Advanced)
- Navigate to: `http://localhost:8000/chat/manage`
- Full chat management interface for power users
- Multiple conversation support with conversation list
- Note: Most agents should use the main Agent Dashboard at `/agent`

## 🔧 Configuration

### Environment Variables
Key environment variables in `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatbot
DB_USERNAME=chatbotdbuser
DB_PASSWORD=abc123

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
