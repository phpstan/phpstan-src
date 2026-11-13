<?php declare(strict_types = 1);

namespace Bug15727;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function isMulti(): bool
{
	if (defined('MULTI')) {
		return (bool) MULTI;
	}

	return false;
}

function can(string $cap): bool
{
	return $cap === 'update';
}

function getS(): string
{
	return (string) rand();
}

function variableAssignedUnderOr(): void
{
	if (isMulti()) {
		echo 'network';
	}

	if (!isMulti() || can('update')) {
		$updateData = 1;
	}

	if (!isMulti()) {
		assertVariableCertainty(TrinaryLogic::createYes(), $updateData);
		echo $updateData;
	}
}

function callNarrowedAfterIfWithoutElse(): void
{
	$client = null;
	if (getS()) {
		$client = 1;
	}

	if (getS() && $client !== null) {
		echo 1;
	}

	assertType('string', getS());
}

class Foo
{

	public function isMulti(): bool
	{
		return true;
	}

	public static function isMultiStatic(): bool
	{
		return true;
	}

	public function can(string $cap): bool
	{
		return $cap === 'update';
	}

	public function methodCall(): void
	{
		if ($this->isMulti()) {
			echo 'network';
		}

		if (!$this->isMulti() || $this->can(self::class)) {
			$updateData = 1;
		}

		if (!$this->isMulti()) {
			assertVariableCertainty(TrinaryLogic::createYes(), $updateData);
		}
	}

	public function staticCall(): void
	{
		if (self::isMultiStatic()) {
			echo 'network';
		}

		if (!self::isMultiStatic() || $this->can('update')) {
			$updateData = 1;
		}

		if (!self::isMultiStatic()) {
			assertVariableCertainty(TrinaryLogic::createYes(), $updateData);
		}
	}

}
