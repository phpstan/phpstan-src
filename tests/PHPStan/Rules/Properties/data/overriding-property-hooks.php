<?php // lint >= 8.4

namespace OverridingPropertyHooks;

class FinalHookParent
{

	public int $prop {
		final get => 42;
	}

	public int $setProp {
		final set => $value;
	}

	public int $notFinal {
		get => 42;
	}

}

class FinalHookChild extends FinalHookParent
{

	public int $prop {
		get => 24;
	}

	public int $setProp {
		set => $value;
	}

	public int $notFinal {
		get => 24;
	}

}

interface ByRefGet
{

	public int $prop { &get; }

	public int $byValue { get; }

}

class ImplementsByRefGetByValue implements ByRefGet
{

	public int $prop {
		get => 1;
	}

	public int $byValue {
		get => 1;
	}

}

class ImplementsByRefGetByRef implements ByRefGet
{

	private int $storage = 1;

	public int $prop {
		&get => $this->storage;
	}

	public int $byValue {
		&get => $this->storage;
	}

}

class ImplementsByRefGetBacked implements ByRefGet
{

	public int $prop = 1;

	public int $byValue = 1;

}

class BackedParent
{

	public int $prop = 1;

	public int $onlyGet = 1;

}

class BackedChild extends BackedParent
{

	private int $storage = 1;

	public int $prop {
		&get => $this->storage;
		set {
			$this->storage = $value;
		}
	}

	public int $onlyGet {
		&get => $this->storage;
	}

}

interface SetWider
{

	public string $prop {
		set(int|string $value);
	}

	public string $sameType {
		set(string $value);
	}

}

class ImplementsSetWider implements SetWider
{

	public string $prop;

	public string $sameType;

}

class ImplementsSetWiderWithWideType implements SetWider
{

	public int|string $prop;

	public string $sameType;

}

class ImplementsSetWiderWithHook implements SetWider
{

	public string $prop {
		set(int|string $value) {
			$this->prop = (string) $value;
		}
	}

	public string $sameType;

}

interface X {}
interface Y extends X {}

class SetParameterParent
{

	public Y $prop {
		set(X $prop) {
		}
	}

	public Y $other {
		set(Y $value) {
		}
	}

}

class SetParameterChild extends SetParameterParent
{

	public Y $prop {
		set(Y $prop) {
		}
	}

	public Y $other {
		set(X $value) {
		}
	}

}

class OverrideParent
{

	public $setOnly {
		set {}
	}

	public $getOnly {
		get => 1;
	}

	public $backed;

}

class OverrideChild extends OverrideParent
{

	public $setOnly {
		#[\Override]
		get => 1;
	}

	public $getOnly {
		#[\Override]
		get => 2;
		#[\Override]
		set {}
	}

	public $backed {
		#[\Override]
		get => $this->backed;
		#[\Override]
		set => $value;
	}

	public $notInParent {
		#[\Override]
		get => 1;
	}

}
