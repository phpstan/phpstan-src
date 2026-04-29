<?php declare(strict_types = 1);

namespace Bug14553;

use function PHPStan\Testing\assertType;

class Foo {

	const FORMAT_HTML = 'html';

	const FORMAT_MARKDOWN = 'markdown';

	/**
	 * @param list<self::FORMAT_*> $formats
	 * @return array<self::FORMAT_*, string>|string
	 * @phpstan-return ($formats is array{} ? string : ($formats is array{self::FORMAT_MARKDOWN} ? string : ($formats is array{self::FORMAT_HTML} ? string : non-empty-array<self::FORMAT_*, string>)))
	 */
	public function getMessage(array $formats = [])
	{
		$message = '<h1>title</h1>';

		if (!$formats) {
			return $message;
		}

		if (1 === count($formats)) {
			return self::formatMessage($message, array_first($formats));
		}

		$formatted = [];
		foreach ($formats as $format) {
			$formatted[$format] = self::formatMessage($message, $format);
		}

		assertType('non-empty-array{html?: string, markdown?: string}', $formatted);

		return $formatted;
	}

	private function formatMessage(string $message, string $format): string
	{
		if ($format === self::FORMAT_HTML) {
			return $message;
		}

		return '# title';
	}

}
