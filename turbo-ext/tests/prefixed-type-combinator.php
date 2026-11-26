<?php declare(strict_types = 1);

/**
 * TypeCombinator is not shadowed: the native code calls its PHP body, which
 * names the Type classes it builds and tells apart. Under the prefixed
 * activation the native classes are PHPStanTurbo\*, which that body does not
 * know, so this declares a copy of it over the prefixed names:
 * PHPStanTurbo\TypeCombinator, with the PHP helpers it runs on, every
 * reference to a shadowed class rewritten to the native class's prefixed
 * name. The class map routes the native code's calls of TypeCombinator to
 * the copy, so a native Type graph is combined by a combinator of its own
 * classes, as in production.
 *
 * @param array<string, array{turboClass: string}> $manifest
 * @param array<string, string> $classMap
 * @return array<string, string> the class map routing typeCombinator to the copy
 */
return static function (array $manifest, array $classMap): array {
	$root = dirname(__DIR__, 2);
	$copies = [
		'PHPStan\\Type\\TypeCombinator' => $root . '/src/Type/TypeCombinator.php',
		'PHPStan\\Type\\ScalarUnion' => $root . '/src/Type/ScalarUnion.php',
		'PHPStan\\Type\\StringRefinement' => $root . '/src/Type/StringRefinement.php',
	];
	if (class_exists('PHPStanTurbo\\TypeCombinator', false)) {
		$classMap['typeCombinator'] = 'PHPStanTurbo\\TypeCombinator';
		return $classMap;
	}

	$prefixed = static function (string $class) use ($manifest, $copies): ?string {
		if (isset($manifest[$class])) {
			return $manifest[$class]['turboClass'];
		}
		if (isset($copies[$class])) {
			return 'PHPStanTurbo\\' . substr($class, strrpos($class, '\\') + 1);
		}
		return null;
	};

	foreach ($copies as $phpClass => $file) {
		$namespace = substr($phpClass, 0, strrpos($phpClass, '\\'));
		$imported = [];
		$names = [];
		$code = '';
		$tokens = token_get_all(file_get_contents($file));
		foreach ($tokens as $i => $token) {
			if (!is_array($token)) {
				$code .= $token;
				continue;
			}
			[$id, $text] = $token;
			if ($id === T_NAME_QUALIFIED && ($tokens[$i - 2][0] ?? null) === T_NAMESPACE) {
				// namespace PHPStan\Type; - the copy lives next to the native classes
				$code .= 'PHPStanTurbo';
				continue;
			}
			if ($id === T_NAME_QUALIFIED || $id === T_NAME_FULLY_QUALIFIED) {
				$class = ltrim($text, '\\');
				$target = $prefixed($class);
				if ($target !== null) {
					$imported[substr($class, strrpos($class, '\\') + 1)] = true;
					$code .= ($id === T_NAME_FULLY_QUALIFIED ? '\\' : '') . $target;
					continue;
				}
				$imported[substr($class, strrpos($class, '\\') + 1)] = true;
			}
			if ($id === T_STRING) {
				$names[$text] = true;
			}
			$code .= $text;
		}

		// a class of the original namespace named without a use statement
		// resolved there; in the copy it names the prefixed class when
		// shadowed, else it is imported from where it lives
		$uses = '';
		foreach (array_keys($names) as $name) {
			$class = $namespace . '\\' . $name;
			if (isset($imported[$name]) || $prefixed($class) !== null) {
				continue;
			}
			if (!class_exists($class) && !interface_exists($class) && !trait_exists($class) && !enum_exists($class)) {
				continue;
			}
			$uses .= "use {$class};\n";
		}
		$code = preg_replace('~^namespace PHPStanTurbo;\n~m', "namespace PHPStanTurbo;\n\n" . $uses, $code, 1);

		$copyFile = sys_get_temp_dir() . '/phpstan-turbo-prefixed-' . getmypid() . '-' . substr($phpClass, strrpos($phpClass, '\\') + 1) . '.php';
		file_put_contents($copyFile, $code);
		require $copyFile;
		unlink($copyFile);
	}

	$classMap['typeCombinator'] = 'PHPStanTurbo\\TypeCombinator';
	return $classMap;
};
