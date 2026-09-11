<?php // lint >= 8.1
declare(strict_types = 1);

namespace Bug15055;

use function PHPStan\Testing\assertType;

enum StepType: string
{

	case Action = 'action';
	case Event = 'event';

}

function literalPrefix(int $id): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', 'step_' . $id);
}

function enumPrefix(StepType $type, int $id): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', $type->value . '_' . $id);
}

function literalSuffix(int $id): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', $id . '_step');
}

function interpolated(int $id): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', "step_$id");
}

function assignOp(int $id): void
{
	$key = 'step';
	$key .= $id;
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', $key);
}

/** @param non-decimal-int-string&non-empty-string $prefix */
function mustStayString(string $prefix, int $id): void
{
	// unsound to infer non-decimal-int-string here: '-' is a non-decimal-int-string
	// while '-' . 1 is the decimal-int-string '-1'
	assertType('non-falsy-string', $prefix . $id);
}

function leadingMinusStaysString(int $id): void
{
	assertType('lowercase-string&non-falsy-string&uppercase-string', '-' . $id);
	assertType('lowercase-string&non-falsy-string&uppercase-string', '1' . $id);
}

function leadingZeroIsNeverCanonical(int $id): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', '0' . $id);
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', '00' . $id);
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', '-0' . $id);
}

function minusInTheMiddle(int $id, string $s): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', $id . '-1');
	// $s can be '', so the result can be the decimal-int-string '-1'
	assertType('non-falsy-string', $s . '-1');
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $id . '007');
	// $s can be '', so the result can be the decimal-int-string '0'
	assertType('non-empty-string', '0' . $s);
}

/** @param ''|'foo' $x */
function possiblyEmptyConstantUnion(string $x, int $id): void
{
	assertType('lowercase-string&non-empty-string', $x . $id);
}

/**
 * @param non-empty-list<int> $ints
 * @param array{int, int} $pair
 * @param non-empty-list<'foo'|'bar'> $words
 */
function implodeSeparator(array $ints, array $pair, array $words): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', implode('_', $pair));
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string&uppercase-string', implode('-', $pair));
	// a single element makes the separator disappear
	assertType('lowercase-string&non-falsy-string&uppercase-string', implode('_', $ints));
	assertType('lowercase-string&non-empty-string&uppercase-string', implode('', $ints));
	assertType('literal-string&lowercase-string&non-decimal-int-string&non-falsy-string', implode('', $words));
}

function sprintfLiteralParts(int $id, string $s): void
{
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', sprintf('step_%d', $id));
	assertType('lowercase-string&non-decimal-int-string&non-falsy-string', sprintf('%d_step', $id));
	assertType('non-decimal-int-string&non-falsy-string', sprintf('%s.%s', $s, $s));
	// '-' . '1' is the decimal-int-string '-1'
	assertType('lowercase-string&numeric-string', sprintf('%d', $id));
	assertType('lowercase-string&non-falsy-string', sprintf('-%d', $id));
	assertType('non-falsy-string', sprintf('%s-%s', $s, $s));
	// the padding of '%05d' is not part of the literal text
	assertType('lowercase-string&numeric-string', sprintf('%05d', $id));
}

function numberFormatDecimalSeparator(float $x, int $decimals): void
{
	assertType('non-decimal-int-string', number_format($x, 2));
	assertType('non-decimal-int-string&numeric-string', number_format($x, 2, '.', ''));
	assertType('non-decimal-int-string&numeric-string', number_format($x, 2, null, ''));
	// without a decimal separator the digits end up next to each other
	assertType('numeric-string', number_format($x, 2, '', ''));
	assertType('string', number_format($x));
	assertType('string', number_format($x, $decimals, '.'));
}

/**
 * @param non-decimal-int-string $nds
 * @param decimal-int-string $ds
 */
function caseFunctionsKeepDigits(string $nds, string $ds): void
{
	assertType('lowercase-string&non-decimal-int-string', strtolower($nds));
	assertType('non-decimal-int-string&uppercase-string', strtoupper($nds));
	assertType('non-decimal-int-string', ucfirst($nds));
	assertType('lowercase-string&non-decimal-int-string', mb_strtolower($nds));
	assertType('non-decimal-int-string', ucwords($nds));
	assertType('decimal-int-string', strtolower($ds));
	// mb_convert_kana can convert digits to their full-width form
	assertType('string', mb_convert_kana($nds));
}

/** @param non-decimal-int-string $nds */
function arithmeticOnNonDecimalIntString(string $nds): void
{
	// a non-decimal-int-string can be an arbitrary non-numeric string
	assertType('*ERROR*', 1 + $nds);
	assertType('*ERROR*', -$nds);
	assertType('*ERROR*', $nds & 3);
}
