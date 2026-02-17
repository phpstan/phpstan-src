<?php // lint >= 8.0

namespace Bug10053;

class Account {}

class MyAssert
{
	/**
	 * @param iterable<mixed|null> $values
	 */
	public static function allNotNull(iterable $values): void
	{
	}
}

class Foo
{
	/**
	 * @param list<string> $ids
	 * @return list<Account>
	 */
	private function accounts(array $ids): array
	{
		$accounts = array_map(fn (string $id): ?Account => $this->findAccount($id), $ids);

		MyAssert::allNotNull($accounts);

		return $accounts;
	}

	private function findAccount(string $id): ?Account
	{
		return null;
	}
}
