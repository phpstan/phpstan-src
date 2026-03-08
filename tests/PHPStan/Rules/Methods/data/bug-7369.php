<?php declare(strict_types=1);

namespace Bug7369;

interface AccountInterface {}

interface AccessResultInterface {
	public function isAllowed(): bool;
}

interface AccessibleInterface {

	/**
	 * @return ($return_as_object is true ? AccessResultInterface : bool)
	 */
	public function access(string $operation, AccountInterface $account = NULL, bool $return_as_object = FALSE);
}

$class = new class() implements AccessibleInterface {
	/**
	 * {@inheritDoc}
	 */
	public function access(
		string $operation,
		AccountInterface $account = null,
		bool $return_as_object = false
	) {
		if ($return_as_object) {
			return new class () implements AccessResultInterface {
				public function isAllowed(): bool {
					return true;
				}
			};
		}
		return false;
	}
};

$class->access('view', null, true)->isAllowed();
$class->access('view', null, false)->isAllowed();

$params = ['view', null, true];
$class->access(...$params)->isAllowed();
