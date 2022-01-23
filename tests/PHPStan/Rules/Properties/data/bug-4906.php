<?php

namespace Bug4906;

class Connection
{
	/**
	 * @var array<string,mixed>
	 * @phpstan-var array<string,mixed>
	 * @psalm-var Params
	 */
	private $params;
}

class HelloWorld
{
	/**
	 * @var array<string,mixed>
	 */
	private $connectionParameters;

	private function overrideConnectionParameters(): void
	{
		$overrideConnectionParameters = \Closure::bind(function (array $connectionParameters) {
			foreach ($connectionParameters as $parameterKey => $parameterValue) {
				$this->params[$parameterKey] = $parameterValue;
			}
		}, $this, Connection::class);
		$overrideConnectionParameters($this->connectionParameters);
	}

}
