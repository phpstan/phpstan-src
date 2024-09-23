<?php

namespace Bug4174;

class HelloWorld
{
	public const NUMBER_TYPE_OFF = 1;
	public const NUMBER_TYPE_HEAD = 2;
	public const NUMBER_TYPE_POSITION = 3;

	/**
	 * @var HelloWorld::NUMBER_TYPE_*
	 */
	private int $newValue = self::NUMBER_TYPE_OFF;

	/**
	 * @return array<HelloWorld::NUMBER_TYPE_*,string>
	 */
	public static function getArrayConstAsKey(): array {
		return [
			self::NUMBER_TYPE_OFF      => 'Off',
			self::NUMBER_TYPE_HEAD     => 'Head',
			self::NUMBER_TYPE_POSITION => 'Position',
		];
	}

	/**
	 * @return list<HelloWorld::NUMBER_TYPE_*>
	 */
	public static function getArrayConstAsValue(): array {
		return [
			self::NUMBER_TYPE_OFF,
			self::NUMBER_TYPE_HEAD,
			self::NUMBER_TYPE_POSITION,
		];
	}

	public function checkConstViaArrayKey(): void
	{
		$numberArray = self::getArrayConstAsKey();

		// ---

		$newvalue = $this->getIntFromPost('newValue');

		if ($newvalue && array_key_exists($newvalue, $numberArray)) {
			$this->newValue = $newvalue;
		}

		if (isset($numberArray[$newvalue])) {
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = $this->getIntFromPostWithoutNull('newValue');

		if ($newvalue && array_key_exists($newvalue, $numberArray)) {
			$this->newValue = $newvalue;
		}

		if (isset($numberArray[$newvalue])) {
			$this->newValue = $newvalue;
		}
	}

	public function checkConstViaArrayValue(): void
	{
		$numberArray = self::getArrayConstAsValue();

		// ---

		$newvalue = $this->getIntFromPost('newValue');

		if ($newvalue && in_array($newvalue, $numberArray, true)) {
			$this->newValue = $newvalue;
		}

		// ---

		$newvalue = $this->getIntFromPostWithoutNull('newValue');

		if ($newvalue && in_array($newvalue, $numberArray, true)) {
			$this->newValue = $newvalue;
		}
	}

	public function getIntFromPost(string $key): ?int {
		return isset($_POST[$key]) ? (int)$_POST[$key] : null;
	}

	public function getIntFromPostWithoutNull(string $key): int {
		return isset($_POST[$key]) ? (int)$_POST[$key] : 0;
	}
}
