<?php

$date = new DateTimeImmutable('2024-01-01');
echo strlen($date->format('Y-m-d')) . PHP_EOL;
