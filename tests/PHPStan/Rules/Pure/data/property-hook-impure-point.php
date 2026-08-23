<?php // lint >= 8.4

declare(strict_types = 1);

namespace PropertyHookImpurePoint;

final class Foo
{

	private int $backing = 1;

	public int $plain = 1;

	public int $impureGet {
		/** @phpstan-impure */
		get {
			echo 'side effect';

			return $this->backing;
		}
	}

	public int $pureGet {
		/** @phpstan-pure */
		get => $this->backing;
	}

	public int $unannotatedGet {
		get => $this->backing;
	}

	public int $impureSet {
		/** @phpstan-impure */
		set {
			$this->impureSet = $value;
		}
	}

	/** @phpstan-pure */
	public function readOwnImpureGet(): int
	{
		return $this->impureGet;
	}

	/** @phpstan-pure */
	public function readOwnPureGet(): int
	{
		return $this->pureGet;
	}

}

/** @phpstan-pure */
function readImpureGet(Foo $foo): int
{
	return $foo->impureGet;
}

/** @phpstan-pure */
function readImpureGetNullsafe(?Foo $foo): ?int
{
	return $foo?->impureGet;
}

/** @phpstan-pure */
function readImpureGetInCompoundAssign(Foo $foo): int
{
	$i = 0;
	$i += $foo->impureGet;

	return $i;
}

/** @phpstan-pure */
function readPureGet(Foo $foo): int
{
	return $foo->pureGet;
}

/** @phpstan-pure */
function readUnannotatedGet(Foo $foo): int
{
	return $foo->unannotatedGet;
}

/** @phpstan-pure */
function readPlainProperty(Foo $foo): int
{
	return $foo->plain;
}

/** @phpstan-pure */
function readUnknownProperty(object $o): mixed
{
	return $o->whatever;
}
