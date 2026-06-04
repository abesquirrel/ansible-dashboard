<?php

return [
    'ssh' => [
        'host'      => env('ANSIBLE_SSH_HOST', '127.0.0.1'),
        'port'      => env('ANSIBLE_SSH_PORT', 22),
        'user'      => env('ANSIBLE_SSH_USER', 'ansible'),
        'key_path'  => env('ANSIBLE_SSH_KEY_PATH', null),
        'password'  => env('ANSIBLE_SSH_PASSWORD', null),
    ],

    'working_dir'          => env('ANSIBLE_WORKING_DIR', '/etc/ansible'),
    'inventory_default'    => env('ANSIBLE_INVENTORY_DEFAULT', '/etc/ansible/inventory'),
    'playbooks_dir'        => env('ANSIBLE_PLAYBOOKS_DIR', '/etc/ansible/playbooks'),
    'vault_password_file'  => env('ANSIBLE_VAULT_PASSWORD_FILE', null),
];
