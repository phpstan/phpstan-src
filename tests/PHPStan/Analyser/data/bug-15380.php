<?php // lint >= 8.1

namespace Bug15380;

enum Suit
{
	case Hearts;
	case Spades;
}

/**
 * @method void doMagic(Suit $suit = Suit::Hearts)
 */
class Foo
{

	public const SUIT = Suit::Hearts;

	/**
	 * @param Suit::Hearts $a
	 * @param Suit::* $b
	 * @param array{Suit::Hearts: int} $c
	 */
	public function doFoo(Suit $suit, $a, $b, array $c, Suit $d = Suit::Spades): void
	{
		echo round(1.5);
		var_dump(Suit::Hearts, self::SUIT, $suit::Spades);
	}

	/**
	 * @param mixed[] $args
	 */
	public function __call(string $name, array $args): void
	{
	}

	public function doBar(): void
	{
		$this->doMagic();
	}

}
