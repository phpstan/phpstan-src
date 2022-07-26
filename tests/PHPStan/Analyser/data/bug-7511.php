<?php declare(strict_types = 1);

namespace Bug7511;

interface PositionEntityInterface {
	public function getPosition(): int;
}
interface TgEntityInterface {}

abstract class HelloWorld
{
	/**
	 * @phpstan-template T of PositionEntityInterface&TgEntityInterface
	 *
	 * @param iterable<T> $tgs
	 *
	 * @return array<T>
	 *
	 * @throws \Exception
	 */
	public function computeForFrontByPosition($tgs)
	{
		/** @phpstan-var array<T> $res */
		$res = [];

		foreach ($tgs as $tgItem) {
			$position = $tgItem->getPosition();

			if (isset($res[$position])) {
				assertType('T of PositionEntityInterface&TgEntityInterface', $res[$position]);
			}
		}

		return $res;
	}

	/**
	 * @phpstan-template S of TgEntityInterface
	 * @phpstan-param S $nextTg
	 * @phpstan-param S $currentTg
	 * @phpstan-return S
	 */
	abstract protected function compare(TgEntityInterface $nextTg, TgEntityInterface $currentTg): TgEntityInterface;
}
