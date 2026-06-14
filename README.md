# CTRL — Ansible Control Dashboard

A production-grade Laravel 11 web dashboard for managing Ansible infrastructure via SSH. Provides a full GUI for playbook execution, inventory visualization, live terminal access, job history, and audit logging — with MariaDB persistence and WebSocket-powered live output.

> [!TIP]
> For a detailed, step-by-step user manual covering RBAC roles, inventory mapping, playground tools, and interactive SSH terminal use, refer to the [User Guide](file:///Users/paul/Git/ansible-dashboard/USER_GUIDE.md).

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.4 |
| Database | MariaDB 11 |
| Queue / Cache | Redis |
| WebSockets | Laravel Reverb |
| Frontend | Blade + Alpine.js + xterm.js |
| SSH | phpseclib3 |
| Deployment | Docker Compose |

---

## Features

- **Dashboard** — job trend charts (Chart.js), connection status, quick actions
- **Playbook Runner** — GUI form with command preview, extra-vars, tags, limit, check mode, verbose
- **Live Output** — xterm.js-styled streaming output via WebSocket (Laravel Reverb)
- **Inventory Graph** — D3 force-directed topology of groups and hosts
- **Ad-hoc Commands** — run any Ansible module against any host pattern
- **File Editor** — edit inventory/playbook files directly via SFTP
- **Interactive Terminal** — full xterm.js SSH terminal with command history, ANSI colour
- **Job History** — paginated table with status, PLAY RECAP summary, duration
- **Audit Log** — every SSH command logged with user, exit code, duration, IP
- **Scheduled Jobs** — cron-based playbook scheduling via Laravel Scheduler
- **Settings** — SSH connection config, live connection test
- **RBAC** — admin / operator / viewer roles

---

## Quick Start (Docker)

### 1. Clone and configure

```bash
git clone <repo> ansible-ctrl
cd ansible-ctrl
cp .env.example .env
```

### 2. Edit `.env` — minimum required

```env
# App
APP_KEY=          # filled by artisan key:generate below
APP_URL=http://your-server-ip

# Database
DB_PASSWORD=your_secure_db_password

# Cache & Queue Store (Laravel 11 requirement)
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Ansible SSH Control Node
ANSIBLE_SSH_HOST=192.168.1.10        # IP/hostname of your Ansible control node
ANSIBLE_SSH_USER=ansible             # SSH user on control node
ANSIBLE_SSH_KEY_PATH=/home/www-data/.ssh/ansible_rsa   # path inside container

# Redis (WebSockets)
REVERB_APP_KEY=generate-random-32-char-string
REVERB_APP_SECRET=generate-random-32-char-string
```

### 3. Place your SSH private key

```bash
# The key must match ANSIBLE_SSH_KEY_PATH on the control node
cp ~/.ssh/your_ansible_key ./ansible_rsa
# Set in docker-compose.yml volumes: - ./ansible_rsa:/home/www-data/.ssh/ansible_rsa:ro
```

### 4. Generate app key and launch

```bash
# Generate key
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
# Paste output into APP_KEY in .env

# Start all services
docker compose up -d

# Run migrations + seed admin user
docker compose exec app php artisan migrate --seed

# Clear caches
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

### 5. Open the dashboard

```
http://your-server-ip:8000
```

**Default credentials:**
- Email: `admin@localhost`
- Password: `changeme`

**Change the password immediately.**

---

## Manual Installation (without Docker)

### Requirements

- PHP 8.4+ with extensions: pdo_mysql, mbstring, zip, redis, pcntl, bcmath
- Composer 2
- MariaDB 10.6+ or MySQL 8+
- Redis 6+
- Apache/Nginx with mod_rewrite

### Steps

```bash
# 1. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env with your DB, Redis, SSH, and Reverb settings

# 4. Migrate and seed
php artisan migrate --seed

# 5. Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Start queue worker (keep running — use supervisor)
php artisan queue:work redis --sleep=3 --tries=1 --timeout=3600

# 7. Start WebSocket server
php artisan reverb:start --host=0.0.0.0 --port=8080

# 8. Start scheduler (add to crontab)
# * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# 9. Configure web server — point DocumentRoot to /public
```

### Nginx config example

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/ansible-ctrl/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}

# WebSocket proxy (for Reverb - host port 8081 mapped to 8080 inside container)
server {
    listen 8081;
    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}
```

### Supervisor config for queue worker

```ini
[program:ansible-ctrl-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ansible-ctrl/artisan queue:work redis --sleep=3 --tries=1 --timeout=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/ansible-ctrl-worker.log
```

---

## SSH Key Setup on Control Node

```bash
# On the Ansible control node, create a dedicated user
sudo useradd -m -s /bin/bash ansible

# Add your dashboard's public key to authorized_keys
sudo mkdir -p /home/ansible/.ssh
sudo tee -a /home/ansible/.ssh/authorized_keys <<< "$(cat /path/to/ansible_rsa.pub)"
sudo chown -R ansible:ansible /home/ansible/.ssh
sudo chmod 700 /home/ansible/.ssh
sudo chmod 600 /home/ansible/.ssh/authorized_keys

# Grant ansible user sudo for ansible commands (optional but typical)
echo "ansible ALL=(ALL) NOPASSWD: /usr/bin/ansible*, /usr/bin/ansible-playbook" | sudo tee /etc/sudoers.d/ansible-ctrl
```

---

## Project Structure

```
ansible-dashboard/
├── app/
│   ├── Events/
│   │   ├── PlaybookOutputChunk.php   # WebSocket: streaming job output
│   │   ├── PlaybookFinished.php      # WebSocket: job completion
│   │   └── TerminalOutput.php        # WebSocket: terminal session
│   ├── Http/Controllers/
│   │   ├── Auth/LoginController.php
│   │   ├── DashboardController.php
│   │   ├── PlaybookController.php    # run, abort, output polling
│   │   ├── InventoryController.php   # graph, ping, facts, ad-hoc, SFTP editor
│   │   ├── TerminalController.php    # exec, streaming
│   │   ├── LogController.php         # audit + job history
│   │   └── SettingsController.php
│   ├── Jobs/
│   │   ├── RunPlaybookJob.php        # async playbook runner + broadcaster
│   │   └── StreamingTerminalJob.php  # streaming terminal via WebSocket
│   ├── Middleware/
│   │   ├── EnsureUserIsActive.php
│   │   └── EnsureUserIsAdmin.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── PlaybookJob.php
│   │   ├── JobOutputLine.php
│   │   ├── AuditLog.php
│   │   ├── InventoryHost.php
│   │   └── ScheduledJob.php
│   ├── Providers/AppServiceProvider.php
│   └── Services/
│       ├── AnsibleSSHService.php     # phpseclib3 SSH/SFTP wrapper
│       └── AnsibleService.php        # Ansible command builder + parser
├── bootstrap/app.php                 # Laravel 11 bootstrap with middleware aliases
├── config/ansible.php                # SSH + path configuration
├── database/
│   ├── migrations/                   # 3 migration files
│   └── seeders/DatabaseSeeder.php    # admin user seeder
├── docker/
│   ├── Dockerfile
│   └── apache.conf
├── docker-compose.yml                # app + worker + scheduler + reverb + mariadb + redis
├── resources/views/
│   ├── auth/login.blade.php          # standalone login page
│   ├── layouts/app.blade.php         # full shell: topbar + sidebar + main
│   ├── dashboard/index.blade.php     # stats + trend chart + recent jobs
│   ├── playbooks/
│   │   ├── index.blade.php           # runner form + jobs list
│   │   └── show.blade.php            # live output viewer + job meta
│   ├── inventory/index.blade.php     # D3 topology + hosts + ad-hoc + SFTP editor
│   ├── terminal/index.blade.php      # full xterm.js terminal
│   ├── logs/
│   │   ├── index.blade.php           # audit log
│   │   └── jobs.blade.php            # job history
│   └── settings/index.blade.php
└── routes/
    ├── web.php
    ├── channels.php                  # Reverb channel auth
    └── console.php                   # scheduler tasks
```

---

## Exporting & Importing Configuration (Multi-Device)

For developers running this dashboard on multiple laptops, a utility script `sync-env.sh` is provided to securely package and restore environment configurations (`.env`) and SSH private keys.

### 1. Export Settings (Source Laptop)
Run the script to create a password-encrypted ZIP archive containing your local `.env` and host/local SSH keys:
```bash
./sync-env.sh export [backup_name.zip]
```
*You will be prompted to enter a password to secure the archive.*

### 2. Import Settings (Target Laptop)
Move the ZIP archive to the dashboard root on your new laptop and run:
```bash
./sync-env.sh import [backup_name.zip]
```
*You will be prompted for the password, and then asked for confirmation before writing the host SSH key (`~/.ssh/id_ed25519`) or overwriting an existing `.env` file.*

Finally, apply the configuration inside Docker:
```bash
docker compose down && docker compose up -d
```

---

## Environment Reference

| Variable | Description |
|---|---|
| `ANSIBLE_SSH_HOST` | IP/hostname of Ansible control node |
| `ANSIBLE_SSH_PORT` | SSH port (default: 22) |
| `ANSIBLE_SSH_USER` | SSH username |
| `ANSIBLE_SSH_KEY_PATH` | Path to private key file |
| `ANSIBLE_SSH_PASSWORD` | Password auth fallback (key preferred) |
| `ANSIBLE_WORKING_DIR` | Ansible working directory on control node |
| `ANSIBLE_INVENTORY_DEFAULT` | Default inventory file path |
| `ANSIBLE_PLAYBOOKS_DIR` | Directory to scan for `.yml` playbooks |
| `ANSIBLE_VAULT_PASSWORD_FILE` | Vault password file path (optional) |
| `REVERB_HOST` | Reverb WebSocket host |
| `REVERB_PORT` | Reverb WebSocket port (default: 8080) |
| `REVERB_APP_KEY` | Reverb app key |
| `REVERB_APP_SECRET` | Reverb app secret |

---

## Security Notes

- SSH key auth is strongly preferred over password auth
- All commands are logged to `audit_logs` with user, IP, exit code, and duration
- Admin role required to access Settings; all routes require authentication
- The terminal blocks `rm -rf /`, `mkfs`, `dd if=/dev/zero` for non-admin users
- Change `admin@localhost` password immediately after first login
- Set `APP_DEBUG=false` in production
- Use HTTPS in production (add TLS to nginx/Apache config)

---

## License

MIT
