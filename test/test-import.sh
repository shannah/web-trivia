#!/bin/bash

# Configuration
API_URL="https://trivia.sjhannah.com/import"  # Replace with the actual URL of your PHP script
BEARER_TOKEN="rTWi1hi0BQRD8db9bcKvcQ37J4Ph9hkIEFG1EUUU0cHjfGSGxbAMlVWCOsd2v1pA"
CSV_FILE="sample_trivia.csv"

# Create a sample CSV file
echo "Category,Difficulty,Question,Answer" > $CSV_FILE
echo "Science,Easy,What planet is known as the Red Planet?,Mars" >> $CSV_FILE
echo "History,Medium,Who was the first president of the United States?,George Washington" >> $CSV_FILE

# Send the raw CSV content with the appropriate headers
curl -X POST "$API_URL" \
     -H "Authorization: Bearer $BEARER_TOKEN" \
     -H "Content-Type: text/csv" \
     --data-binary "@$CSV_FILE" \
     -w "\nHTTP Status: %{http_code}\n" \
     --verbose

# Cleanup: Uncomment the next line if you want to remove the CSV file after the test
# rm -f $CSV_FILE
