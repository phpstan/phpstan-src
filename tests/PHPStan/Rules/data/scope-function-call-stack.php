<?php // lint >= 8.0

namespace ScopeFunctionCallStack;

function (): void
{
	var_dump(print_r(sleep(throw new \Exception())));

	var_dump(print_r(function () {
		sleep(throw new \Exception());
	}));

	var_dump(print_r(fn () => sleep(throw new \Exception())));
};

class NamedArgumentTest
{
	/**
	 * @param-immediately-invoked-callable $immediate
	 */
	public function testMethod(callable $immediate, ?callable $notImmediate = null): void
	{
		$immediate();
	}

	public function test(): void
	{
		// When using named argument for $notImmediate, the parameter should be reported as "notImmediate", not "immediate"
		$this->testMethod(
			notImmediate: function () {
				throw new \Exception(); // should report: NamedArgumentTest::testMethod ($notImmediate)
			},
			immediate: function () {},
		);
	}

	public function testMissingRequiredArg(): void
	{
		// Named argument with missing required param - should still match parameter by name
		$this->testMethod(
			notImmediate: function () {
				throw new \Exception(); // reports $notImmediate
			},
		);
	}
}
