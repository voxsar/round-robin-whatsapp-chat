#!/bin/bash

# WhatsApp Webhook Test Suite
# This script tests the complete webhook integration

echo "================================================"
echo "WhatsApp Webhook Integration Test Suite"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="${BASE_URL:-http://localhost:8000}"

echo "Using base URL: $BASE_URL"
echo ""

# Test 1: Check if webhook route exists
echo -e "${YELLOW}Test 1: Checking webhook route...${NC}"
ROUTE_CHECK=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/api/webhooks/whatsapp" \
  -H "Content-Type: application/json" \
  -d '{}')

if [ "$ROUTE_CHECK" != "000" ]; then
    echo -e "${GREEN}✓ Webhook endpoint is accessible${NC}"
else
    echo -e "${RED}✗ Webhook endpoint is not accessible${NC}"
    exit 1
fi
echo ""

# Test 2: Send valid webhook payload
echo -e "${YELLOW}Test 2: Sending valid webhook payload...${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/api/webhooks/whatsapp" \
  -H "Content-Type: application/json" \
  -d '[{
    "body": {
      "event": "messages.upsert",
      "data": {
        "key": {
          "remoteJid": "120363422186326906@g.us",
          "fromMe": false,
          "id": "TEST_MESSAGE_001"
        },
        "pushName": "Test User",
        "message": {
          "conversation": "This is a test message"
        },
        "messageTimestamp": 1737421523
      },
      "sender": "94789210953@s.whatsapp.net"
    }
  }]')

echo "Response: $RESPONSE"
STATUS=$(echo $RESPONSE | grep -o '"status":"[^"]*"' | cut -d'"' -f4)

if [ "$STATUS" = "unknown_session" ]; then
    echo -e "${YELLOW}✓ Webhook processed correctly (no session found - expected)${NC}"
elif [ "$STATUS" = "ok" ]; then
    echo -e "${GREEN}✓ Webhook processed successfully!${NC}"
else
    echo -e "${RED}✗ Unexpected response: $RESPONSE${NC}"
fi
echo ""

# Test 3: Test with invalid event type
echo -e "${YELLOW}Test 3: Testing event filtering...${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/api/webhooks/whatsapp" \
  -H "Content-Type: application/json" \
  -d '[{
    "body": {
      "event": "messages.delete",
      "data": {
        "key": {
          "remoteJid": "120363422186326906@g.us"
        }
      }
    }
  }]')

echo "Response: $RESPONSE"
STATUS=$(echo $RESPONSE | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
REASON=$(echo $RESPONSE | grep -o '"reason":"[^"]*"' | cut -d'"' -f4)

if [ "$STATUS" = "ignored" ] && [ "$REASON" = "not_message_event" ]; then
    echo -e "${GREEN}✓ Event filtering works correctly${NC}"
else
    echo -e "${RED}✗ Event filtering not working as expected${NC}"
fi
echo ""

# Test 4: Test self-message filtering
echo -e "${YELLOW}Test 4: Testing self-message filter...${NC}"
RESPONSE=$(curl -s -X POST "$BASE_URL/api/webhooks/whatsapp" \
  -H "Content-Type: application/json" \
  -d '[{
    "body": {
      "event": "messages.upsert",
      "data": {
        "key": {
          "remoteJid": "120363422186326906@g.us",
          "fromMe": true,
          "id": "TEST_MESSAGE_002"
        },
        "message": {
          "conversation": "Message from bot"
        }
      }
    }
  }]')

echo "Response: $RESPONSE"
STATUS=$(echo $RESPONSE | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
REASON=$(echo $RESPONSE | grep -o '"reason":"[^"]*"' | cut -d'"' -f4)

if [ "$STATUS" = "ignored" ] && [ "$REASON" = "from_me" ]; then
    echo -e "${GREEN}✓ Self-message filter works correctly${NC}"
else
    echo -e "${RED}✗ Self-message filter not working as expected${NC}"
fi
echo ""

# Summary
echo "================================================"
echo -e "${GREEN}Webhook integration test complete!${NC}"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Create a chat session via: POST $BASE_URL/api/chat/session"
echo "2. Note the 'group_jid' from the response"
echo "3. Update the webhook test with the actual group_jid"
echo "4. Configure your WhatsApp provider webhook URL to:"
echo "   $BASE_URL/api/webhooks/whatsapp"
echo ""
