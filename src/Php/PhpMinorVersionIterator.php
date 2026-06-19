<?php declare(strict_types = 1);

namespace PHPStan\Php;

use IteratorAggregate;
use Override;
use PHPStan\ShouldNotHappenException;
use Traversable;
use function floor;

/**
 * @api
 *
 * @implements IteratorAggregate<PhpVersion>
 */
final class PhpMinorVersionIterator implements IteratorAggregate
{

	private PhpVersion $currentVersion;

	public function __construct(
		PhpVersion $startVersion,
		private PhpVersion $endVersion,
	)
	{
		if ($startVersion->getMajorVersionId() < 5
			|| $startVersion->getMajorVersionId() > 8
		) {
			throw new ShouldNotHappenException();
		}
		if ($endVersion->getMajorVersionId() < 5
			|| $endVersion->getMajorVersionId() > 8
		) {
			throw new ShouldNotHappenException();
		}

		$this->currentVersion = $startVersion;
	}

	#[Override]
	public function getIterator(): Traversable
	{
		yield $this->currentVersion;

		while (true) {
			if (
				$this->currentVersion->getMajorVersionId() === 5
				&& $this->currentVersion->getMinorVersionId() === 6
			) {
				$next = new PhpVersion(70000);
			} elseif (
				$this->currentVersion->getMajorVersionId() === 7
				&& $this->currentVersion->getMinorVersionId() === 4
			) {
				$next = new PhpVersion(80000);
			} else {
				$nextMinorVersionId = $this->currentVersion->getVersionId() + 100;
				$nextWithZeroPatch = (int) floor($nextMinorVersionId / 100) * 100;
				$next = new PhpVersion($nextWithZeroPatch);
			}

			if ($next->getVersionId() > $this->endVersion->getVersionId()) {
				break;
			}

			$this->currentVersion = $next;

			yield $this->currentVersion;
		}

		if ($this->currentVersion->getVersionId() === $this->endVersion->getVersionId()) {
			return;
		}

		yield $this->endVersion;
	}

}
