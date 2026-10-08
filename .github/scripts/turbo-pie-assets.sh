#!/usr/bin/env bash
# Packages the self-contained turbo binaries of a phar.yml run (the `pie`
# variant of the turbo-compile jobs) as PIE release assets for the
# phpstan/turbo-ext release of <version>.
#
# PIE installs a pre-packaged binary by copying one file into the extension
# directory — phpstan_turbo.so on Unix (UnixBuild::prePackagedBinary,
# UnixInstall), the DLL named like the asset on Windows, whose other DLLs it
# puts next to php.exe instead (WindowsInstall) — so the thin extension the
# phpstan/phpstan dist ships, which loads the platform's core from its own
# directory, cannot be one; these are built self-contained.
#
# An artifact phpstan_turbo_pie-<target>-php<minor>[-zts] carries
# phpstan_turbo.so, or on Windows php_phpstan_turbo.dll and a `compiler`
# file naming the Visual Studio generation (vs16/vs17/vs18) the official PHP
# of that minor is built with — PIE matches it against the target PHP's.
# Asset names follow PIE's PrePackagedBinaryAssetName and
# WindowsExtensionAssetName (matched lowercase):
#   php_phpstan_turbo-<version>_php<minor>-<arch>-<linux|darwin>-<glibc|musl|bsdlibc>[-zts].zip
#   php_phpstan_turbo-<version>-<minor>-<ts|nts>-<vs>-x86_64.zip (with the same-named .dll)
#
# Usage: turbo-pie-assets.sh <artifacts directory> <version> <output directory>
set -euo pipefail

artifacts=$1
version=$2
out=$3

mkdir -p "$out"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
status=0
packaged=0
found=0

for dir in "$artifacts"/phpstan_turbo_pie-*; do
	[ -d "$dir" ] || continue
	found=$((found + 1))
	name="${dir#"$artifacts"/phpstan_turbo_pie-}"
	target="${name%-php*}"
	ver="${name##*-php}"
	minor="${ver%-zts}"
	zts=""
	if [ "$ver" != "$minor" ]; then
		zts="-zts"
	fi

	case "$target" in
		linux-gnu-* | linux-musl-* | macos-arm64)
			case "$target" in
				linux-gnu-*) os=linux; libc=glibc; arch="${target#linux-gnu-}" ;;
				linux-musl-*) os=linux; libc=musl; arch="${target#linux-musl-}" ;;
				*) os=darwin; libc=bsdlibc; arch=arm64 ;;
			esac
			if [ ! -f "$dir/phpstan_turbo.so" ]; then
				echo "::error::$dir carries no phpstan_turbo.so" >&2
				status=1
				continue
			fi
			asset="php_phpstan_turbo-${version}_php${minor}-${arch}-${os}-${libc}${zts}"
			rm -rf "${work:?}"/*
			cp "$dir/phpstan_turbo.so" "$work/phpstan_turbo.so"
			(cd "$work" && zip -q -X "$asset.zip" phpstan_turbo.so)
			;;
		windows-x86_64)
			if [ ! -f "$dir/php_phpstan_turbo.dll" ] || [ ! -f "$dir/compiler" ]; then
				echo "::error::$dir carries no php_phpstan_turbo.dll and compiler file" >&2
				status=1
				continue
			fi
			vs="$(tr -d '[:space:]' < "$dir/compiler")"
			case "$vs" in
				vs16 | vs17 | vs18) ;;
				*)
					echo "::error::$dir names an unknown compiler: $vs" >&2
					status=1
					continue
					;;
			esac
			ts=nts
			if [ -n "$zts" ]; then
				ts=ts
			fi
			asset="php_phpstan_turbo-${version}-${minor}-${ts}-${vs}-x86_64"
			rm -rf "${work:?}"/*
			cp "$dir/php_phpstan_turbo.dll" "$work/$asset.dll"
			(cd "$work" && zip -q -X "$asset.zip" "$asset.dll")
			;;
		*)
			echo "::error::$dir is for an unknown target: $target" >&2
			status=1
			continue
			;;
	esac

	if [ -e "$out/$asset.zip" ]; then
		echo "::error::two artifacts map onto $asset.zip" >&2
		status=1
		continue
	fi
	mv "$work/$asset.zip" "$out/$asset.zip"
	echo "$name -> $asset.zip"
	packaged=$((packaged + 1))
done

if [ "$found" -eq 0 ]; then
	echo "::error::no phpstan_turbo_pie-* artifacts found in $artifacts" >&2
	status=1
fi
if [ "$packaged" -ne "$found" ]; then
	echo "::error::packaged $packaged assets from $found artifacts" >&2
	status=1
fi

exit "$status"
