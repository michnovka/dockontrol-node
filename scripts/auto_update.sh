#!/bin/bash

# Resolve the absolute path to the project directory
PROJECT_DIR=$(realpath "$(dirname "$0")/..")

# Define the path to the update needed file
UPDATE_FILE="${PROJECT_DIR}/www/var/update/update_needed"

# Navigate to the project directory
cd "$PROJECT_DIR" || exit

# Check if the update_needed file exists
if [ -f "$UPDATE_FILE" ]; then
    echo "$(date): Update detected, checking for updates..."

    # Fetch the latest changes from the remote repository
    git fetch

    # Get the current branch name
    BRANCH_NAME=$(git rev-parse --abbrev-ref HEAD)

    # Compare the local HEAD with the remote HEAD
    LOCAL_HASH=$(git rev-parse HEAD)
    REMOTE_HASH=$(git rev-parse "origin/$BRANCH_NAME")

    if [ "$LOCAL_HASH" = "$REMOTE_HASH" ]; then
        echo "$(date): No changes detected, local repo is up to date."
    else
        echo "$(date): Changes detected. Resetting local repository and updating containers..."

        # Forcefully reset the local branch
        git reset --hard "origin/$BRANCH_NAME"

        # Proceed with Docker updates
        docker compose down          # Stop and remove current containers
        docker compose build         # Build the updated containers
        docker compose up -d         # Start the new containers in detached mode
    fi

    # Remove the signal file after processing
    rm -f "$UPDATE_FILE"
fi