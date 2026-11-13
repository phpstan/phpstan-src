<?php // lint >= 8.2

namespace DebugInfoReturnNull;

class ReturnsNull
{

	public function __debugInfo()
	{
		return null;
	}

}

class ReturnsNullConditionally
{

	private bool $debug = false;

	/**
	 * @return array<string, mixed>|null
	 */
	public function __DebugInfo(): ?array
	{
		if (!$this->debug) {
			return null;
		}

		$value = null;
		if ($this->debug) {
			return $value;
		}

		return ['debug' => $this->debug];
	}

}

class ReturnsNullFromCall
{

	public function __debugInfo()
	{
		return $this->log();
	}

	private function log(): null
	{
		echo 'debugging';

		return null;
	}

}

class EmptyReturn
{

	public function __debugInfo()
	{
		if (rand(0, 1) === 0) {
			return;
		}

		return [];
	}

}

class ImplicitReturn
{

	public function __debugInfo()
	{
		echo 'debugging';
	}

}

class MaybeReturnsNull
{

	/** @var array<string, mixed>|null */
	private ?array $info = null;

	/**
	 * @return array<string, mixed>|null
	 */
	public function __debugInfo(): ?array
	{
		return $this->info;
	}

	public function getInfo(): ?string
	{
		return null;
	}

}

interface HasDebugInfo
{

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array;

}
