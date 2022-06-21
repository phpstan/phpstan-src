<?php declare(strict_types = 1);

namespace Bug7500;

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
                $tgItemToKeep   = $this->compare($tgItem, $res[$position]);
                $res[$position] = $tgItemToKeep;
            }
        }
        ksort($res);

        return $res;
    }

	/**
     * @phpstan-template T of TgEntityInterface
     * @phpstan-param T $nextTg
     * @phpstan-param T $currentTg
     * @phpstan-return T
     */
    abstract protected function compare(TgEntityInterface $nextTg, TgEntityInterface $currentTg): TgEntityInterface;
}
