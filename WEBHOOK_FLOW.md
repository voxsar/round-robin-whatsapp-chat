# WhatsApp Webhook Flow - Complete Guide

## Architecture Overview

```
WhatsApp Provider → Webhook Endpoint → Find Session → Broadcast to Pusher → Frontend
```

## Complete Message Flow

### 1. Incoming Message from WhatsApp
- User sends message in WhatsApp group
- WhatsApp provider sends webhook POST request to `/api/webhooks/whatsapp`

### 2. Webhook Processing
**File:** `app/Http/Controllers/WebhookController.php`

```php
// Extract data from webhook
$event = $payload['event']; // "messages.upsert"
$groupJid = $data['key']['remoteJid']; // "120363422186326906@g.us"
$messageText = $data['message']['conversation']; // "Hello!"
```

### 3. Session Lookup
```php
// Find active chat session matching the WhatsApp group
$session = ChatSession::where('group_jid', $groupJid)
    ->where('status', 'active')
    ->first();
```

### 4. Pusher Broadcast
**Service:** `app/Services/PusherClient.php`

```php
// Broadcast to session channel
$pusher->trigger("session-{$session->id}", 'message', [
    'message' => [
        'id' => 'MESSAGE_ID',
        'sender' => 'agent',
        'text' => 'Hello!',
        'timestamp' => '2026-01-21T01:25:23+00:00'
    ]
]);
```

### 5. Frontend Receives Message
The Vue.js chat widget subscribes to the Pusher channel and displays the message in real-time.

## Webhook Payload Structure

### What You Receive
```json
[{
  "body": {
    "event": "messages.upsert",
    "data": {
      "key": {
        "remoteJid": "120363422186326906@g.us",  // ← Group ID
        "fromMe": false,                          // ← Not from bot
        "id": "AC65ABFB8C40B49FBF265432D19ED2C4" // ← Message ID
      },
      "pushName": "voxsar",                       // ← Sender name
      "message": {
        "conversation": "Hi"                      // ← Message text
      },
      "messageTimestamp": 1768969523              // ← Unix timestamp
    },
    "sender": "94789210953@s.whatsapp.net"       // ← Sender number
  }
}]
```

### What Gets Broadcast to Pusher
```json
{
  "message": {
    "id": "AC65ABFB8C40B49FBF265432D19ED2C4",
    "sender": "agent",
    "sender_name": "voxsar",
    "sender_number": "94789210953@s.whatsapp.net",
    "text": "Hi",
    "timestamp": "2026-01-21T01:25:23+00:00"
  }
}
```

## Key Features

### 1. CSRF Protection Disabled
**File:** `bootstrap/app.php`

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/whatsapp',  // ← Webhook can receive external requests
]);
```

### 2. Message Type Support
The webhook handles multiple message formats:
- Plain text: `message.conversation`
- Extended text: `message.extendedTextMessage.text`
- Image captions: `message.imageMessage.caption`
- Video captions: `message.videoMessage.caption`

### 3. Self-Message Filtering
```php
$fromMe = $data['key']['fromMe'] ?? false;

if ($fromMe) {
    return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
}
```

Prevents bot messages from being re-broadcast.

### 4. Logging
```php
Log::info('WhatsApp Webhook Received', ['payload' => $payload]);
```

All webhooks are logged for debugging.

## Testing Workflow

### 1. Create a Chat Session
```bash
curl -X POST http://localhost:8000/api/chat/session \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com"
  }'
```

**Response includes:**
- `session.id` - Use for sending messages
- `session.group_jid` - WhatsApp group ID
- `channel` - Pusher channel name

### 2. Configure Webhook in WhatsApp Provider
Point your WhatsApp provider webhook to:
```
https://your-domain.com/api/webhooks/whatsapp
```

### 3. Send Message in WhatsApp Group
The message will automatically:
1. Arrive at webhook endpoint
2. Be matched to active session
3. Broadcast to Pusher channel
4. Display in frontend widget

### 4. Monitor Logs
```bash
tail -f storage/logs/laravel.log | grep "WhatsApp Webhook"
```

## Response Codes

| Status | Reason | Description |
|--------|--------|-------------|
| `ok` | - | Message successfully broadcast |
| `ignored` | `not_message_event` | Event is not messages.upsert |
| `ignored` | `no_data` | Missing data field |
| `ignored` | `no_group_jid` | Can't identify group |
| `ignored` | `no_text_content` | Message has no text |
| `ignored` | `from_me` | Message sent by bot |
| `unknown_session` | - | No active session found for group |

## Security Considerations

1. **External Access**: Webhook must be publicly accessible
2. **CSRF Disabled**: Only for `/api/webhooks/whatsapp` endpoint
3. **Validation**: Only processes `messages.upsert` events
4. **Session Matching**: Only broadcasts to active sessions
5. **Bot Filter**: Prevents message loops

## Debugging Tips

### Check if webhook is receiving data
```bash
# Watch logs
tail -f storage/logs/laravel.log

# Test locally
php test-webhook.php
```

### Verify session exists
```bash
php artisan tinker
>>> ChatSession::where('group_jid', '120363422186326906@g.us')->first()
```

### Test Pusher manually
```php
use App\Services\PusherClient;

$pusher = app(PusherClient::class);
$pusher->trigger('session-1', 'message', [
    'message' => ['text' => 'Test']
]);
```

### Check route registration
```bash
php artisan route:list | grep webhook
```

## Files Modified/Created

### Core Implementation
- ✅ `app/Http/Controllers/WebhookController.php` - Webhook handler (updated)
- ✅ `routes/api.php` - Webhook route (already exists)
- ✅ `bootstrap/app.php` - CSRF exception (already configured)

### Documentation
- ✅ `WEBHOOK_INTEGRATION.md` - Complete webhook documentation
- ✅ `QUICK_REFERENCE.md` - Updated with webhook endpoint
- ✅ `README.md` - Added webhook troubleshooting

### Testing
- ✅ `test-webhook.php` - Test script
- ✅ `webhook-sample.json` - Sample payload

## Next Steps

1. **Deploy application** to publicly accessible server
2. **Configure webhook URL** in WhatsApp provider settings
3. **Test end-to-end flow** with real WhatsApp messages
4. **Monitor logs** for any issues
5. **Set up monitoring** for webhook uptime

## Support

If issues persist:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify Pusher events in Pusher dashboard
3. Test webhook with curl or test script
4. Ensure firewall allows incoming webhook requests
