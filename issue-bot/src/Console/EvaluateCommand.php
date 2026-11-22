<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use Exception;
use Github\Api\Issue as GitHubIssueApi;
use Github\Client;
use PHPStan\IssueBot\Comment\BotComment;
use PHPStan\IssueBot\Comment\BotCommentParser;
use PHPStan\IssueBot\Comment\IssueCommentDownloader;
use PHPStan\IssueBot\Issue\IssueCache;
use PHPStan\IssueBot\Playground\NewlyUnparsableVersionFilter;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\TabCreator;
use PHPStan\IssueBot\PostGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function file_get_contents;
use function implode;
use function in_array;
use function is_file;
use function sprintf;
use function unserialize;

class EvaluateCommand extends Command
{

	private const EXIT_ERROR = 1;
	private const EXIT_AFFECTS_ISSUES = 2;
	private const EXIT_NO_AFFECTED_ISSUES = 0;

	private const BLEEDING_EDGE_URL = 'https://phpstan.org/blog/what-is-bleeding-edge';

	public function __construct(
		private TabCreator $tabCreator,
		private NewlyUnparsableVersionFilter $newlyUnparsableVersionFilter,
		private PostGenerator $postGenerator,
		private Client $githubClient,
		private IssueCommentDownloader $issueCommentDownloader,
		private string $issueCachePath,
		private string $playgroundCachePath,
		private string $tmpDir,
		private string $gitBranch,
		private string $phpstanSrcCommitBefore,
		private string $phpstanSrcCommitAfter,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('evaluate');
		$this->addOption('post-comments', null, InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$issueCache = $this->loadIssueCache();
		$originalResults = $this->loadPlaygroundCache()->getResults();
		[$newResults, $newBleedingEdgeResults] = $this->loadResults();
		$toPost = [];
		$totalCodeSnippets = 0;

		foreach ($issueCache->getIssues() as $issue) {
			$botComments = [];
			$deduplicatedExamples = [];
			foreach ($issue->getComments() as $comment) {
				if ($comment instanceof BotComment) {
					$botComments[] = $comment;
					continue;
				}
				foreach ($comment->getPlaygroundExamples() as $example) {
					if (isset($deduplicatedExamples[$example->getHash()])) {
						$deduplicatedExamples[$example->getHash()]['users'][] = $comment->getAuthor();
						$deduplicatedExamples[$example->getHash()]['users'] = array_values(array_unique($deduplicatedExamples[$example->getHash()]['users']));
						continue;
					}
					$deduplicatedExamples[$example->getHash()] = [
						'example' => $example,
						'users' => [$comment->getAuthor()],
					];
				}
			}

			$totalCodeSnippets += count($deduplicatedExamples);
			foreach ($deduplicatedExamples as ['example' => $example, 'users' => $users]) {
				$hash = $example->getHash();
				if (!array_key_exists($hash, $originalResults)) {
					throw new Exception(sprintf('Hash %s does not exist in original results.', $hash));
				}

				$originalErrors = $originalResults[$hash]->getVersionedErrors();

				if (!array_key_exists($hash, $newResults)) {
					throw new Exception(sprintf('Hash %s does not exist in new results.', $hash));
				}

				$newResult = $this->fillPhp71Results($originalErrors, $newResults[$hash]);
				$candidates = [[false, $newResult]];
				if (array_key_exists($hash, $newBleedingEdgeResults)) {
					$newBleedingEdgeResult = $this->fillPhp71Results($originalErrors, $newBleedingEdgeResults[$hash]);

					// errors only added by bleeding edge come from its new rules, they do not tell whether the issue is fixed
					if ($this->removesErrors($newResult, $newBleedingEdgeResult)) {
						$candidates[] = [true, $newBleedingEdgeResult];
					}
				}

				foreach ($candidates as [$bleedingEdge, $candidateResult]) {
					$candidateBotComments = $this->filterBotComments($botComments, $bleedingEdge);
					$originalTabs = $this->tabCreator->create($originalErrors);
					$newTabs = $this->tabCreator->create($this->filterErrors($originalErrors, $candidateResult));
					$text = $this->postGenerator->createText($hash, $originalTabs, $newTabs, $candidateBotComments);
					if ($text === null) {
						continue;
					}

					if ($this->isOnlyParseErrorChange($hash, $originalErrors, $candidateResult, $candidateBotComments)) {
						continue;
					}

					if ($this->isIssueClosed($issue->getNumber())) {
						continue;
					}

					$freshBotComments = $this->filterBotComments($this->getFreshBotComments($issue->getNumber()), $bleedingEdge);
					$textAgain = $this->postGenerator->createText($hash, $originalTabs, $newTabs, $freshBotComments);
					if ($textAgain === null) {
						continue;
					}

					$toPost[] = [
						'issue' => $issue->getNumber(),
						'hash' => $hash,
						'users' => $users,
						'diff' => $text['diff'],
						'details' => $text['details'],
						'bleedingEdge' => $bleedingEdge,
					];
				}
			}
		}

		$exitCode = self::EXIT_AFFECTS_ISSUES;
		if (count($toPost) === 0) {
			$exitCode = self::EXIT_NO_AFFECTED_ISSUES;
			$output->writeln(sprintf('No changes in results in %d code snippets from %d GitHub issues. :tada:', $totalCodeSnippets, count($issueCache->getIssues())));
		}

		foreach ($toPost as ['issue' => $issue, 'hash' => $hash, 'users' => $users, 'diff' => $diff, 'details' => $details, 'bleedingEdge' => $bleedingEdge]) {
			$text = sprintf(
				"Result of the [code snippet](https://phpstan.org/r/%s) from %s in [#%d](https://github.com/phpstan/phpstan/issues/%d) changed%s:\n\n```%s\n%s```",
				$hash,
				implode(' ', array_map(static fn (string $user): string => sprintf('@%s', $user), $users)),
				$issue,
				$issue,
				$bleedingEdge ? sprintf(' with [bleeding edge](%s) enabled', self::BLEEDING_EDGE_URL) : '',
				$this->getDiffInfoString($bleedingEdge),
				$diff,
			);
			if ($details !== null) {
				$text .= "\n\n" . sprintf('<details>
 <summary>Full report</summary>

%s
</details>', $details);
			}

			$text .= "\n\n---\n";

			$output->writeln($text);
		}

		$postComments = (bool) $input->getOption('post-comments');
		if ($postComments) {
			if (count($toPost) > 20) {
				$output->writeln('Too many comments to post, something is probably wrong.');
				return self::EXIT_ERROR;
			}
			foreach ($toPost as ['issue' => $issue, 'hash' => $hash, 'users' => $users, 'diff' => $diff, 'details' => $details, 'bleedingEdge' => $bleedingEdge]) {
				$text = sprintf(
					"%s After [the latest push in %s](https://github.com/phpstan/phpstan-src/compare/%s...%s), PHPStan%s now reports different result with your [code snippet](https://phpstan.org/r/%s):\n\n```%s\n%s```",
					implode(' ', array_map(static fn (string $user): string => sprintf('@%s', $user), $users)),
					$this->gitBranch,
					$this->phpstanSrcCommitBefore,
					$this->phpstanSrcCommitAfter,
					$bleedingEdge ? sprintf(' with [bleeding edge](%s) enabled', self::BLEEDING_EDGE_URL) : '',
					$hash,
					$this->getDiffInfoString($bleedingEdge),
					$diff,
				);
				if ($details !== null) {
					$text .= "\n\n" . sprintf('<details>
 <summary>Full report</summary>

%s
</details>', $details);
				}

				/** @var GitHubIssueApi $issueApi */
				$issueApi = $this->githubClient->api('issue');
				$issueApi->comments()->create('phpstan', 'phpstan', $issue, [
					'body' => $text,
				]);
			}
		}

		return $exitCode;
	}

	private function getDiffInfoString(bool $bleedingEdge): string
	{
		if ($bleedingEdge) {
			return 'diff ' . BotCommentParser::BLEEDING_EDGE_INFO_WORD;
		}

		return 'diff';
	}

	/**
	 * Comments about the regular result and about the result with bleeding edge enabled are deduplicated separately.
	 *
	 * @param BotComment[] $botComments
	 * @return list<BotComment>
	 */
	private function filterBotComments(array $botComments, bool $bleedingEdge): array
	{
		return array_values(array_filter($botComments, static fn (BotComment $comment): bool => $comment->isBleedingEdge() === $bleedingEdge));
	}

	/**
	 * Playground snippets analysed on PHP 7.1 are compared with the PHP 7.2 result.
	 *
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newResult
	 * @return array<int, list<PlaygroundError>>
	 */
	private function fillPhp71Results(array $originalErrors, array $newResult): array
	{
		if (array_key_exists(70100, $originalErrors) || array_keys($originalErrors) === [70400]) {
			$newResult[70100] = $newResult[70200];
		}

		return $newResult;
	}

	/**
	 * Whether some error reported in $errors is not reported in $bleedingEdgeErrors on the same PHP version.
	 *
	 * @param array<int, list<PlaygroundError>> $errors
	 * @param array<int, list<PlaygroundError>> $bleedingEdgeErrors
	 */
	private function removesErrors(array $errors, array $bleedingEdgeErrors): bool
	{
		$describe = static fn (PlaygroundError $error): string => sprintf('%d: %s', $error->getLine(), $error->getMessage());
		foreach ($errors as $phpVersion => $versionErrors) {
			$remaining = array_map($describe, $bleedingEdgeErrors[$phpVersion] ?? []);
			foreach (array_map($describe, $versionErrors) as $error) {
				if (!in_array($error, $remaining, true)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Decides whether the detected change disappears once PHP versions on
	 * which the snippet does not parse are ignored. The posted comment is
	 * always generated from the unfiltered results, so diffs keep matching
	 * previously posted bot comments - the filtered results are only used
	 * to decide whether to post at all.
	 *
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newErrors
	 * @param BotComment[] $botComments
	 */
	private function isOnlyParseErrorChange(string $hash, array $originalErrors, array $newErrors, array $botComments): bool
	{
		[$filteredOriginalErrors, $filteredNewErrors] = $this->newlyUnparsableVersionFilter->filter($originalErrors, $newErrors);
		if (count($filteredOriginalErrors) === 0) {
			// every version the original snapshot covered no longer parses
			return true;
		}

		$originalTabs = $this->tabCreator->create($filteredOriginalErrors);
		$newTabs = $this->tabCreator->create($this->filterErrors($filteredOriginalErrors, $filteredNewErrors));

		return $this->postGenerator->createText($hash, $originalTabs, $newTabs, $botComments) === null;
	}

	/**
	 * @return list<BotComment>
	 */
	private function getFreshBotComments(int $issueNumber): array
	{
		$comments = [];
		foreach ($this->issueCommentDownloader->getComments($issueNumber) as $issueComment) {
			if (!$issueComment instanceof BotComment) {
				continue;
			}

			$comments[] = $issueComment;
		}

		return $comments;
	}

	private function isIssueClosed(int $issueNumber): bool
	{
		/** @var GitHubIssueApi $issueApi */
		$issueApi = $this->githubClient->api('issue');
		$issue = $issueApi->show('phpstan', 'phpstan', $issueNumber);

		return $issue['state'] === 'closed';
	}

	private function loadIssueCache(): IssueCache
	{
		if (!is_file($this->issueCachePath)) {
			throw new Exception('Issue cache must exist');
		}

		$contents = file_get_contents($this->issueCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

	private function loadPlaygroundCache(): PlaygroundCache
	{
		if (!is_file($this->playgroundCachePath)) {
			throw new Exception('Playground cache must exist');
		}

		$contents = file_get_contents($this->playgroundCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

	/**
	 * @return array{array<string, array<int, list<PlaygroundError>>>, array<string, array<int, list<PlaygroundError>>>} regular results and additional results with bleeding edge enabled
	 */
	private function loadResults(): array
	{
		$finder = new Finder();
		$tmpResults = [];
		$tmpBleedingEdgeResults = [];
		foreach ($finder->files()->name('results-*.tmp')->in($this->tmpDir) as $resultFile) {
			$contents = file_get_contents($resultFile->getPathname());
			if ($contents === false) {
				throw new Exception('Result read unsuccessful');
			}
			$result = unserialize($contents);
			$phpVersion = (int) $result['phpVersion'];
			foreach ($result['errors'] as $hash => $errors) {
				$tmpResults[(string) $hash][$phpVersion] = array_values($errors);
			}
			foreach ($result['bleedingEdgeErrors'] ?? [] as $hash => $errors) {
				$tmpBleedingEdgeResults[(string) $hash][$phpVersion] = array_values($errors);
			}
		}

		return [$tmpResults, $tmpBleedingEdgeResults];
	}

	/**
	 * @param array<int, list<PlaygroundError>> $originalErrors
	 * @param array<int, list<PlaygroundError>> $newErrors
	 * @return array<int, list<PlaygroundError>>
	 */
	private function filterErrors(
		array $originalErrors,
		array $newErrors,
	): array
	{
		$originalPhpVersions = array_keys($originalErrors);
		$filteredNewErrors = [];
		foreach ($newErrors as $phpVersion => $errors) {
			if (!in_array($phpVersion, $originalPhpVersions, true)) {
				continue;
			}

			$filteredNewErrors[$phpVersion] = $errors;
		}

		$newTabs = $this->tabCreator->create($newErrors);
		$filteredNewTabs = $this->tabCreator->create($filteredNewErrors);
		if (count($newTabs) !== count($filteredNewTabs)) {
			return $newErrors;
		}

		$firstFilteredNewTab = $filteredNewTabs[0];
		$firstNewTab = $newTabs[0];

		if (count($firstFilteredNewTab->getErrors()) !== count($firstNewTab->getErrors())) {
			return $newErrors;
		}

		foreach ($firstFilteredNewTab->getErrors() as $i => $error) {
			$otherError = $firstNewTab->getErrors()[$i];
			if ($error->getLine() !== $otherError->getLine()) {
				return $newErrors;
			}
			if ($error->getMessage() !== $otherError->getMessage()) {
				return $newErrors;
			}
		}

		return $filteredNewErrors;
	}

}
