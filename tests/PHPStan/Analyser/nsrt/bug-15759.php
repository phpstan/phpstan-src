<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15759;

use function PHPStan\Testing\assertType;

/**
 * @return (
 *     ( $p is '' ? decimal-int-string : string )
 *     & ( $p is lowercase-string ? lowercase-string : string )
 * )
 */
function with_decimal_int_string( string $p ): string {
	return $p . '1';
}

/**
 * @return (
 *     ( $p is '' ? numeric-string : string )
 *     & ( $p is lowercase-string ? lowercase-string : string )
 * )
 */
function with_numeric_string( string $p ): string {
	return $p . '1';
}

/**
 * @return (
 *     ( $p is '' ? non-decimal-int-string : string )
 *     & ( $p is lowercase-string ? lowercase-string : string )
 * )
 */
function with_non_decimal_int_string( string $p ): string {
	return $p . '1';
}

function (): void {
	assertType( 'lowercase-string', with_decimal_int_string( 'foo-' ) );
	assertType( 'lowercase-string', with_numeric_string( 'foo-' ) );
	assertType( 'lowercase-string', with_non_decimal_int_string( 'foo-' ) );
	assertType( 'decimal-int-string', with_decimal_int_string( '' ) );
	assertType( 'string', with_decimal_int_string( 'FOO' ) );
};

/**
 * @return ( $p is '' ? decimal-int-string : string )|( $p is lowercase-string ? lowercase-string : string )
 */
function union_with_decimal_int_string( string $p ): string {
	return $p . '1';
}

function (): void {
	assertType( 'lowercase-string', union_with_decimal_int_string( '' ) );
	assertType( 'string', union_with_decimal_int_string( 'foo-' ) );
};

/**
 * @return ( $p is int ? int : string )|( $p is not int ? int : string )
 */
function union_with_negated( int|string $p ): int|string {
	return $p;
}

function (): void {
	assertType( 'int|string', union_with_negated( 1 ) );
	assertType( 'int|string', union_with_negated( 'a' ) );
};

/**
 * @template T of string
 * @param T $p
 * @return ( T is '' ? decimal-int-string : string )
 *     & ( T is lowercase-string ? lowercase-string : string )
 */
function template_with_decimal_int_string( string $p ): string {
	return $p . '1';
}

function (): void {
	assertType( 'lowercase-string', template_with_decimal_int_string( 'foo-' ) );
	assertType( 'decimal-int-string', template_with_decimal_int_string( '' ) );
};
