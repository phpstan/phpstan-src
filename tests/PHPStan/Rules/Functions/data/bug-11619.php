<?php // lint >= 8.1

namespace Bug11619;

final class Foo implements \Stringable {

	private function __construct(public readonly string $value) {
	}

	public static function fromString(string $string): self {
		return new self($string);
	}

	public function __toString(): string {
		return $this->value;
	}

}

function test(): void
{
	$options = [
		Foo::fromString('c'),
		Foo::fromString('b'),
		Foo::fromString('a'),
	];

	uasort($options, 'strnatcasecmp');
	usort($options, 'strnatcasecmp');

	uasort($options, fn($a, $b) => strnatcasecmp($a, $b));
	uasort($options, fn(string $a, string $b) => strnatcasecmp($a, $b));
}

/**
 * @param array<\Stringable> $a
 * @param callable(\Stringable, \Stringable): int $f
 */
function customUsort(array &$a, callable $f): void
{
	for ($i = 1; $i < count($a); $i++)
		for ($j = $i; $j > 0 && $f($a[$j-1], $a[$j]) > 0; $j--)
			[$a[$j-1], $a[$j]] = [$a[$j], $a[$j-1]];
}

function userlandComparator(string $a, string $b): int {
	return strnatcasecmp($a, $b);
}

function test2(): void
{
	$options = [
		Foo::fromString('c'),
		Foo::fromString('b'),
		Foo::fromString('a'),
	];

	customUsort($options, 'strnatcasecmp');

	uasort($options, 'Bug11619\userlandComparator');
	usort($options, 'Bug11619\userlandComparator');
}
