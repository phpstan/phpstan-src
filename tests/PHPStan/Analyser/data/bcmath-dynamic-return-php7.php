<?php // onlyif PHP_VERSION_ID < 80000

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


//  bcdiv ( string $dividend , string $divisor [, int $scale = 0 ] ) : string
// Returns the result of the division as a numeric-string, or NULL if divisor is 0.
\PHPStan\Testing\assertType('null', bcdiv('10', '0')); // Warning: Division by zero
\PHPStan\Testing\assertType('null', bcdiv('10', '0.0')); // Warning: Division by zero
\PHPStan\Testing\assertType('null', bcdiv('10', 0.0)); // Warning: Division by zero
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '1'));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '-1'));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '2', 0));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', '2', 1));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iNeg));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iPos));
\PHPStan\Testing\assertType('numeric-string', bcdiv($iPos, $iPos));
\PHPStan\Testing\assertType('numeric-string|null', bcdiv('10', $mixed));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iPos, $iPos));
\PHPStan\Testing\assertType('numeric-string', bcdiv('10', $iUnknown));
\PHPStan\Testing\assertType('null', bcdiv('10', $iPos, $nonNumeric)); // Warning: expects parameter 3 to be int, string given in
\PHPStan\Testing\assertType('null', bcdiv('10', $nonNumeric)); // Warning: bcmath function argument is not well-formed

//  bcmod ( string $dividend , string $divisor [, int $scale = 0 ] ) : string
// Returns the modulus as a numeric-string, or NULL if divisor is 0.
\PHPStan\Testing\assertType('null', bcmod('10', '0'));
\PHPStan\Testing\assertType('null', bcmod($iPos, '0')); // Warning: Division by zero
\PHPStan\Testing\assertType('null', bcmod('10', $nonNumeric));
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
\PHPStan\Testing\assertType('numeric-string|null', bcmod('10', $mixed));

//  bcpowmod ( string $base , string $exponent , string $modulus [, int $scale = 0 ] ) : string
// Returns the result as a numeric-string, or FALSE if modulus is 0 or exponent is negative.
\PHPStan\Testing\assertType('false', bcpowmod('10', '-2', '0')); // exponent negative, and modulus is 0
\PHPStan\Testing\assertType('false', bcpowmod('10', '-2', '1')); // exponent negative
\PHPStan\Testing\assertType('false', bcpowmod('10', '2', $nonNumeric)); // Warning: bcmath function argument is not well-formed
\PHPStan\Testing\assertType('false', bcpowmod('10', '-2', '-1')); // exponent negative
\PHPStan\Testing\assertType('false', bcpowmod('10', '-2', -1.3)); // exponent negative
\PHPStan\Testing\assertType('false', bcpowmod('10', -$iPos, '-1')); // exponent negative
\PHPStan\Testing\assertType('false', bcpowmod('10', -$iPos, '1')); // exponent negative
\PHPStan\Testing\assertType('false', bcpowmod('10', $nonNumeric, $nonNumeric)); // Warning: bcmath function argument is not well-formed
\PHPStan\Testing\assertType('false', bcpowmod($iPos, $nonNumeric, $nonNumeric));
\PHPStan\Testing\assertType('false', bcpowmod('10', '2', '0')); // modulus is 0
\PHPStan\Testing\assertType('false', bcpowmod('10', 2.3, '0')); // modulus is 0
\PHPStan\Testing\assertType('false', bcpowmod('10', '0', '0')); // modulus is 0
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '0', '-2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '2', '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', $iUnknown, '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod($iPos, '2', '2'));
\PHPStan\Testing\assertType('numeric-string|false', bcpowmod('10', $mixed, $mixed));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', '2', '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', -$iNeg, '2'));
\PHPStan\Testing\assertType('numeric-string', bcpowmod('10', $nonNumeric, '2')); // Warning: bcmath function argument is not well-formed
\PHPStan\Testing\assertType('numeric-string|false', bcpowmod('10', $iUnknown, $iUnknown));

//  bcsqrt ( string $operand [, int $scale = 0 ] ) : string
// Returns the square root as a numeric-string, or NULL if operand is negative.
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', $iNeg));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('0.00', 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt(0.0, 1));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('0', 1));
\PHPStan\Testing\assertType('numeric-string|null', bcsqrt($iUnknown, $iUnknown));
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10', $iPos));
\PHPStan\Testing\assertType('null', bcsqrt('-10', 0)); // Warning: Square root of negative number
\PHPStan\Testing\assertType('null', bcsqrt($iNeg, 0));
\PHPStan\Testing\assertType('null', bcsqrt('10', $nonNumeric)); // Warning: Second argument must be ?int (Fatal in PHP8)
\PHPStan\Testing\assertType('numeric-string', bcsqrt('10'));
\PHPStan\Testing\assertType('numeric-string|null', bcsqrt($iUnknown));
\PHPStan\Testing\assertType('null', bcsqrt('-10')); // Warning: Square root of negative number

\PHPStan\Testing\assertType('numeric-string|null', bcsqrt($nonNumeric, -1)); // Warning: bcmath function argument is not well-formed
\PHPStan\Testing\assertType('numeric-string|null', bcsqrt('10', $mixed));
\PHPStan\Testing\assertType('numeric-string', bcsqrt($iPos));
