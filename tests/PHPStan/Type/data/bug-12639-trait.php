<?php declare(strict_types = 1);

namespace Bug12639Separate\Policy;

if (true) {
	// some statement before use declarations
}

use Bug12639Separate\Types\ObjectRefT;
use Bug12639Separate\Accounts\Account;

trait BaseAccount
{
	/**
	 * @var ObjectRefT<Account>
	 */
	protected ObjectRefT $account;
}
