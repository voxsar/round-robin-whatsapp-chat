#!/bin/bash

# Round Robin WhatsApp Chat - Setup Verification Script

echo "====================================="
echo "Round Robin WhatsApp Chat"
echo "Setup Verification"
echo "====================================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "   Run: cp .env.example .env"
    exit 1
fi
echo "✅ .env file exists"

# Check if APP_KEY is set
if grep -q "APP_KEY=base64:" .env; then
    echo "✅ APP_KEY is set"
else
    echo "❌ APP_KEY not set!"
    echo "   Run: php artisan key:generate"
    exit 1
fi

# Check database connection
echo ""
echo "Checking database connection..."
if php artisan db:show > /dev/null 2>&1; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed!"
    echo "   Check your database credentials in .env"
fi

# Check required environment variables
echo ""
echo "Checking required environment variables..."

check_env_var() {
    local var_name=$1
    local var_value=$(grep "^${var_name}=" .env | cut -d'=' -f2-)
    
    if [ -n "$var_value" ] && [ "$var_value" != "" ]; then
        echo "✅ $var_name is set"
        return 0
    else
        echo "❌ $var_name is NOT set"
        return 1
    fi
}

# Check Pusher credentials
check_env_var "PUSHER_APP_ID"
check_env_var "PUSHER_APP_KEY"
check_env_var "PUSHER_APP_SECRET"
check_env_var "PUSHER_APP_CLUSTER"

# Check WhatsApp credentials
check_env_var "WHATSAPP_BASE_URL"
check_env_var "WHATSAPP_API_KEY"
check_env_var "WHATSAPP_INSTANCE"

echo ""
echo "Checking frontend assets..."
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Frontend assets are built"
else
    echo "❌ Frontend assets not built!"
    echo "   Run: npm run build"
fi

echo ""
echo "Checking routes..."
if php artisan route:list --path=chat > /dev/null 2>&1; then
    echo "✅ Chat routes are registered"
else
    echo "❌ Routes check failed"
fi

echo ""
echo "====================================="
echo "Configuration Summary"
echo "====================================="

# Show current configuration (safely)
echo ""
echo "WhatsApp Configuration:"
echo "  Base URL: $(grep "^WHATSAPP_BASE_URL=" .env | cut -d'=' -f2-)"
echo "  Instance: $(grep "^WHATSAPP_INSTANCE=" .env | cut -d'=' -f2-)"
echo ""
echo "Pusher Configuration:"
echo "  Cluster: $(grep "^PUSHER_APP_CLUSTER=" .env | cut -d'=' -f2-)"
echo ""

echo "====================================="
echo "Next Steps"
echo "====================================="
echo ""
echo "1. Start the application:"
echo "   php artisan serve"
echo ""
echo "2. Visit http://localhost:8000"
echo ""
echo "3. Test the chat widget by:"
echo "   - Clicking the chat button"
echo "   - Filling in your details"
echo "   - Sending a message"
echo ""
echo "4. Check logs if issues occur:"
echo "   tail -f storage/logs/laravel.log"
echo ""
