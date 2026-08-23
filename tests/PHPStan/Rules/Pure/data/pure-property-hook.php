<?php // lint >= 8.4

declare(strict_types = 1);

namespace PurePropertyHook;

final class Foo
{

	private int $backing = 1;

	public int $pureGetWithSideEffect {
		/** @phpstan-pure */
		get {
			echo 'side effect';

			return $this->backing;
		}
	}

	public int $pureGet {
		/** @phpstan-pure */
		get => $this->backing;
	}

	public int $impureGetWithoutSideEffect {
		/** @phpstan-impure */
		get => $this->backing;
	}

	public int $impureGet {
		/** @phpstan-impure */
		get {
			echo 'side effect';

			return $this->backing;
		}
	}

	public int $unannotatedGet {
		get {
			echo 'side effect';

			return $this->backing;
		}
	}

	public int $pureSet {
		/** @phpstan-pure */
		set {
			$this->pureSet = $value;
		}
	}

	public int $impureSet {
		/** @phpstan-impure */
		set {
			$this->impureSet = $value;
		}
	}

}

class NotFinal
{

	public int $impureGetWithoutSideEffect {
		/** @phpstan-impure */
		get => 1;
	}

	public int $finalImpureGetWithoutSideEffect {
		/** @phpstan-impure */
		final get => 1;
	}

}

abstract class AbstractGetHookFollowedBySetHook
{

	abstract public int $mixedHooks {
		get;
		/** @phpstan-pure */
		set {
			echo 'side effect';
		}
	}

}
