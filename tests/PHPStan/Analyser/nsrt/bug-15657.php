<?php declare(strict_types = 1);

namespace Bug15657;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, string> $meta
 */
function readProps(array $meta): string
{
	// 17 entries - with 16 entries, all keys are known to exist after the loop
	$metaKeyToProp = [
		'_description' => 'description',
		'_regular_price' => 'regular_price',
		'_sale_price' => 'sale_price',
		'_date_on_sale_from' => 'date_on_sale_from',
		'_date_on_sale_to' => 'date_on_sale_to',
		'_manage_stock' => 'manage_stock',
		'_stock_status' => 'stock_status',
		'_virtual' => 'virtual',
		'_downloadable' => 'downloadable',
		'_sku' => 'sku',
		'_stock_quantity' => 'stock_quantity',
		'_weight' => 'weight',
		'_length' => 'length',
		'_width' => 'width',
		'_height' => 'height',
		'_backorders' => 'backorders',
		'_tax_class' => 'tax_class',
	];

	$props = [];
	foreach ($metaKeyToProp as $metaKey => $prop) {
		$props[$prop] = $meta[$metaKey] ?? '';
	}

	assertType('array{description: string, regular_price: string, sale_price: string, date_on_sale_from: string, date_on_sale_to: string, manage_stock: string, stock_status: string, virtual: string, downloadable: string, sku: string, stock_quantity: string, weight: string, length: string, width: string, height: string, backorders: string, tax_class: string}', $props);

	return $props['tax_class'];
}

const MAP = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8, 'i' => 9, 'j' => 10, 'k' => 11, 'l' => 12, 'm' => 13, 'n' => 14, 'o' => 15, 'p' => 16, 'q' => 17];

function keyVar(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result[$key] = $value * 2;
	}
	assertType('array{a: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, b: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, c: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, d: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, e: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, f: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, g: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, h: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, i: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, j: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, k: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, l: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, m: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, n: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, o: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, p: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34, q: 2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34}', $result);
	assertType('2|4|6|8|10|12|14|16|18|20|22|24|26|28|30|32|34', $result['q']);
}

function valueVarIntKeys(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result[$value] = $key;
	}
	assertType("array{1: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 2: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 3: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 4: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 5: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 6: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 7: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 8: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 9: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 10: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 11: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 12: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 13: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 14: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 15: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 16: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q', 17: 'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k'|'l'|'m'|'n'|'o'|'p'|'q'}", $result);
}

function withoutKeyVar(): void
{
	$result = ['x' => 0];
	foreach (MAP as $value) {
		if ($value > 5) {
			$result['big'] = true;
		}
		$result[$value] = true;
	}
	assertType('true', array_key_exists(17, $result));
	assertType('0', $result['x']);
	assertType('true', $result['big'] ?? true);
}

function continueBeforeWrite(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		if ($value > 5) {
			continue;
		}
		$result[$key] = $value;
	}
	assertType('array{a?: 1|2|3|4|5, b?: 1|2|3|4|5, c?: 1|2|3|4|5, d?: 1|2|3|4|5, e?: 1|2|3|4|5, f?: 1|2|3|4|5, g?: 1|2|3|4|5, h?: 1|2|3|4|5, i?: 1|2|3|4|5, j?: 1|2|3|4|5, k?: 1|2|3|4|5, l?: 1|2|3|4|5, m?: 1|2|3|4|5, n?: 1|2|3|4|5, o?: 1|2|3|4|5, p?: 1|2|3|4|5, q?: 1|2|3|4|5}', $result);
}

function conditionalWrite(bool $b): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		if ($b) {
			$result[$key] = $value;
		}
	}
	assertType('array{a?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, b?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, c?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, d?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, e?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, f?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, g?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, h?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, i?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, j?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, k?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, l?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, m?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, n?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, o?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, p?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, q?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17}', $result);
}

function writeThenBreak(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result[$key] = $value;
		if ($value === 3) {
			break;
		}
	}
	assertType('1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|null', $result['q'] ?? null);
}

function unsetOtherKey(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result[$key] = $value;
		if ($value === 3) {
			unset($result['a']);
		}
	}
	assertType('1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|null', $result['a'] ?? null);
}

function reassigned(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result = [];
		$result[$key] = $value;
	}
	assertType('1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|null', $result['a'] ?? null);
}

function passedByReference(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$result[$key] = $value;
		takeByReference($result);
	}
	assertType('mixed', $result['a'] ?? null);
}

/**
 * @param array<mixed> $a
 */
function takeByReference(array &$a): void
{
}

function loopVariableReassigned(): void
{
	$result = [];
	foreach (MAP as $key => $value) {
		$key = 'a';
		$result[$key] = $value;
	}
	assertType('array{a: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17}', $result);
}

function otherWriteForms(): void
{
	$r = [];
	foreach (MAP as $k => $v) {
		$r[$k] ??= $v;
	}
	assertType('true', array_key_exists('q', $r));

	$r = [];
	foreach (MAP as $k => $v) {
		$r[$k][] = $v;
	}
	assertType('true', array_key_exists('q', $r));

	$r = [];
	foreach (MAP as $k => $v) {
		$r[$k]['x'] = $v;
	}
	assertType('true', array_key_exists('q', $r));

	$r = [];
	foreach (MAP as $k => $v) {
		$r[$k] = &$v;
	}
	assertType('true', array_key_exists('q', $r));

	$r = [];
	foreach (MAP as $k => $v) {
		[$r[$k]] = [$v];
	}
	assertType('true', array_key_exists('q', $r));
}

final class PropertyTarget
{

	/** @var array<string, int> */
	private array $props = [];

	public function writtenInEveryIteration(): void
	{
		$this->props = [];
		foreach (MAP as $key => $value) {
			$this->props[$key] = $value;
		}
		assertType('array{a: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, b: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, c: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, d: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, e: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, f: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, g: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, h: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, i: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, j: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, k: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, l: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, m: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, n: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, o: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, p: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, q: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17}', $this->props);
	}

	public function resetAfterWrite(): void
	{
		$this->props = [];
		foreach (MAP as $key => $value) {
			$this->props[$key] = $value;
			$this->reset();
		}
		assertType('array<string, int>', $this->props);
	}

	public function reassignedAfterWrite(): void
	{
		$this->props = [];
		foreach (MAP as $key => $value) {
			$this->props[$key] = $value;
			$this->props = [];
		}
		assertType('array{}', $this->props);
	}

	public function reset(): void
	{
		$this->props = [];
	}

}

final class ShapePropertyTarget
{

	/** @var array{a?: int, b?: int, c?: int, d?: int, e?: int, f?: int, g?: int, h?: int, i?: int, j?: int, k?: int, l?: int, m?: int, n?: int, o?: int, p?: int, q?: int} */
	private array $props = [];

	public function resetBeforeWrite(): void
	{
		foreach (MAP as $key => $value) {
			$this->reset();
			$this->props[$key] = $value;
		}
		assertType('non-empty-array{a?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, b?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, c?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, d?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, e?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, f?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, g?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, h?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, i?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, j?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, k?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, l?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, m?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, n?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, o?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, p?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17, q?: 1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17}', $this->props);
	}

	public function reset(): void
	{
		$this->props = [];
	}

}
