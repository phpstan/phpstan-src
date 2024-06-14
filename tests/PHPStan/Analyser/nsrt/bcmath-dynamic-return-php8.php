<?php

// Verification for constant types: https://3v4l.org/96GSj

/** @var mixed $mixed */
$mixed = getMixed();

/** @var int $iUnknown */
$iUnknown = getInt();

/** @var string $string */
$string = getString();

$iNeg = -5;
$iPos = 5;
$nonNumeric = 'foo';


//  bcdiv ( string $dividend , string $divisor [, ?int $scale = null ] ) : string
// Returns the result of the division as a numeric-string.
\PHPStan\Testing\assertType('*NEVER*', bcdiv('10', '0')); // DivisionByZeroError
\PHPStan\Testing\assertType('*NEVER*', bcdiv('10', '0.0')); // DivisionByZeroError
\PHPStan\Testing\assertType('*NEVER*', bcdiv('10', 0.0)); // DivisionByZeroError
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '1'));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '-1'));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '2', 0));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '2', 1));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iNeg));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iPos));
\PHPStan\Testing\assertType('numeric-string', bcdiv($iPos, $iPos));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $mixed));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iPos, $iPos));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iUnknown));
\PHPStan\Testing\assertType('*NEVER*', bcdiv('10', $iPos, $nonNumeric)); // ValueError argument 3
\PHPStan\Testing\assertType('*NEVER*', bcdiv('10', $nonNumeric)); // ValueError argument 2

//  bcmod ( string $dividend , string $divisor [, ?int $scale = null ] ) : string
// Returns the modulus as a numeric-string.
\PHPStan\Testing\assertType('*NEVER*', bcmod('10', '0')); // DivisionByZeroError
\PHPStan\Testing\assertType('*NEVER*', bcmod($iPos, '0')); // DivisionByZeroError
\PHPStan\Testing\assertType('*NEVER*', bcmod('10', $nonNumeric)); // ValueError argument 2
\PHPStan\Testing\assertType('numeric-string', bcmod('10', '1'));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', '2', 0));
\PHPStan\Testing\assertType('numeric-string', bcmod('5.7', '1.3', 1));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', 2.2));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', $iUnknown));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', '-1'));
\PHPStan\Testing\assertType('numeric-string', bcmod($iPos, '-1'));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', $iNeg));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', $iPos));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', -$iNeg));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', -$iPos));
\PHPStan\Testing\assertType('numeric-string', bcmod('10', $mixed));

//  bcpowmod ( string $base , string $exponent , string $modulus [, ?int $scale = null ] ) : string
// Returns the result as a numeric-string, or FALSE if modulus is 0 or exponent is negative.
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '-2', '0')); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '-2', '1')); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '2', $nonNumeric)); // ValueError argument 3
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '-2', '-1')); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '-2', -1.3)); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', -$iPos, '-1')); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', -$iPos, '1')); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', $nonNumeric, $nonNumeric)); // ValueError argument 2
\PHPStan\Testing\assertType('*NEVER*', bcpowmod($iPos, $nonNumeric, $nonNumeric));
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '2', '0')); // modulus is 0
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', 2.3, '0')); // modulus is 0
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', '0', '0')); // modulus is 0
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '0', '-2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '2', '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', $iUnknown, '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod($iPos, '2', '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', $mixed, $mixed));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '2', '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', -$iNeg, '2'));
\PHPStan\Testing\assertType('*NEVER*', bcpowmod('10', $nonNumeric, '2')); // ValueError argument 2
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', $iUnknown, $iUnknown));

//  bcsqrt ( string $operand [, ?int $scale = null ] ) : string
// Returns the square root as a numeric-string.
\PHPStan\Testing\assertType('*NEVER*', bcsqrt('10', $iNeg)); // ValueError argument 2
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('0.00', 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt(0.0, 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('0', 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt($iUnknown, $iUnknown));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', $iPos));
\PHPStan\Testing\assertType('*NEVER*', bcsqrt('-10', 0)); // ValueError argument 1
\PHPStan\Testing\assertType('*NEVER*', bcsqrt($iNeg, null)); // ValueError argument 1
\PHPStan\Testing\assertType('*NEVER*', bcsqrt('10', $nonNumeric)); // ValueError argument 2
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10'));
\PHPStan\Testing\assertType('numeric-string', bcsqrt($iUnknown));
\PHPStan\Testing\assertType('*NEVER*', bcsqrt('-10')); // ValueError argument 1

\PHPStan\Testing\assertType('*NEVER*', bcsqrt($nonNumeric, -1)); // ValueError argument 1
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', $mixed));
\PHPStan\Testing\assertType('numeric-string', bcsqrt($iPos));
