<?php

namespace Bug12406_2;

final class Foo
{

	public function sayHello(string $s): void
	{
		$stats = [];
		preg_match_all('~
			commit[ ][a-z0-9]{40}\\n
			Author:\s+(.+)\s+<[^>]+>\\n
			AuthorDate:[^\\n]+\\n
			Commit:[^\\n]+\\n
			CommitDate:[^\\n]+\\n\\n
			(\s+(?:[^\n]+\n)+)\n
			[ ](\\d+)[ ]files?[ ]changed,(?:[ ](\\d+)[ ]insertions?\\(\\+\\),?)?(?:[ ](\\d+)[ ]deletions?\\(-\\))?
		~mx', $s, $matches, PREG_SET_ORDER);

		for ($i = 0; $i < count($matches); $i++) {
			$author = $matches[$i][1];
			$files = (int) $matches[$i][3];
			$insertions = (int) ($matches[$i][4] ?? 0);
			$deletions = (int) ($matches[$i][5] ?? 0);

			$stats[$author]['commits'] = ($stats[$author]['commits'] ?? 0) + 1;
			$stats[$author]['files'] = ($stats[$author]['files'] ?? 0) + $files;
			$stats[$author]['insertions'] = ($stats[$author]['insertions'] ?? 0) + $insertions;
			$stats[$author]['deletions'] = ($stats[$author]['deletions'] ?? 0) + $deletions;
			$stats[$author]['diff'] = ($stats[$author]['diff'] ?? 0) + $insertions - $deletions;
		}
	}

}
