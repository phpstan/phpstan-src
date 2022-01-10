<?php

namespace Bug4165;

interface Client
{
	/**
	 * @return 'int'|'stg'|'prd'
	 */
	public function env(): string;
}

final class ComparisonKeysBuilder
{
	private Client $client;
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @phpstan-return array<'int'|'stg'|'prd', int>
	 */
	public function __invoke(): array
	{
		$result = [
			'int' => 3,
			'stg' => 4,
			'prd' => 5
		];

		$result[$this->client->env()] = 42;

		return $result;
	}
}
