<?php

namespace Bug2676;

use DoctrineIntersectionTypeIsSupertypeOf\Collection;
use function PHPStan\Testing\assertType;

class BankAccount
{

}

/**
 * @ORM\Table
 * @ORM\Entity
 */
class Wallet
{
	/**
	 * @var Collection<BankAccount>
	 *
	 * @ORM\OneToMany(targetEntity=BankAccount::class, mappedBy="wallet")
	 * @ORM\OrderBy({"id" = "ASC"})
	 */
	private $bankAccountList;

	/**
	 * @return Collection<BankAccount>
	 */
	public function getBankAccountList(): Collection
	{
		return $this->bankAccountList;
	}
}

function (Wallet $wallet): void
{
	$bankAccounts = $wallet->getBankAccountList();
	assertType('DoctrineIntersectionTypeIsSupertypeOf\Collection&iterable<Bug2676\BankAccount>', $bankAccounts);

	foreach ($bankAccounts as $key => $bankAccount) {
		assertType('mixed', $key);
		assertType('Bug2676\BankAccount', $bankAccount);
	}
};
