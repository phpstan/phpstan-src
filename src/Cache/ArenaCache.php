<?php declare(strict_types = 1);

namespace PHPStan\Cache;

/**
 * Cross-process sharing seam for a single analysis run.
 *
 * This PHP implementation shares nothing: create() never yields an arena,
 * lookups always miss and publishes are dropped — callers fall through to
 * computing values locally, which is exactly PHPStan's behavior without the
 * turbo extension. The extension shadows this class with a shared-memory
 * arena: the master process creates it under a per-run name before spawning
 * parallel workers, every worker maps it, and whichever process computes a
 * record first publishes it for the others. The arena lives exactly as long
 * as the run — the master unlinks the name once all workers have attached
 * (the kernel then reclaims the memory when the last process exits, even
 * after SIGKILL) and destroys it when the analysis ends.
 *
 * Records hold data-only value trees (null, bool, int, float, string,
 * array). Values that are not representable (objects, resources) are simply
 * not published. A stored null is indistinguishable from an absent entry, so
 * callers must only store non-null values. hasRecord() distinguishes "the
 * record was never published" (compute locally, then publish) from "the
 * record exists and is authoritative" (a null entry lookup means absent).
 *
 * @internal
 */
final class ArenaCache
{

	public static function create(string $runId): ?string
	{
		return null;
	}

	public static function attach(string $name): bool
	{
		return false;
	}

	public static function unlinkName(): void
	{
	}

	public static function destroy(): void
	{
	}

	public static function hasRecord(string $key): bool
	{
		return false;
	}

	public static function lookup(string $key): mixed
	{
		return null;
	}

	/**
	 * @param mixed $value
	 */
	public static function publish(string $key, $value): void
	{
	}

	public static function lookupHash(string $recordKey, string $entryKey): mixed
	{
		return null;
	}

	/**
	 * @param mixed[] $entries
	 */
	public static function publishHash(string $recordKey, array $entries): void
	{
	}

}
