<?php

namespace Bug7460;

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

			if (!isset($res[$position])) {
				$res[$position] = $tgItem;
			} else {
				/** @phpstan-var T $tgItemToKeep */
				$tgItemToKeep   = $this->compare($tgItem, $res[$position]);
				$res[$position] = $tgItemToKeep;
			}
		}
		ksort($res);

		return $res;
	}

	/**
	 * @phpstan-template T of PositionEntityInterface&TgEntityInterface
	 *
	 * @param iterable<T> $tgs
	 *
	 * @return array<T>
	 *
	 * @throws \Exception
	 */
	public function computeForFrontByPosition2($tgs)
	{
		/** @phpstan-var array<T> $res */
		$res = [];

		foreach ($tgs as $tgItem) {
			$position = $tgItem->getPosition();

			if (!isset($res[$position])) {
				$res[$position] = $tgItem;
			} else {
				/** @phpstan-var T $tgItemToKeep */
				$tgItemToKeep   = $this->compare($tgItem, $res[$position]);
				$res[$position] = $tgItemToKeep;
			}
		}
		ksort($res);

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
