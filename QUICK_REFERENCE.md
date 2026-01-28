# Quick Reference Guide - Round Robin WhatsApp Chat

## Quick Start

```bash
# Start the application
php artisan serve

# Start with custom host/port
php artisan serve --host=0.0.0.0 --port=8000

# Run in development mode (with hot reload)
npm run dev
```

## API Endpoints

### 1. Create Chat Session
**Endpoint:** `POST /chat/session`

**Request:**
```json
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
    "group_jid": "120363123456789@g.us",
    "status": "active"
  },
  "group": { "remoteJid": "120363123456789@g.us" },
  "channel": "session-1"
}
```

### 2. Send Message
**Endpoint:** `POST /chat/message`

**Request:**
```json
{
  "session_id": 1,
  "text": "Hello, I need assistance!"
}
```

**Response:**
```json
{
  "status": "sent"
}
```

### 3. WhatsApp Webhook (Incoming Messages)
**Endpoint:** `POST /api/webhooks/whatsapp`

**CSRF:** Disabled  
**Purpose:** Receives incoming WhatsApp messages and broadcasts to active Pusher sessions

**Request Example:**
```json
[{
  "body": {
    "event": "messages.upsert",
    "data": {
      "key": {
        "remoteJid": "120363422186326906@g.us",
        "fromMe": false,
        "id": "MESSAGE_ID"
      },
      "pushName": "User Name",
      "message": {
        "conversation": "Hello from WhatsApp!"
      },
      "messageTimestamp": 1737421523
    },
    "sender": "94789210953@s.whatsapp.net"
  }
}]
```

**Response:**
```json
{
  "status": "ok",
  "session_id": 1
}
```

**Test Webhook:**
```bash
# Using test script
php test-webhook.php

# Using curl
curl -X POST http://localhost:8000/api/webhooks/whatsapp \
  -H "Content-Type: application/json" \
  -d @webhook-sample.json
```

## Testing with cURL

### Create a session
```bash
curl -X POST http://localhost:8000/chat/session \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "mobile": "+1234567890"
  }'
```

### Send a message
```bash
curl -X POST http://localhost:8000/chat/message \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "session_id": 1,
    "text": "This is a test message"
  }'
```

## Environment Variables

### Essential Variables
```env
# Database
DB_DATABASE=round_robin
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Pusher (Real-time)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap2

# WhatsApp API
WHATSAPP_BASE_URL=https://your-api.com
WHATSAPP_API_KEY=your_api_key
WHATSAPP_INSTANCE=your_instance

# Participants
WHATSAPP_BOT_NUMBER=+1234567890
WHATSAPP_PARTICIPANT_POOL=+1234567891,+1234567892
WHATSAPP_ROUND_ROBIN=true
```

## Common Commands

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# List all routes
php artisan route:list

# Run migrations
php artisan migrate

# View database info
php artisan db:show

# Build frontend assets
npm run build

# Development mode
npm run dev

# View logs
tail -f storage/logs/laravel.log
```

## Debugging

### Check if routes are registered
```bash
php artisan route:list --path=chat
```

### Test database connection
```bash
php artisan db:show
```

### Check configuration values
```bash
php artisan tinker
>>> config('services.whatsapp')
>>> config('services.pusher')
```

### View real-time logs
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Issue: "Session not found"
- Ensure the database migration has run
- Check if `chat_sessions` table exists

### Issue: "WhatsApp instance not configured"
- Verify `WHATSAPP_INSTANCE` in `.env`
- Check `config/services.php` has correct values

### Issue: "No real-time updates"
- Verify Pusher credentials
- Check browser console for Pusher connection
- Ensure frontend assets are built: `npm run build`

### Issue: "Group not created"
- Verify participant phone numbers format (+countrycode)
- Check WhatsApp API credentials
- Review Laravel logs for API errors

### Issue: "CSRF token mismatch"
- Ensure `<meta name="csrf-token">` is in HTML
- Clear browser cache
- Check `SESSION_DRIVER=database` in `.env`

## Project Structure

```
/var/www/round-robin-whatsapp-chat/
├── app/
│   ├── Http/Controllers/
│   │   └── ChatSessionController.php   # Main API
│   ├── Models/
│   │   └── ChatSession.php             # Session model
│   └── Services/
│       ├── WhatsappClient.php          # WhatsApp API
│       ├── PusherClient.php            # Real-time messaging
│       └── ParticipantSelector.php     # Round-robin logic
├── config/
│   ├── services.php                    # API config
│   └── whatsapp.php                    # Participant config
├── resources/
│   ├── js/
│   │   └── components/App.vue          # Chat widget
│   └── views/
│       └── app.blade.php               # Main page
├── routes/
│   └── web.php                         # Routes
└── .env                                 # Environment config
```

## Production Deployment

```bash
# Set environment to production
APP_ENV=production
APP_DEBUG=false

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm run build

# Set up queue worker (process.conf or supervisor)
php artisan queue:work --tries=3

# Set up cron for scheduled tasks
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Verify setup: `./verify-setup.sh`
3. Review [README.md](README.md) for detailed documentation
