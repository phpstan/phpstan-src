#!/usr/bin/env bash
# For an unreleased PHP minor, setup-php installs a nightly build of its
# development branch on Windows, which has no devel pack to compile an
# extension against. The Windows legs of phar.yml then replace that runtime
# with the official prerelease (install-php-windows-prerelease.sh) and build
# against its devel pack (download-php-windows-devel.sh).
#
# For each minor in MINORS that windows.php.net has no stable build of, this
# writes the current prerelease from its QA manifest to the `prereleases`
# step output:
#   {"<minor>": {"version": "8.6.0RC3",
#                "nts": {"zip": {"path", "sha256"}, "devel": {"path", "sha256"}},
#                "ts": {...}}}
# A minor with a stable build is left out: setup-php installs that release,
# whose devel pack is published next to it. It runs once per workflow run,
# so every job installs the same prerelease even when a new one comes out
# in the meantime.
#
# Usage: MINORS="8.3 8.4 8.5 8.6" find-php-windows-prereleases.sh
set -euo pipefail

: "${MINORS:?set MINORS to the PHP minors the Windows legs build for}"
fetch() { curl -fsSL --retry 3 --retry-all-errors "https://downloads.php.net/~windows/$1/releases.json"; }
stable=$(fetch releases)
qa=$(fetch qa)

prereleases=$(jq -cn --argjson stable "$stable" --argjson qa "$qa" --arg minors "$MINORS" '
	def files($builds; $ts):
		$builds | to_entries
		| map(select(.key | test("^" + $ts + "-vs[0-9]+-x64$")))
		| if length == 1 then .[0].value | {zip: (.zip | {path, sha256}), devel: (.devel_pack | {path, sha256})}
		  else error("expected one \($ts) x64 build, found \(length)") end;
	reduce ($minors | split(" ") | .[] | select(. != "")) as $minor ({};
		if $stable | has($minor) then .
		elif $qa | has($minor) | not then error("windows.php.net has neither a stable nor a QA build of PHP \($minor)")
		else .[$minor] = {version: $qa[$minor].version, nts: files($qa[$minor]; "nts"), ts: files($qa[$minor]; "ts")}
		end)')

jq -r 'if length == 0 then "every minor has a stable build" else to_entries[] | "PHP \(.key): prerelease \(.value.version)" end' <<< "$prereleases"
echo "prereleases=$prereleases" >> "${GITHUB_OUTPUT:-/dev/stdout}"
