<?php // lint >= 8.1

namespace UnnecessaryTernary;

class Foo
{

}

enum Suit
{
	case Hearts;
	case Spades;
}

class HelloWorld
{

	public function getIntOrNull(?int $i): ?int
	{
		return $i !== null ? $i : null;
	}

	public function getIntOrNullCoalesce(?int $i): ?int
	{
		return $i ?? null;
	}

	public function identical(?int $i): ?int
	{
		return $i === null ? null : $i;
	}

	public function nullFirst(?int $i): ?int
	{
		return null !== $i ? $i : null;
	}

	public function instanceofCheck(?Foo $foo): ?Foo
	{
		return $foo instanceof Foo ? $foo : null;
	}

	public function isInt(?int $i): ?int
	{
		return is_int($i) ? $i : null;
	}

	/**
	 * @param Foo|null $foo
	 */
	public function phpDoc($foo): ?Foo
	{
		return $foo instanceof Foo ? $foo : null;
	}

	public function shortObject(?Foo $foo): ?Foo
	{
		return $foo ?: null;
	}

	public function shortBool(bool $b): bool
	{
		return $b ?: false;
	}

	/**
	 * @param array<int> $a
	 * @return array<int>
	 */
	public function shortArray(array $a): array
	{
		return $a ?: [];
	}

	public function enumCase(Suit $s): Suit
	{
		return $s !== Suit::Hearts ? $s : Suit::Hearts;
	}

	public function shortInt(?int $i): ?int
	{
		// falsy $i is 0|null
		return $i ?: null;
	}

	public function shortString(string $s): string
	{
		// falsy $s is ''|'0'
		return $s ?: '';
	}

	public function otherValue(?int $i): ?int
	{
		return $i !== null ? $i : 0;
	}

	public function float(float $f): float
	{
		return $f !== 0.0 ? $f : 0.0;
	}

	public function shortFloat(?float $f): ?float
	{
		return $f ?: null;
	}

	public function impureCondition(): ?int
	{
		return $this->next() !== null ? $this->next() : null;
	}

	public function impureElse(?Foo $foo): ?Foo
	{
		return $foo ?: $this->nothing();
	}

	public function otherVariable(?int $i, ?int $j): ?int
	{
		return $j !== null ? $i : null;
	}

	public function next(): ?int
	{
		return rand(0, 1) === 1 ? 1 : null;
	}

	public function nothing(): ?Foo
	{
		echo 'x';
		return null;
	}

}

function strlenOfFalse(string|false $blob): int
{
	return $blob === false ? 0 : strlen($blob);
}

function strlenOfString(string|false $blob): int
{
	return $blob !== false ? strlen($blob) : 0;
}

/** @return null */
function maybeSideEffects()
{
	return null;
}

function callWithMaybeSideEffects(?Foo $foo): ?Foo
{
	return $foo ?: maybeSideEffects();
}

trait FooTrait
{

	public function shortObject(?Foo $foo): ?Foo
	{
		return $foo ?: null;
	}

}

class UsesFooTrait
{

	use FooTrait;

}

class Issets
{

	public function maybeUndefined(): ?int
	{
		if (rand(0, 1)) {
			$a = 1;
		}

		return isset($a) ? $a : null;
	}

	public function property(Bar $bar): ?int
	{
		return $bar->p !== null ? $bar->p : null;
	}

	public function sameVariables(?int $a, ?int $b): ?int
	{
		return $a !== $b ? $a : $b;
	}

	/**
	 * @param 'a'|'b' $s
	 */
	public function literal(string $s): string
	{
		return $s === 'a' ? 'a' : $s;
	}

	public function booleanNot(?Foo $foo): ?Foo
	{
		return !$foo ? null : $foo;
	}

}

class Bar
{

	public ?int $p = null;

}

class FalsyObjects
{

	public function simpleXml(?\SimpleXMLElement $xml): ?\SimpleXMLElement
	{
		// an empty SimpleXMLElement is falsy
		return $xml ?: null;
	}

	public function simpleXmlNot(?\SimpleXMLElement $xml): ?\SimpleXMLElement
	{
		return !$xml ? null : $xml;
	}

}

class ConstantCondition
{

	public function alwaysFalse(): int
	{
		// TernaryOperatorConstantConditionRule reports this
		$i = 0;
		return $i ?: 0;
	}

}
