<?php // lint >= 8.0

namespace Bug14705;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * strpos with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 * @param non-empty-string $needle
	 */
	public function strposNonEmpty(string $haystack, string $needle): void
	{
		if (strpos($haystack, $needle) !== false) {
			assertType('non-empty-string', $haystack);
			assertType('non-empty-string', $needle);
		}
	}

	/**
	 * str_contains with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strContainsNonEmpty(string $haystack, string $needle): void
	{
		if (str_contains($haystack, $needle)) {
			assertType('non-empty-string', $haystack);
			assertType('string', $needle);
		}
	}

	/**
	 * str_starts_with with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strStartsWithNonEmpty(string $haystack, string $needle): void
	{
		if (str_starts_with($haystack, $needle)) {
			assertType('non-empty-string', $haystack);
			assertType('string', $needle);
		}
	}

	/**
	 * str_ends_with with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strEndsWithNonEmpty(string $haystack, string $needle): void
	{
		if (str_ends_with($haystack, $needle)) {
			assertType('non-empty-string', $haystack);
			assertType('string', $needle);
		}
	}

	/**
	 * array_key_exists with non-constant key on a non-empty-array should not report always-true.
	 *
	 * @param non-empty-array<string, int> $array
	 */
	public function arrayKeyExistsNonEmpty(array $array, string $key): void
	{
		if (array_key_exists($key, $array)) {
			assertType('non-empty-array<string, int>', $array);
		}
	}

	/**
	 * @phpstan-assert-if-true =non-empty-string $foo
	 */
	public function isValid(string $foo): bool
	{
		return $foo !== '';
	}

	public function equalityAssertDuplicate(string $task): void
	{
		if ($this->isValid($task)) {
			assertType('non-empty-string', $task);
			if ($this->isValid($task)) { // reported as always-true
				assertType('non-empty-string', $task);
			}
		}
	}

	/**
	 * @phpstan-assert =non-empty-string $foo
	 */
	public function assertValid(string $foo): void
	{
		if ($foo === '') {
			throw new \Exception();
		}
	}

	public function voidAssertDuplicate(string $task): void
	{
		$this->assertValid($task);
		assertType('non-empty-string', $task);
		$this->assertValid($task); // reported as always-true
		assertType('non-empty-string', $task);
	}

	public function realpathElvis(string $fileName): void
	{
		$fileName = realpath($fileName) ?: $fileName;
		assertType('string', $fileName);
	}

	/** @param list<string> $paths */
	public function realpathElvisWithLoop(string $fileName, array $paths): void
	{
		$fileName = realpath($fileName) ?: $fileName;
		assertType('string', $fileName);

		foreach ($paths as $path) {
			if (str_starts_with($fileName, $path)) {
				assertType('string', $fileName);
			}
		}
	}

	/**
	 * Duplicate array_key_exists after an early-continue narrows the negated
	 * call to false, while the non-negated call stays bool.
	 *
	 * @param array<string,string|array<int,string>> $theInput
	 * @phpstan-param array{'name':string,'owners':array<int,string>} $theInput
	 * @param array<int,string> $theTags
	 */
	public function arrayKeyExistsDuplicateInLoop(array $theInput, array $theTags): void
	{
		foreach ($theTags as $tag) {
			if (!array_key_exists($tag, $theInput)) {
				continue;
			}
			assertType('false', !array_key_exists($tag, $theInput));
			assertType('bool', array_key_exists($tag, $theInput)); // could be true
		}
	}

	/**
	 * scandir has an equality assertion (@phpstan-assert-if-true =non-empty-string
	 * $directory) but returns list<string>|false. The equality boolean marker must
	 * not overwrite that non-boolean return type, otherwise the Elvis operand would
	 * collapse to array{} and report a false "Empty array passed to foreach.".
	 */
	public function scandirElvis(string $srcDirectory): void
	{
		$files = scandir($srcDirectory) ?: [];
		assertType('list<string>', $files);

		foreach ($files as $value) {
			assertType('string', $value);
		}

		if (scandir($srcDirectory)) {
			assertType('non-empty-list<string>', scandir($srcDirectory));
		}
	}

}
