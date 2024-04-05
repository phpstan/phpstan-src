<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use function array_map;
use function count;
use function floor;
use function ksort;
use function sprintf;
use function str_starts_with;
use function usort;
use const SORT_NUMERIC;

class TabCreator
{

	/**
	 * @param array<int, list<PlaygroundError>> $versionedErrors
	 * @return list<PlaygroundResultTab>
	 */
	public function create(array $versionedErrors): array
	{
		ksort($versionedErrors, SORT_NUMERIC);

		$versions = [];
		$last = null;

		foreach ($versionedErrors as $phpVersion => $errors) {
			$errors = array_map(static function (PlaygroundError $error): PlaygroundError {
				if ($error->getIdentifier() === null) {
					return $error;
				}

				if (!str_starts_with($error->getIdentifier(), 'phpstanPlayground.')) {
					return $error;
				}

				return new PlaygroundError(
					$error->getLine(),
					sprintf('Tip: %s', $error->getMessage()),
					$error->getIdentifier(),
				);
			}, $errors);
			$current = [
				'versions' => [$phpVersion],
				'errors' => $errors,
			];
			if ($last === null) {
				$last = $current;
				continue;
			}

			if (count($errors) !== count($last['errors'])) {
				$versions[] = $last;
				$last = $current;
				continue;
			}

			$merge = true;
			foreach ($errors as $i => $error) {
				$lastError = $last['errors'][$i];
				if ($error->getLine() !== $lastError->getLine()) {
					$versions[] = $last;
					$last = $current;
					$merge = false;
					break;
				}
				if ($error->getMessage() !== $lastError->getMessage()) {
					$versions[] = $last;
					$last = $current;
					$merge = false;
					break;
				}
			}

			if (!$merge) {
				continue;
			}

			$last['versions'][] = $phpVersion;
		}

		if ($last !== null) {
			$versions[] = $last;
		}

		usort($versions, static function ($a, $b): int {
			$aVersion = $a['versions'][count($a['versions']) - 1];
			$bVersion = $b['versions'][count($b['versions']) - 1];

			return $bVersion - $aVersion;
		});

		$tabs = [];

		foreach ($versions as $version) {
			$title = 'PHP ';
			if (count($version['versions']) > 1) {
				$title .= $this->versionNumberToString($version['versions'][0]);
				$title .= ' – ';
				$title .= $this->versionNumberToString($version['versions'][count($version['versions']) - 1]);
			} else {
				$title .= $this->versionNumberToString($version['versions'][0]);
			}

			if (count($version['errors']) === 1) {
				$title .= ' (1 error)';
			} elseif (count($version['errors']) > 0) {
				$title .= ' (' . count($version['errors']) . ' errors)';
			}

			$tabs[] = new PlaygroundResultTab($title, $version['errors']);
		}

		return $tabs;
	}

	private function versionNumberToString(int $versionId): string
	{
		$first = (int) floor($versionId / 10000);
		$second = (int) floor(($versionId % 10000) / 100);
		$third = (int) floor($versionId % 100);

		return $first . '.' . $second . ($third !== 0 ? '.' . $third : '');
	}

}
