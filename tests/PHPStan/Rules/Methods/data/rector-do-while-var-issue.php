<?php

namespace RectorDoWhileVarIssue;

class Foo
{

	public function doFoo(string $cls): void
	{
		do {
			if (doBar()) {
				/** @var string $cls */
				[$cls] = $this->processCharacterClass($cls);
			} else {
				$cls = 'foo';
			}
		} while (doFoo());
	}

	public function doFoo2(string $cls): void
	{
		do {
			if (doBar()) {
				[$cls] = $this->processCharacterClass($cls);
			} else {
				$cls = 'foo';
			}
		} while (doFoo());
	}

	/**
	 * @return int[]|string[]
	 */
	private function processCharacterClass(string $cls): array
	{
		return [];
	}

}
