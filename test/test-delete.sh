#!/bin/bash

# Configuration
API_URL="https://trivia.sjhannah.com/delete"  # Replace with the actual URL of your delete endpoint
BEARER_TOKEN="rTWi1hi0BQRD8db9bcKvcQ37J4Ph9hkIEFG1EUUU0cHjfGSGxbAMlVWCOsd2v1pA"
CATEGORY="Science"  # Replace with the category you want to delete

# Send the DELETE request
curl -X DELETE "$API_URL/$CATEGORY" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -H "Accept: application/json" \
     -w "\nHTTP Status: %{http_code}\n" \
     --verbose

