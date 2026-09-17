<?php declare(strict_types = 1);

// GIT_SEQUENCE_EDITOR for a non-interactive `git rebase -i`: turns the pick
// lines of the commits listed in REBASE_DROP / REBASE_EDIT (space-separated
// SHAs, full or abbreviated) into drop / edit. Exits non-zero - which aborts
// the rebase before anything is replayed - when a listed SHA has no pick line.
//
// GIT_SEQUENCE_EDITOR="php rebase-todo.php" REBASE_DROP="..." REBASE_EDIT="..." git rebase -i --onto <new-base> <old-base> <branch>

$todoFile = $argv[1];
$actions = [];
foreach (['drop' => 'REBASE_DROP', 'edit' => 'REBASE_EDIT'] as $action => $variable) {
	foreach (preg_split('~\s+~', trim((string) getenv($variable)), -1, PREG_SPLIT_NO_EMPTY) as $sha) {
		if (isset($actions[$sha])) {
			fwrite(STDERR, "$sha is listed for both drop and edit\n");
			exit(1);
		}
		$actions[$sha] = $action;
	}
}

$lines = file($todoFile);
$matched = [];
foreach ($lines as $i => $line) {
	if (preg_match('~^(?:pick|p) ([0-9a-f]+)( .*)?$~s', $line, $m) !== 1) {
		continue;
	}
	foreach ($actions as $sha => $action) {
		if (str_starts_with($sha, $m[1]) || str_starts_with($m[1], $sha)) {
			$lines[$i] = $action . ' ' . $m[1] . ($m[2] ?? "\n");
			$matched[$sha] = true;
		}
	}
}

$missing = array_diff_key($actions, $matched);
if ($missing !== []) {
	fwrite(STDERR, 'No pick line in the todo for: ' . implode(' ', array_keys($missing)) . "\n");
	exit(1);
}

file_put_contents($todoFile, implode('', $lines));
