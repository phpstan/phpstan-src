<?php


namespace AlwaysTruePregMatch;

class X
{
	/**
	 * @param string $test A long description format of the current test
	 *
	 * @return string The test name without TestSuiteClassName:: and @dataprovider details
	 */
	public function getTestName(string $test): string
	{
		$matches = [];

		if (preg_match('/^(?<name>\S+::\S+)/', $test, $matches)) {
			$test = $matches['name'];
		}

		return $test;
	}
}
