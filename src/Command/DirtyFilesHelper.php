<?php declare(strict_types = 1);

namespace PHPStan\Command;

final class DirtyFilesHelper
{
	private function getGitRepoRoot(): ?string
	{
		exec('git rev-parse --show-toplevel 2>&1', $output, $returnVar);
		if ($returnVar !== 0) {
			return null;
		}

		return trim($output[0]);
	}

	/**
	 * @return string[]
	 */
	public function getGitDirtyFiles(): array
	{
		$repoPath = $this->getGitRepoRoot();
		if ($repoPath === null) {
			return [];
		}

		$cmd = 'cd ' . escapeshellarg($repoPath) . ' && git status --porcelain';

		exec($cmd, $output, $returnVar);
		if ($returnVar !== 0) {
			return [];
		}

		$dirtyFiles = [];

		foreach ($output as $line) {
			$filePath = substr($line, 3);
			$dirtyFiles[] = $filePath;
		}

		return $dirtyFiles;
	}
}
