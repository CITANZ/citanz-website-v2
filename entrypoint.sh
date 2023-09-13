#!/bin/sh

# Start Apache
apache2-foreground &

# Check if the database is initialized
if [ ! -f /var/www/html/.database_initialized ]; then

  # Wait for MySQL to start completely
  sleep 20

  # Execute the command to build the database
  vendor/bin/sake dev/build flush=all

  # Mark the database as initialized
  touch /var/www/html/.database_initialized
fi

# Wait for Apache to run
wait

