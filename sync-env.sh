#!/bin/bash
# ╔══════════════════════════════════════════════════════════════════════════╗
# ║  sync-env.sh  —  Secure Environment & Key Import/Export Utility          ║
# ║                                                                          ║
# ║  This script allows developers to securely transfer their local setup    ║
# ║  (including SSH keys and .env files) to another computer.                ║
# ║                                                                          ║
# ║  Commands:                                                               ║
# ║    ./sync-env.sh export [output_file.zip]                                ║
# ║    ./sync-env.sh import [input_file.zip]                                 ║
# ╚══════════════════════════════════════════════════════════════════════════╝

set -e

COMMAND=$1
ARCHIVE_PATH=${2:-"dashboard_secrets.zip"}

show_usage() {
    echo "Usage:"
    echo "  Export local keys and .env:  ./sync-env.sh export [output_file.zip]"
    echo "  Import keys and .env:        ./sync-env.sh import [input_file.zip]"
    exit 1
}

if [ -z "$COMMAND" ] || { [ "$COMMAND" != "export" ] && [ "$COMMAND" != "import" ]; }; then
    show_usage
fi

if [ "$COMMAND" = "export" ]; then
    echo "=== Exporting Environment & Keys ==="
    
    # 1. Create a clean temporary directory
    TMP_DIR="./.tmp_export_secrets"
    rm -rf "$TMP_DIR"
    mkdir -p "$TMP_DIR"

    # 2. Gather default SSH keys
    if [ -f "$HOME/.ssh/id_ed25519" ]; then
        cp "$HOME/.ssh/id_ed25519" "$TMP_DIR/id_ed25519"
        cp "$HOME/.ssh/id_ed25519.pub" "$TMP_DIR/id_ed25519.pub"
        echo "✔ Gathered host SSH Key (id_ed25519)"
    else
        echo "⚠ No host SSH key (id_ed25519) found at ~/.ssh/id_ed25519"
    fi

    # 3. Gather local project keys
    if [ -f "./ansible_rsa" ]; then
        cp "./ansible_rsa" "$TMP_DIR/ansible_rsa"
        cp "./ansible_rsa.pub" "$TMP_DIR/ansible_rsa.pub"
        echo "✔ Gathered local key (ansible_rsa)"
    fi

    # 4. Gather environment config
    if [ -f ".env" ]; then
        cp ".env" "$TMP_DIR/env_backup"
        echo "✔ Gathered .env config"
    else
        echo "⚠ No .env file found in the current directory"
    fi

    # Check if there's anything to archive
    if [ -z "$(ls -A "$TMP_DIR")" ]; then
        echo "❌ Nothing to export! Exiting."
        rm -rf "$TMP_DIR"
        exit 1
    fi

    # 5. Archive and encrypt with zip
    echo "--------------------------------------------------------"
    echo "Please enter a password to encrypt your archive."
    echo "Remember this password! You will need it to import."
    echo "--------------------------------------------------------"
    
    # Run zip with encryption and direct it to the specified output path
    # We cd into the temp directory so the zip has a flat structure
    (cd "$TMP_DIR" && zip -e -r "../$ARCHIVE_PATH" ./*)

    # Clean up
    rm -rf "$TMP_DIR"

    echo "--------------------------------------------------------"
    echo "✔ Export complete! Secure archive saved to: $ARCHIVE_PATH"
    echo "ℹ Transfer this file to your other computer and run:"
    echo "  ./sync-env.sh import $ARCHIVE_PATH"
    echo "--------------------------------------------------------"

elif [ "$COMMAND" = "import" ]; then
    echo "=== Importing Environment & Keys ==="

    if [ ! -f "$ARCHIVE_PATH" ]; then
        echo "❌ Archive file not found: $ARCHIVE_PATH"
        exit 1
    fi

    # 1. Create a clean temporary directory
    TMP_DIR="./.tmp_import_secrets"
    rm -rf "$TMP_DIR"
    mkdir -p "$TMP_DIR"

    # 2. Extract the archive
    echo "--------------------------------------------------------"
    echo "Please enter the decryption password for the archive."
    echo "--------------------------------------------------------"
    unzip "$ARCHIVE_PATH" -d "$TMP_DIR"

    # 3. Import Host keys
    if [ -f "$TMP_DIR/id_ed25519" ]; then
        mkdir -p "$HOME/.ssh"
        chmod 700 "$HOME/.ssh"
        
        IMPORT_SSH=true
        if [ -f "$HOME/.ssh/id_ed25519" ]; then
            echo "⚠ An SSH key already exists at ~/.ssh/id_ed25519"
            read -p "Do you want to overwrite it with the imported key? (y/N): " -r CONFIRM
            if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
                IMPORT_SSH=false
                echo "✔ Kept existing ~/.ssh/id_ed25519 key"
            fi
        fi

        if [ "$IMPORT_SSH" = true ]; then
            cp "$TMP_DIR/id_ed25519" "$HOME/.ssh/id_ed25519"
            cp "$TMP_DIR/id_ed25519.pub" "$HOME/.ssh/id_ed25519.pub"
            chmod 600 "$HOME/.ssh/id_ed25519"
            chmod 644 "$HOME/.ssh/id_ed25519.pub"
            echo "✔ Restored host SSH Key to ~/.ssh/id_ed25519"
        fi
    fi

    # 4. Import local project keys
    if [ -f "$TMP_DIR/ansible_rsa" ]; then
        cp "$TMP_DIR/ansible_rsa" "./ansible_rsa"
        cp "$TMP_DIR/ansible_rsa.pub" "./ansible_rsa.pub"
        chmod 600 "./ansible_rsa"
        echo "✔ Restored local key to ./ansible_rsa"
    fi

    # 5. Import environment config
    if [ -f "$TMP_DIR/env_backup" ]; then
        IMPORT_ENV=true
        if [ -f ".env" ]; then
            echo "⚠ A .env file already exists in this directory"
            read -p "Do you want to overwrite it with the imported config? (y/N): " -r CONFIRM
            if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
                IMPORT_ENV=false
                echo "✔ Kept existing .env config"
            fi
        fi

        if [ "$IMPORT_ENV" = true ]; then
            cp "$TMP_DIR/env_backup" ".env"
            echo "✔ Restored .env config"
        fi
    fi

    # Clean up
    rm -rf "$TMP_DIR"

    echo "--------------------------------------------------------"
    echo "✔ Import complete!"
    echo "ℹ Rebuild the containers using: docker compose down && docker compose up -d"
    echo "--------------------------------------------------------"
fi
