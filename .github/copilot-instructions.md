# Copilot Instructions for Round Robin WhatsApp Chat

## Architecture Overview

Laravel 12 + Vue 3 + Filament 4 application bridging a web chat widget with WhatsApp groups via external API. Real-time messaging via Pusher.

**Core Flow:**
1. Visitor submits pre-chat form → `ChatSessionController::store()` creates `ChatSession` + WhatsApp group via `WhatsappClient`
2. Messages flow bidirectionally: Widget ↔ Pusher ↔ WhatsApp webhook
3. `ParticipantSelector` assigns agents using round-robin or fixed-participant modes

## Data Model

```
User (role: admin|manager|agent)
  └── manager_id → User (hierarchical management)
  └── assignedPeople → Person[]
  └── assignedChatSessions → ChatSession[]

Person (customer record)
  └── stage: new|qualified|in_progress|resolved|archived
  └── chatSessions → ChatSession[]

ChatSession
  └── group_jid (WhatsApp group identifier for webhook matching)
  └── pusher_channel (format: group-{group_id})
  └── messages → ChatMessage[]
  └── person_id, assigned_user_id

ChatMessage
  └── sender: visitor|agent|system
  └── source: widget|whatsapp|away|ended
```

## Key Services (`app/Services/`)

| Service | Purpose |
|---------|---------|
| `WhatsappClient` | **Primary** - WhatsApp API wrapper (groups, messages, participants) |
| `WhatsAppProvider` | Legacy message sender - prefer `WhatsappClient` for new code |
| `PusherClient` | Simple Pusher SDK wrapper for broadcasting |
| `PusherBroadcaster` | Manual HTTP-based Pusher client (alternative) |
| `ParticipantSelector` | Round-robin/fixed agent assignment logic |
| `StageMembershipService` | Auto-add/remove WhatsApp participants on Person stage changes |
| `ChatInactivityService` | Scheduled away/end messages for idle sessions |

## Critical Patterns

### Webhook Flow (WhatsApp → Frontend)
1. External WhatsApp API sends POST to `/webhooks/whatsapp` (CSRF disabled in `bootstrap/app.php`)
2. `WebhookController::handleWhatsApp()` extracts `group_jid` from payload
3. Matches to `ChatSession` record via `group_jid` column
4. Broadcasts to Pusher channel `group-{group_jid}`
5. Vue widget receives message in real-time

### Webhook Commands
Agents can send commands from WhatsApp:
- `/endchat` - Ends session, sends end message to widget
- `/block` - Blocks session, prevents further messages

### Pusher Channel Format
- Channel: `group-{group_id}` or `group-{group_jid}`
- Event: `message`
- Payload: `{message: {id, sender, sender_name, text, timestamp}}`

### Stage-Based Participant Management
When `Person.stage` changes, `PersonObserver` triggers `StageMembershipService::syncStage()`:
- Adds participants from `WHATSAPP_STAGE_{STAGE}_PARTICIPANTS`
- Removes participants from `WHATSAPP_STAGE_{STAGE}_REMOVE_PARTICIPANTS`
- Sends stage change notification to WhatsApp group

**Stages:** `new` → `qualified` → `in_progress` → `resolved` → `archived`

### Configuration Split
| Config File | Contains |
|-------------|----------|
| `config/whatsapp.php` | Participant pools, stage-based assignments, endpoint templates |
| `config/services.php` | API credentials (`services.whatsapp.*`, `services.pusher.*`) |
| `config/chat.php` | Inactivity timeouts (`away_after_minutes`, `end_after_minutes`), system messages |

### Runtime Settings via Database
`ChatSetting::current()` returns singleton settings that override config values:
- `bot_number`, `away_after_minutes`, `end_after_minutes`
- `away_message`, `end_message`, `user_end_message`

## Frontend Architecture

Vue 3 chat widget in `resources/js/components/App.vue`:
- Pre-chat form collects name, email, mobile
- Consent checkboxes for contact saving and group joining
- Pusher subscription via `pusher-js` for real-time updates
- Filament embed mode supports session lookup by ID/email/mobile

**Key API calls from widget:**
- `POST /chat/session` - Create new session
- `POST /chat/session/lookup` - Find existing session
- `POST /chat/message` - Send message from widget
- `POST /chat/session/end` - End session from widget

## Filament Admin

Admin panel at `/admin` (Filament 4). Resources in `app/Filament/Admin/Resources/`:

| Resource | Purpose |
|----------|---------|
| `ChatSessionResource` | View/manage sessions, send messages, end/block chats |
| `PersonResource` | Customer records with stage workflow, role-based filtering |
| `ChatSettingResource` | Runtime config (single record) |
| `UserResource` | Admin/manager/agent users with hierarchy |

**Role-based access:** Managers see their direct reports' data. Agents see only their assigned records.

## Development Commands

```bash
composer dev          # Runs server + queue + logs (pail) + Vite concurrently
composer test         # Clears config + runs PHPUnit
composer setup        # Full install: deps, key, migrations, npm build
vendor/bin/pint       # Format PHP code
```

## Testing Webhooks

```bash
./test-webhook.sh     # Simulates incoming WhatsApp webhook (bash)
php test-webhook.php  # PHP-based webhook test
```

Sample payload structure in `webhook-sample.json`.

## Environment Variables

**WhatsApp API:**
```env
WHATSAPP_BASE_URL=https://api.example.com
WHATSAPP_API_KEY=your_key
WHATSAPP_INSTANCE=instance_name
WHATSAPP_BOT_NUMBER=+1234567890          # Always added to groups
```

**Participant Modes:**
```env
WHATSAPP_ROUND_ROBIN=true                 # true=pool rotation, false=fixed
WHATSAPP_PARTICIPANT_POOL=+123,+456,+789  # For round-robin mode
WHATSAPP_FIXED_PARTICIPANTS=+123,+456     # For fixed mode
```

**Stage-based auto-assignment:**
```env
WHATSAPP_STAGE_NEW_PARTICIPANTS=+123
WHATSAPP_STAGE_QUALIFIED_PARTICIPANTS=+456
WHATSAPP_STAGE_QUALIFIED_REMOVE_PARTICIPANTS=+123  # Remove when entering qualified
```

**Pusher:**
```env
PUSHER_APP_ID=xxx
PUSHER_APP_KEY=xxx
PUSHER_APP_SECRET=xxx
PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## Code Conventions

- Laravel Pint for PHP formatting
- Services use constructor injection via Laravel container
- Models use `$fillable` arrays (never `$guarded = []`)
- Prefer `WhatsappClient` over `WhatsAppProvider` for new code
- Filament resources in `app/Filament/Admin/Resources/` with Pages subdirectories
- Use `ChatSetting::current()` for runtime-configurable values
