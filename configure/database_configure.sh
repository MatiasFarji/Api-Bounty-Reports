#!/bin/bash

echo "======================================"
echo "   PostgreSQL Installer (Debian)      "
echo "======================================"

# Ask for user inputs
read -p "Enter the database username to create: " DB_USER
read -s -p "Enter the password for user $DB_USER: " DB_PASS
echo ""
read -p "Enter the database name to create: " DB_NAME

# Path to schema file
SCHEMA_FILE="setup_schema.sql"

echo "[*] Updating system packages..."
sudo apt-get update -y
sudo apt-get upgrade -y

echo "[*] Installing PostgreSQL..."
sudo apt-get install -y postgresql postgresql-contrib

echo "[*] Enabling and starting PostgreSQL service..."
sudo systemctl enable postgresql
sudo systemctl start postgresql

echo "[*] Creating database user and database..."
sudo -u postgres psql <<EOF
DO
\$do\$
BEGIN
   IF NOT EXISTS (
      SELECT FROM pg_catalog.pg_roles WHERE rolname = '${DB_USER}'
   ) THEN
      CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';
   END IF;
END
\$do\$;

CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};
GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};
EOF

echo "[*] Applying schema from ${SCHEMA_FILE}..."
if [ -f "$SCHEMA_FILE" ]; then
    sudo -u postgres psql -d ${DB_NAME} -f "$SCHEMA_FILE"
    echo "[✔] Schema applied successfully."
else
    echo "[!] Schema file ${SCHEMA_FILE} not found. Skipping..."
fi

echo "======================================"
echo "[✔] Installation and setup completed."
echo " Database user: $DB_USER"
echo " Database name: $DB_NAME"
echo " Schema file:  $SCHEMA_FILE"
echo "======================================"
