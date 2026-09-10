<?php declare(strict_types = 1);

namespace PHPStan\Compiler;

use PHPStan\ShouldNotHappenException;
use function array_keys;
use function array_reverse;
use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function preg_quote;
use function preg_replace_callback;
use function realpath;
use function sprintf;
use function str_starts_with;
use function substr;
use function usort;
use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * Applies the call-site edits InlineCallCollector gathered (build/inline.neon)
 * to the sources in place, then makes the non-public properties the inlined
 * bodies read public — a property read moved from its class into a caller
 * needs to be accessible there. Never a property of php-parser or
 * phpdoc-parser (PROTECTED_PACKAGE_DIRECTORIES): the collector does not
 * record those, and an edit that would need one is refused here rather than
 * applied.
 *
 * Overlapping edits (a call inside an argument of another inlined call)
 * resolve outermost-wins: the outer replacement was printed from the
 * callee's body with the original argument source, so the inner call inside
 * it simply stays a call.
 */
final class InlineEditsApplier
{

	/**
	 * Same list as InlineCallCollector::PROTECTED_PACKAGE_DIRECTORIES —
	 * packages the phar ships unprefixed and projects install on their own
	 * too, so the copy loaded at run time may be one with the properties
	 * still non-public.
	 */
	private const PROTECTED_PACKAGE_DIRECTORIES = [
		'vendor/nikic/php-parser',
		'vendor/phpstan/phpdoc-parser',
	];

	/** @var list<string> */
	private array $protectedDirectories = [];

	/**
	 * @param list<string>|null $protectedDirectories defaults to PROTECTED_PACKAGE_DIRECTORIES under the repository root
	 */
	public function __construct(?array $protectedDirectories = null)
	{
		if ($protectedDirectories === null) {
			$protectedDirectories = [];
			foreach (self::PROTECTED_PACKAGE_DIRECTORIES as $directory) {
				$protectedDirectories[] = dirname(__DIR__, 2) . '/' . $directory;
			}
		}
		foreach ($protectedDirectories as $directory) {
			$realDirectory = realpath($directory);
			$this->protectedDirectories[] = ($realDirectory === false ? $directory : $realDirectory) . DIRECTORY_SEPARATOR;
		}
	}

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
					if ($this->isInProtectedPackage($property['file'])) {
						throw new ShouldNotHappenException(sprintf('Refusing to make %s::$%s public, it is declared in a protected package (%s)', $property['class'], $property['property'], $property['file']));
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
				// visibility, optional modifiers and type, then the variable — made
				// public, with the source visibility recorded in an attribute PHPStan's
				// own reflection honours (same line, so line numbers stay)
				$source = preg_replace_callback(
					'/\b(private|protected)(\s+(?:readonly\s+|static\s+)*(?:[?\w\\\\|&()]+\s+)?)\$' . preg_quote($name, '/') . '\b/',
					static fn (array $matches): string => sprintf(
						'#[\PHPStan\Reflection\Attribute\%s] public%s$%s',
						$matches[1] === 'private' ? 'PrivateProperty' : 'ProtectedProperty',
						$matches[2],
						$name,
					),
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

	private function isInProtectedPackage(string $file): bool
	{
		$realFile = realpath($file);
		foreach ($this->protectedDirectories as $directory) {
			if (str_starts_with($realFile === false ? $file : $realFile, $directory)) {
				return true;
			}
		}

		return false;
	}

}
