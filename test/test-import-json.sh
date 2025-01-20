#!/bin/bash

# Configuration
API_URL="https://trivia.sjhannah.com/import"  # Replace with the actual URL of your PHP script
BEARER_TOKEN="rTWi1hi0BQRD8db9bcKvcQ37J4Ph9hkIEFG1EUUU0cHjfGSGxbAMlVWCOsd2v1pA"
JSON_FILE="sample_trivia.json"

# Create a sample JSON file
cat <<EOL > $JSON_FILE
{ 
    "questions": [
        {
            "category": "Science",
            "difficulty": "easy",
            "question": "What planet is known as the Red Planet?",
            "answer": "Mars"
        },
        {
            "category": "History",
            "difficulty": "medium",
            "question": "Who was the first president of the United States?",
            "answer": "George Washington"
        }
    ]
}
EOL

# Send the JSON content with the appropriate headers
curl -X POST "$API_URL" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -H "Content-Type: application/json" \
     --data "@$JSON_FILE" \
     -w "\nHTTP Status: %{http_code}\n" \
     --verbose

# Cleanup: Uncomment the next line if you want to remove the JSON file after the test
# rm -f $JSON_FILE
