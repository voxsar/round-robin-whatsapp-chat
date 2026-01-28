# Round Robin WhatsApp Chat

A Laravel-based web application that creates WhatsApp group chats with round-robin agent assignment. Features a Vue.js chat widget that connects visitors to WhatsApp groups via API integration with real-time messaging through Pusher.

## Features

- **Chat Widget**: Embedded Vue.js chat interface
- **WhatsApp Integration**: Automatically creates WhatsApp groups for each chat session
- **Round-Robin Assignment**: Distributes chats among available agents
- **Real-time Messaging**: Uses Pusher for instant message delivery
- **Session Management**: Tracks chat sessions and participants
- **Webhook Support**: Receives WhatsApp message notifications

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL/MariaDB
- WhatsApp Business API access
- Pusher account

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd round-robin-whatsapp-chat
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=round_robin
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Configure Pusher

Sign up at [https://pusher.com](https://pusher.com) and get your credentials:

```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 6. Configure WhatsApp API

Update `.env` with your WhatsApp API details:

```env
# WhatsApp API Configuration
WHATSAPP_BASE_URL=https://your-whatsapp-api.com
WHATSAPP_API_KEY=your_api_key
WHATSAPP_INSTANCE=your_instance_name

# Participant Configuration
WHATSAPP_BOT_NUMBER=+1234567890
WHATSAPP_FIXED_PARTICIPANTS=+1234567891,+1234567892
WHATSAPP_PARTICIPANT_POOL=+1234567893,+1234567894,+1234567895
WHATSAPP_ROUND_ROBIN=true
```

### 7. Run migrations

```bash
php artisan migrate
```

### 8. Build frontend assets

```bash
npm run build
# Or for development with hot reload:
npm run dev
```

### 9. Start the application

```bash
php artisan serve
```

Visit `http://localhost:8000` to see the chat widget.

## Configuration

### Round-Robin vs Fixed Participants

In `config/whatsapp.php` and `.env`:

**Fixed Participants Mode** (WHATSAPP_ROUND_ROBIN=false):
- All chats include all participants from `WHATSAPP_FIXED_PARTICIPANTS`

**Round-Robin Mode** (WHATSAPP_ROUND_ROBIN=true):
- Each chat randomly selects one participant from `WHATSAPP_PARTICIPANT_POOL`
- Distributes workload evenly among agents

### Bot Number

`WHATSAPP_BOT_NUMBER` is always included in every group (typically your business number).

## API Endpoints

### Create Chat Session

```bash
POST /chat/session
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "mobile": "+1234567890"
}
```

**Response:**
```json
{
  "session": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "group_jid": "120363123456789@g.us",
    "status": "active"
  },
  "group": {
    "remoteJid": "120363123456789@g.us"
  },
  "channel": "session-1"
}
```

### Send Message

```bash
POST /chat/message
Content-Type: application/json

{
  "session_id": 1,
  "text": "Hello, I need help!"
}
```

**Response:**
```json
{
  "status": "sent"
}
```

### WhatsApp Webhook

```bash
POST /webhooks/whatsapp
X-Webhook-Signature: <signature>

{
  "event": "message.received",
  "data": {
    "message": "Response from agent",
    "from": "+1234567891"
  }
}
```

## Frontend Integration

The chat widget is automatically loaded on the `/` route. To embed it elsewhere:

```html
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div 
        id="app"
        data-pusher-key="{{ config('services.pusher.key') }}"
        data-pusher-cluster="{{ config('services.pusher.cluster') }}"
    ></div>
</body>
</html>
```

## Troubleshooting

### No messages are being sent

1. Check WhatsApp API credentials in `.env`
2. Verify `WHATSAPP_BASE_URL` and `WHATSAPP_INSTANCE` are correct
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Real-time messages not working

1. Verify Pusher credentials in `.env`
2. Check browser console for Pusher connection errors
3. Ensure frontend assets are built: `npm run build`

### WhatsApp groups not being created

1. Verify participant phone numbers include country code (e.g., +1234567890)
2. Check if participants exist in WhatsApp
3. Verify API permissions for group creation

### Webhook not receiving messages

1. Verify webhook URL is configured in your WhatsApp provider
2. Ensure the webhook endpoint is publicly accessible
3. Check webhook logs: `tail -f storage/logs/laravel.log | grep "WhatsApp Webhook"`
4. Test locally: `php test-webhook.php`
5. Verify CSRF is disabled for webhook route in `bootstrap/app.php`

### Messages received but not broadcasting

1. Verify Pusher credentials are correct
2. Check that chat session exists with matching `group_jid`
3. Ensure session status is 'active'
4. Check Pusher debug console for events

### Frontend not connecting to backend

1. Ensure CSRF token is present in the page
2. Check if routes are registered: `php artisan route:list`
3. Verify database session is configured: `SESSION_DRIVER=database`

## Development

### Run with hot reload

```bash
npm run dev
php artisan serve
```

### Queue workers (for background jobs)

```bash
php artisan queue:work
```

### Run tests

```bash
php artisan test
```

## Project Structure

```
app/
├── Http/Controllers/
│   └── ChatSessionController.php    # Main chat API controller
├── Models/
│   └── ChatSession.php              # Session model
├── Services/
│   ├── WhatsappClient.php           # WhatsApp API client
│   ├── PusherClient.php             # Pusher client
│   └── ParticipantSelector.php      # Round-robin logic
config/
├── services.php                      # API credentials
└── whatsapp.php                      # WhatsApp configuration
resources/
├── js/
│   ├── app.js                        # Vue app entry
│   └── components/
│       └── App.vue                   # Chat widget component
└── views/
    └── app.blade.php                 # Main view
routes/
├── web.php                           # Web routes
└── api.php                           # API routes (if any)
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
