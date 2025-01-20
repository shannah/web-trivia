#!/bin/bash

# Load environment variables from dreamhost.env
SCRIPT_DIR="$(dirname "$0")"
ENV_FILE="$SCRIPT_DIR/dreamhost.env"

if [[ -f "$ENV_FILE" ]]; then
  # Load the environment variables
  set -a
  source "$ENV_FILE"
  set +a
else
  echo "Error: Environment file 'dreamhost.env' not found in the script directory."
  exit 1
fi

# Ensure SERVER_HOST and SERVER_USER are set
if [[ -z "$SERVER_HOST" || -z "$SERVER_USER" ]]; then
  echo "Error: SERVER_HOST and SERVER_USER must be set in 'dreamhost.env'."
  exit 1
fi

# Connect to the server via SSH
cd "$SCRIPT_DIR/.."
scp -r htdocs/.htaccess "$SERVER_USER@$SERVER_HOST":trivia.sjhannah.com/
scp -r htdocs/* "$SERVER_USER@$SERVER_HOST":trivia.sjhannah.com/
