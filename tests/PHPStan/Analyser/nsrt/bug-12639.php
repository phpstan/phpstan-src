<?php declare(strict_types=1);

namespace Bug12639\Database\FieldTypes;

/** @template T */
class ObjectRefT {}

namespace Bug12639\Accounts;

class Account {}

namespace Bug12639\Policy;

if (!defined('Bug12639')) {
	define('Bug12639', true);
}

use Bug12639\Database\FieldTypes;
use Bug12639\Accounts\Account;

use function PHPStan\Testing\assertType;

trait BaseAccount
{
	/** @var FieldTypes\ObjectRefT<Account> */
	protected FieldTypes\ObjectRefT $account;

	protected function test(): void
	{
		assertType('Bug12639\Database\FieldTypes\ObjectRefT<Bug12639\Accounts\Account>', $this->account);
	}
}

class StandardAccount
{
	use BaseAccount;

	public function run(): void
	{
		assertType('Bug12639\Database\FieldTypes\ObjectRefT<Bug12639\Accounts\Account>', $this->account);
	}
}
