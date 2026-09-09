<?php declare(strict_types = 1);

namespace PHPStan\Compiler;

use PHPStan\ShouldNotHappenException;
use function array_keys;
use function array_reverse;
use function count;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function substr;
use function usort;
use const JSON_THROW_ON_ERROR;

/**
 * Applies the call-site edits InlineCallCollector gathered (build/inline.neon)
 * to the sources in place, then makes the non-public properties the inlined
 * bodies read public — a property read moved from its class into a caller
 * needs to be accessible there.
 *
 * Overlapping edits (a call inside an argument of another inlined call)
 * resolve outermost-wins: the outer replacement was printed from the
 * callee's body with the original argument source, so the inner call inside
 * it simply stays a call.
 */
final class InlineEditsApplier
{

	/**
	 * @return array{edits: int, files: int, properties: int}
	 */
	public function apply(string $editsJsonFile): array
	{
		/** @var list<array{file: string, start: int, end: int, replacement: string, callee: string, publicize: list<array{class: string, property: string, file: string|null}>}> $edits */
		$edits = json_decode((string) file_get_contents($editsJsonFile), true, 512, JSON_THROW_ON_ERROR);

		$byFile = [];
		foreach ($edits as $edit) {
			$byFile[$edit['file']][] = $edit;
		}

		$applied = 0;
		$publicize = [];
		foreach ($byFile as $file => $fileEdits) {
			usort($fileEdits, static fn (array $a, array $b): int => $a['start'] <=> $b['start'] ?: $b['end'] <=> $a['end']);
			$accepted = [];
			$lastEnd = -1;
			foreach ($fileEdits as $edit) {
				if ($edit['start'] <= $lastEnd) {
					continue;
				}
				$accepted[] = $edit;
				$lastEnd = $edit['end'];
			}
			$source = file_get_contents($file);
			if ($source === false) {
				throw new ShouldNotHappenException(sprintf('Cannot read %s', $file));
			}
			foreach (array_reverse($accepted) as $edit) {
				$source = substr($source, 0, $edit['start']) . $edit['replacement'] . substr($source, $edit['end'] + 1);
				$applied++;
				foreach ($edit['publicize'] as $property) {
					if ($property['file'] === null) {
						throw new ShouldNotHappenException(sprintf('No file for %s::$%s', $property['class'], $property['property']));
					}
					$publicize[$property['file']][$property['property']] = true;
				}
			}
			file_put_contents($file, $source);
		}

		$properties = 0;
		foreach ($publicize as $file => $names) {
			$source = file_get_contents($file);
			if ($source === false) {
				throw new ShouldNotHappenException(sprintf('Cannot read %s', $file));
			}
			foreach (array_keys($names) as $name) {
				// a declaration, a promoted constructor parameter or a trait property:
				// visibility, optional modifiers and type, then the variable
				$source = preg_replace(
					'/\b(private|protected)(\s+(?:readonly\s+|static\s+)*(?:[?\w\\\\|&()]+\s+)?)\$' . preg_quote($name, '/') . '\b/',
					'public$2$' . $name,
					$source,
					-1,
					$count,
				);
				if ($source === null || $count === 0) {
					throw new ShouldNotHappenException(sprintf('Property $%s not found in %s', $name, $file));
				}
				$properties += $count;
			}
			file_put_contents($file, $source);
		}

		return ['edits' => $applied, 'files' => count($byFile), 'properties' => $properties];
	}

}
