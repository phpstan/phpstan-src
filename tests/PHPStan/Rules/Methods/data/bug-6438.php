<?php declare(strict_types = 1);

namespace Bug6438;

class HelloWorld
{
    /**
     * @template       T of (bool|int)
     * @phpstan-param  T $value
     * @phpstan-return array{value: T, description: string}|null
     */
    private function getValueDescription($value, string $description)
    {
        if (false === $value) {
            return null;
        }

        return ['value' => $value, 'description' => $description];
    }

	/**
	 * @phpstan-return array{value: int, description: string}|null
	 */
	public function testInteger()
    {
		return $this->getValueDescription(5, 'Description');
    }

	/**
	 * @phpstan-return array{value: bool, description: string}|null
	 */
	public function testBooleanTrue()
    {
		return $this->getValueDescription(true, 'Description');
    }

	/**
	 * @phpstan-return array{value: bool, description: string}|null
	 */
	public function testBooleanFalse()
    {
		return $this->getValueDescription(false, 'Description');
    }
}
