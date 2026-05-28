<?php // lint >= 8.4

declare(strict_types=1);

namespace Bug14716;

$value = round(2.5, 0, \RoundingMode::HalfAwayFromZero);
$value = round(2.5, 0, \RoundingMode::HalfTowardsZero);
$value = round(2.5, 0, \RoundingMode::HalfEven);
$value = round(2.5, 0, \RoundingMode::HalfOdd);
$value = round(2.5, 0, \RoundingMode::TowardsZero);
$value = round(2.5, 0, \RoundingMode::AwayFromZero);
$value = round(2.5, 0, \RoundingMode::NegativeInfinity);
$value = round(2.5, 0, \RoundingMode::PositiveInfinity);

var_dump($value);
