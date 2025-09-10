<?php declare(strict_types = 1);

namespace Bug1452;

$dateInterval = (new \DateTimeImmutable('now -60 minutes'))->diff(new \DateTimeImmutable('now'));

echo $dateInterval->format('%a') * 60;
