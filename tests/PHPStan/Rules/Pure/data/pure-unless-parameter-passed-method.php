<?php

namespace PureUnlessParameterPassedMethod;

class Replacer
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count): string
	{
		$count = 1;

		return $subject;
	}

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replaceOptional(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

}
