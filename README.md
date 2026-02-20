# CityStake Bookings

Premium apartment rental booking platform for Abuja's finest properties.

## About

CityStake is a modern, full-stack apartment booking system built with Laravel and Vue.js. It provides a seamless booking experience for guests and a comprehensive management system for administrators.

## Features

### Guest Features
- 🏠 Browse premium apartments in Asokoro, Maitama, and Wuse
- 📅 Real-time availability checking
- 💳 Secure online payments via Paystack
- 📧 Automated booking confirmations and reminders
- 📱 Responsive design for all devices
- 👤 User dashboard to manage bookings
- ❌ Self-service booking cancellation

### Admin Features
- 📊 Comprehensive dashboard with analytics
- 📋 Complete booking management system
- 🏢 Property and unit type CRUD operations
- 🚫 Block dates for maintenance/repairs
- 💰 Walk-in booking creation with offline payments
- 📧 Automated admin notifications
- 🔍 Advanced filtering and search
- 📈 Revenue tracking and reporting

### Technical Features
- ⚡ Server-side rendering with Inertia.js
- 🎨 Beautiful UI with Tailwind CSS
- 🔒 Secure authentication with Laravel Breeze
- 💾 Optimized database queries with eager loading
- 📧 Professional email templates
- 🚀 Performance optimizations (caching, indexing)
- 🌙 Dark mode support

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js
- **Styling:** Tailwind CSS
- **Database:** MySQL
- **Payment:** Paystack
- **Email:** SMTP (Mailtrap for dev, configurable for production)

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- MySQL 8.0+

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/citystake-bookings.git
cd citystake-bookings
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

### 4. Configure your `.env` file
```env
APP_NAME="CityStake"
APP_URL=http://citystake-bookings.test

DB_CONNECTION=mysql
DB_DATABASE=citystake_bookings
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Paystack
PAYSTACK_PUBLIC_KEY=your_public_key
PAYSTACK_SECRET_KEY=your_secret_key

# Mail (use Mailtrap for development)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS="bookings@citystake.net"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_EMAIL="admin@citystake.net"
```

### 5. Database setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Build assets
```bash
npm run dev
```

### 7. Start the development server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## Seeded Data

After running `php artisan db:seed`, you'll have:

- **Buildings:** 3 properties (Asokoro, Maitama, Wuse)
- **Unit Types:** 2-bed, 3-bed, 4-bed apartments
- **Units:** Multiple units per property
- **Test Admin:**
    - Email: `admin@citystake.net`
    - Password: `password`

## Development

### Running in development mode
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (hot reload)
npm run dev
```

### Building for production
```bash
npm run build
```

## Project Structure
```
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/           # Admin panel controllers
│   │   ├── BookingController.php
│   │   └── ...
│   ├── Mail/                # Email templates
│   ├── Models/              # Eloquent models
│   └── Services/            # Business logic services
├── resources/
│   ├── js/
│   │   ├── Components/      # Reusable Vue components
│   │   ├── Layouts/         # Layout components
│   │   └── Pages/           # Inertia pages
│   └── views/
│       └── emails/          # Email blade templates
├── routes/
│   └── web.php              # Application routes
└── database/
    ├── migrations/          # Database migrations
    └── seeders/             # Database seeders
```

## Key Routes

### Public Routes
- `/` - Homepage
- `/properties/{building}/{unitType}` - Property details
- `/about` - About page
- `/contact` - Contact page
- `/terms` - Terms & Conditions
- `/privacy` - Privacy Policy

### User Routes (Authenticated)
- `/bookings` - My bookings
- `/bookings/{booking}` - Booking details

### Admin Routes (Admin only)
- `/admin/dashboard` - Admin dashboard
- `/admin/bookings` - All bookings
- `/admin/properties` - Property management
- `/admin/blocked-dates` - Block dates management

## Testing Payments

For development, use Paystack test cards:

- **Successful:** `4084084084084081`
- **Declined:** `5060666666666666666`

CVV: Any 3 digits
Expiry: Any future date
PIN: 1234 (if required)

## Email Testing

Emails are configured to use Mailtrap in development. Check your Mailtrap inbox at [mailtrap.io](https://mailtrap.io) to view all sent emails.

## Scheduled Tasks

The application includes automated tasks:
```bash
# Check-in reminders (sent daily at 9 AM)
php artisan bookings:send-checkin-reminders
```

To enable scheduling in production, add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Performance Optimization

The application includes several performance optimizations:

- Database query caching
- Indexed columns for fast lookups
- Eager loading to prevent N+1 queries
- Lazy evaluation in Inertia responses

For production deployment:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary and confidential.

## Support

For support, email info@citystake.net or contact us through the website.

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- UI powered by [Tailwind CSS](https://tailwindcss.com)
- Icons by [Lucide](https://lucide.dev)
- Payments by [Paystack](https://paystack.com)

---

**CityStake** - Premium Living in Abuja's Finest Locations
