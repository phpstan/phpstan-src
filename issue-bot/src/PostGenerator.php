<?php declare(strict_types = 1);

namespace PHPStan\IssueBot;

use Nette\Utils\Strings;
use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Playground\PlaygroundResultTab;
use SebastianBergmann\Diff\Differ;
use function array_merge;
use function count;
use function implode;
use function sprintf;
use function str_pad;
use function strlen;
use function strpos;
use const STR_PAD_LEFT;

class PostGenerator
{

	public function __construct(private Differ $differ)
	{
	}

	/**
	 * @param list<PlaygroundResultTab> $originalTabs
	 * @param list<PlaygroundResultTab> $currentTabs
	 * @param BotComment[] $botComments
	 * @return array{diff: string, details: string|null}|null
	 */
	public function createText(
		string $hash,
		array $originalTabs,
		array $currentTabs,
		array $botComments,
	): ?array
	{
		foreach ($currentTabs as $tab) {
			foreach ($tab->getErrors() as $error) {
				if (strpos($error->getMessage(), 'Internal error') === false) {
					continue;
				}

				return null;
			}
		}

		$maxDigit = 1;
		foreach (array_merge($originalTabs, $currentTabs) as $tab) {
			foreach ($tab->getErrors() as $error) {
				$length = strlen((string) $error->getLine());
				if ($length <= $maxDigit) {
					continue;
				}

				$maxDigit = $length;
			}
		}
		$originalErrorsText = $this->generateTextFromTabs($originalTabs, $maxDigit);
		$currentErrorsText = $this->generateTextFromTabs($currentTabs, $maxDigit);
		if ($originalErrorsText === $currentErrorsText) {
			return null;
		}

		$diff = $this->differ->diff($originalErrorsText, $currentErrorsText);
		foreach ($botComments as $botComment) {
			if ($botComment->getResultHash() !== $hash) {
				continue;
			}

			if ($botComment->getDiff() === $diff) {
				return null;
			}
		}

		if (count($currentTabs) === 1 && count($currentTabs[0]->getErrors()) === 0) {
			return ['diff' => $diff, 'details' => null];
		}

		$details = [];
		foreach ($currentTabs as $tab) {
			$detail = '';
			if (count($currentTabs) > 1) {
				$detail .= sprintf("%s\n-----------\n\n", $tab->getTitle());
			}

			if (count($tab->getErrors()) === 0) {
				$detail .= "No errors\n";
				$details[] = $detail;
				continue;
			}

			$detail .= "| Line | Error |\n";
			$detail .= "|---|---|\n";

			foreach ($tab->getErrors() as $error) {
				$errorText = Strings::replace($error->getMessage(), "/\r|\n/", '');
				$detail .= sprintf("| %d | `%s` |\n", $error->getLine(), $errorText);
			}

			$details[] = $detail;
		}

		return ['diff' => $diff, 'details' => implode("\n", $details)];
	}

	/**
	 * @param PlaygroundResultTab[] $tabs
	 */
	private function generateTextFromTabs(array $tabs, int $maxDigit): string
	{
		$parts = [];
		foreach ($tabs as $tab) {
			$text = '';
			if (count($tabs) > 1) {
				$text .= sprintf("%s\n==========\n\n", $tab->getTitle());
			}

			if (count($tab->getErrors()) === 0) {
				$text .= 'No errors';
				$parts[] = $text;
				continue;
			}

			$errorLines = [];
			foreach ($tab->getErrors() as $error) {
				$errorLines[] = sprintf('%s: %s', str_pad((string) $error->getLine(), $maxDigit, ' ', STR_PAD_LEFT), $error->getMessage());
			}

			$text .= implode("\n", $errorLines);

			$parts[] = $text;
		}

		return implode("\n\n", $parts);
	}

}
