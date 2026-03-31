#!/bin/bash

# Script to revert today's manual completions via API
# Usage: ./revert_manual_completions.sh or bash revert_manual_completions.sh

echo "Reverting today's manual completions via API..."

# Get CSRF token first (you'll need to be logged in to the admin panel)
CSRF_TOKEN=$(curl -c cookies.txt -s http://localhost/admin/data-sub | grep -o 'content="[^"]*" name="csrf-token' | sed 's/content="//' | sed 's/" name="csrf-token//')

if [ -z "$CSRF_TOKEN" ]; then
    echo "Error: Could not get CSRF token. Make sure you're logged in to the admin panel."
    echo "Try visiting http://localhost/admin/data-sub in your browser first."
    exit 1
fi

echo "CSRF token: $CSRF_TOKEN"

# Make the API call to revert transactions
RESPONSE=$(curl -b cookies.txt -s -X POST \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -d '{}' \
  http://localhost/admin/revert-today-manual-completions)

echo "Response: $RESPONSE"

# Clean up cookies
rm -f cookies.txt

echo "Done."
