<?php

namespace DeadCodeUnreachable;

class Foo
{

	public function doFoo()
	{
		if (doFoo()) {
			return;
			return;
		}
	}

	public function doBar(string $foo)
	{
		return;
		echo $foo;
	}

	public function doBaz($foo)
	{
		if ($foo) {
			return;
		} else{
			return;
		}

		echo $foo;
	}

	public function doLorem()
	{
		return;
		// this is why...
	}

	public function doLorem2(string $foo)
	{
		return;
		// this is why...

		echo $foo;
	}

	public function doLorem3()
	{
		return;
		;
	}

	public function doLorem4(string $foo)
	{
		return;
		;

		echo $foo;
	}

	/**
	 * @param \stdClass[] $all
	 */
	public function doIpsum(array $all)
	{
		foreach ($all as $a) {

		}

		if (isset($a)) {
			throw new \Exception();
		}

		var_dump($a);
	}

	public function whileIssue(\DateTime $dt)
	{
		while ($dt->getTimestamp() === 1000) {

		}

		echo $dt->getTimestamp();
	}

	public function otherWhileIssue(\DateTime $dt)
	{
		assert($dt->getTimestamp() === 1000);
		while ($dt->getTimestamp() === 1000) {

		}

		echo $dt->getTimestamp();
	}

	public function anotherWhileIssue(\DateTime $dt)
	{
		while ($dt->getTimestamp() === 1000) {
			$dt->modify('+1 day');
		}

		echo $dt->getTimestamp();
	}

	public function yetOtherWhileIssue(\DateTime $dt)
	{
		assert($dt->getTimestamp() === 1000);
		while ($dt->getTimestamp() === 1000) {
			$dt->modify('+1 day');
		}

		echo $dt->getTimestamp();
	}

	public function yetAnotherWhileIssue(\DateTime $dt)
	{
		while ($this->somethingAboutDateTime($dt) === false) {

		}

		echo $dt->getTimestamp();
	}

	public function yetYetAnotherWhileIssue(\DateTime $dt)
	{
		while ($this->somethingAboutDateTime($dt) === false) {
			$dt->modify('+1 day');
		}

		echo $dt->getTimestamp();
	}

	private function somethingAboutDateTime(\DateTime $dt): bool
	{
		return rand(0, 1) ? true : false;
	}

}

class LastElseIf
{

	/**
	 * @param 'a'|'b'|'c' $s
	 * @return void
	 */
	public function doFoo(string $s): void
	{
		if ($s === 'a') {
			return;
		} elseif ($s === 'b') {
			return;
		} elseif ($s === 'c') {
			return;
		}

		echo "test";
	}

}
