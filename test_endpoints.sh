#!/usr/bin/env bash
# Simple test script for SIKAP backend API endpoints
# Ensure the Laravel server is running (e.g. php artisan serve --port=8000)
BASE_URL="http://127.0.0.1:8000/api/v1"

echo "Testing locations endpoints..."
# 1. Get municipalities
status=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/locations/municipalities")
if [ "$status" -eq 200 ]; then
  echo "✅ GET /locations/municipalities passed"
else
  echo "❌ GET /locations/municipalities failed (status $status)"
fi

# 2. Get barangays (example municipality param must be URL‑encoded)
status=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/locations/barangays?municipality=Sorsogon%20City")
if [ "$status" -eq 200 ]; then
  echo "✅ GET /locations/barangays passed"
else
  echo "❌ GET /locations/barangays failed (status $status)"
fi

echo "Testing auth flow..."
# Register a temporary user (adjust data as needed)
REGISTER_PAYLOAD=$(cat <<EOF
{
  "name": "Test User",
  "email": "testuser@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "worker",
  "phone": "09123456789",
  "barangay": "Abuyog",
  "municipality": "Sorsogon City"
}
EOF
)
reg_status=$(curl -s -o /dev/null -w "%{http_code}" -X POST -H "Content-Type: application/json" -d "$REGISTER_PAYLOAD" "$BASE_URL/auth/register")
if [ "$reg_status" -eq 201 ]; then
  echo "✅ POST /auth/register passed"
else
  echo "❌ POST /auth/register failed (status $reg_status)"
fi

echo "Done. Review the output above for any failures."
