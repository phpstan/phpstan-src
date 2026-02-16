<?php declare(strict_types = 1);

namespace Bug12639\Types;

/** @template T */
class ObjectRefT
{
	/** @var T */
	public $value;
}

namespace Bug12639\Accounts;

class Account
{
}

namespace Bug12639\Policy;

use Bug12639\Types\ObjectRefT;
use Bug12639\Accounts\Account;
use function PHPStan\Testing\assertType;

trait BaseAccount
{
	/**
	 * @var ObjectRefT<Account>
	 */
	protected ObjectRefT $account;
}

class StandardAccount
{
	use BaseAccount;

	public function doTest(): void
	{
		assertType('Bug12639\Types\ObjectRefT<Bug12639\Accounts\Account>', $this->account);
	}
}

namespace Bug12639\OtherPlace;

use Bug12639\Policy\BaseAccount;
use function PHPStan\Testing\assertType;

class AnotherUser
{
	use BaseAccount;

	public function doTest(): void
	{
		assertType('Bug12639\Types\ObjectRefT<Bug12639\Accounts\Account>', $this->account);
	}
}
