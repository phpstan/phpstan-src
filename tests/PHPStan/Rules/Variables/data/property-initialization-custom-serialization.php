<?php // lint >= 8.2

namespace PropertyInitializationCustomSerialization;

use Serializable;

class NoSerialization
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}

class OnlyWakeup
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}

	public function __wakeup(): void
	{
	}
}

class Sleep
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}

	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

class SerializeAndUnserialize
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}

	/** @return array<string, mixed> */
	public function __serialize(): array
	{
		return [];
	}

	/** @param array<string, mixed> $data */
	public function __unserialize(array $data): void
	{
	}
}

class OnlyUnserialize
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}

	/** @param array<string, mixed> $data */
	public function __unserialize(array $data): void
	{
	}
}

class ParentWithSleep
{
	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

class InheritsSleep extends ParentWithSleep
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}

trait SleepTrait
{
	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

class SleepFromTrait
{
	use SleepTrait;

	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}
}

class OldSchoolSerializable implements Serializable
{
	private string $string;
	private true $true;

	public function __construct()
	{
		$this->string = 'foo';
		$this->true = true;
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
		if (isset($this->string)) {
		}
		if (empty($this->true)) {
		}
	}

	public function serialize(): string
	{
		return '';
	}

	public function unserialize(string $data): void
	{
	}
}

class PromotedNoSerialization
{
	public function __construct(private string $string)
	{
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
	}
}

class PromotedSleep
{
	public function __construct(private string $string)
	{
	}

	public function doFoo(): void
	{
		echo $this->string ?? 'default';
	}

	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

class CoalesceAssignNoSerialization
{
	private string $string;

	public function __construct()
	{
		$this->string = 'foo';
	}

	public function doFoo(): void
	{
		echo $this->string ??= 'default';
	}
}

class CoalesceAssignSleep
{
	private string $string;

	public function __construct()
	{
		$this->string = 'foo';
	}

	public function doFoo(): void
	{
		echo $this->string ??= 'default';
	}

	/** @return list<string> */
	public function __sleep(): array
	{
		return [];
	}
}

function anonymousClassWithSleep(): object
{
	return new class {
		private string $string;

		public function __construct()
		{
			$this->string = 'foo';
		}

		public function doFoo(): void
		{
			echo $this->string ?? 'default';
		}

		/** @return list<string> */
		public function __sleep(): array
		{
			return [];
		}
	};
}
