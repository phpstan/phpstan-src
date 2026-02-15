<?php declare(strict_types = 1);

namespace Bug10704;

$hosts   = [];
$weights = [];
$success = getmxrr('', $hosts, $weights);
echo count($hosts);
