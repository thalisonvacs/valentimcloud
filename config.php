<?php
define('SECRET_KEY', 'troque_essa_chave_para_uma_muito_secreta_!@#1234567890abcdef');

$USERS = [
  'thalison.valentim' => [
      'pass' => '170216Ae@',
      'name' => 'Thálison Valentim',
      'role' => 'user',
      'cpf'  => '04883833232'    // <- coloque o CPF do usuário aqui (apenas números)
  ],
  'admin' => [
      'pass' => 'admin123',
      'name' => 'Admin',
      'role' => 'admin',
      'cpf'  => '00000000000'
  ]
];

define('BASE_UPLOAD_DIR', __DIR__ . '/uploads');
define('PASSWORDS_FILE', __DIR__ . '/data/passwords.json');
