<?php // lint >= 8.0

namespace Bug11011;

final class ImpureImpl  {
	/** @phpstan-impure */
	public function doFoo() {
		echo "yes";
		$_SESSION['ab'] = 1;
	}
}

final class PureImpl {
	public function doFoo(): bool {
		return true;
	}
}

final class AnotherPureImpl {
	public function doFoo(): bool {
		return true;
	}
}

class User {
	function doBar(PureImpl|ImpureImpl $f): bool {
		$f->doFoo();
		return true;
	}

	function doBar2(PureImpl|AnotherPureImpl $f): bool {
		$f->doFoo();
		return true;
	}
}
