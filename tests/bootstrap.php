<?php

declare(strict_types=1);

// テスト環境の設定
$_SERVER['APP_ENV'] = 'testing';
$_ENV['APP_ENV']    = 'testing';
putenv('APP_ENV=testing');

require __DIR__ . '/../vendor/autoload.php';
