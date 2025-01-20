#!/bin/bash

# Configuration
API_URL="https://trivia.sjhannah.com/export"  # Replace with the actual URL of your export endpoint
OUTPUT_FILE="exported_trivia.csv"

# Send the GET request to export trivia questions
curl -X GET "$API_URL" \
     -H "Accept: text/csv" \
     -o "$OUTPUT_FILE" \
     -w "\nHTTP Status: %{http_code}\n" \
     --verbose

# Check if the file was downloaded successfully
if [ -s "$OUTPUT_FILE" ]; then
    echo "Export successful. Trivia questions saved to $OUTPUT_FILE"
else
    echo "Export failed. No data was saved."
fi
