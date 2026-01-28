# WhatsApp Webhook Integration

## Overview

The webhook endpoint receives incoming WhatsApp messages and broadcasts them to active Pusher chat sessions in real-time.

## Endpoint

```
POST /api/webhooks/whatsapp
```

**CSRF Protection:** Disabled (configured in bootstrap/app.php)

## How It Works

1. **Webhook receives message** from WhatsApp provider
2. **Extracts group JID** from the payload
3. **Finds active chat session** matching the group JID
4. **Parses message content** (text, image captions, etc.)
5. **Broadcasts to Pusher** on the session's channel
6. **Updates session** last activity timestamp

## Webhook Payload Format

The webhook expects the following structure:

```json
[
  {
    "body": {
      "event": "messages.upsert",
      "instance": "WhatsApp",
      "data": {
        "key": {
          "remoteJid": "120363422186326906@g.us",
          "fromMe": false,
          "id": "AC65ABFB8C40B49FBF265432D19ED2C4"
        },
        "pushName": "User Name",
        "message": {
          "conversation": "Message text"
        },
        "messageTimestamp": 1768969523
      },
      "sender": "94789210953@s.whatsapp.net"
    }
  }
]
```

## Supported Message Types

- **Text messages**: `message.conversation`
- **Extended text**: `message.extendedTextMessage.text`
- **Image captions**: `message.imageMessage.caption`
- **Video captions**: `message.videoMessage.caption`

## Pusher Broadcast Format

Messages are broadcast to the channel `session-{session_id}` with event name `message`:

```json
{
  "message": {
    "id": "AC65ABFB8C40B49FBF265432D19ED2C4",
    "sender": "agent",
    "sender_name": "User Name",
    "sender_number": "94789210953@s.whatsapp.net",
    "text": "Message text",
    "timestamp": "2026-01-21T01:25:23+00:00"
  }
}
```

## Response Codes

### Success
```json
{
  "status": "ok",
  "session_id": 123
}
```

### Ignored Cases
```json
{
  "status": "ignored",
  "reason": "not_message_event|no_data|no_group_jid|no_text_content|from_me"
}
```

### Session Not Found
```json
{
  "status": "unknown_session",
  "group_jid": "120363422186326906@g.us"
}
```

## Testing

Use the provided test script:

```bash
php test-webhook.php
```

Or test with curl:

```bash
curl -X POST http://localhost:8000/api/webhooks/whatsapp \
  -H "Content-Type: application/json" \
  -d @webhook-sample.json
```

## Debugging

All incoming webhooks are logged with full payload:

```bash
tail -f storage/logs/laravel.log | grep "WhatsApp Webhook"
```

## Configuration

Ensure Pusher credentials are configured in `.env`:

```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

## Security Considerations

1. **CSRF Disabled**: The webhook endpoint has CSRF protection disabled to accept external requests
2. **Validation**: Only processes `messages.upsert` events
3. **Session Matching**: Only broadcasts to active sessions with matching group JID
4. **Self-Message Filter**: Ignores messages sent by the bot itself (`fromMe: true`)

## Related Files

- **Controller**: [app/Http/Controllers/WebhookController.php](app/Http/Controllers/WebhookController.php)
- **Route**: [routes/api.php](routes/api.php)
- **Pusher Service**: [app/Services/PusherClient.php](app/Services/PusherClient.php)
- **Chat Model**: [app/Models/ChatSession.php](app/Models/ChatSession.php)
