#!/usr/bin/env php
<?php declare(strict_types=1);

$composerPath = __DIR__ . '/../composer.json';
$json = json_decode(file_get_contents($composerPath), true);
$json['require']['php'] = '^7.1';

file_put_contents($composerPath, json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
