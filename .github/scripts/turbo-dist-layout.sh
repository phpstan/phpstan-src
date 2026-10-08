#!/usr/bin/env bash
# Maps the turbo compile artifacts of a phar.yml run onto the layout
# phpstan/phpstan ships (turbo-ext/<target>/...), printing one
# "<artifact file><TAB><path under turbo-ext/>" line per distributed file.
#
# An artifact phpstan_turbo-<target>-php<minor>[-zts] carries the extension
# of that PHP version (phpstan_turbo.so, php_phpstan_turbo.dll) and the
# platform's shared core next to it (phpstan_turbo_core*.so/.dll), which the
# extension loads from its own directory:
#   extension -> <target>/phpstan_turbo-<minor>[-zts].<so|dll>
#   core      -> <target>/<its own name>
# Every leg of a platform links against the one core turbo-compile-core
# built for it, so each of its artifacts carries the same core: it is listed
# once, and different bytes under one name fail the mapping — such a set
# would ship extensions next to a core they were not linked against.
#
# Usage: turbo-dist-layout.sh <artifacts directory>
set -euo pipefail

artifacts=$1
declare -A core_source=()
status=0
mapped=0

for dir in "$artifacts"/phpstan_turbo-*; do
	[ -d "$dir" ] || continue
	name="${dir#"$artifacts"/phpstan_turbo-}"
	target="${name%-php*}"
	minor="${name##*-php}"
	for file in "$dir"/*; do
		[ -f "$file" ] || continue
		base="${file##*/}"
		case "$base" in
			phpstan_turbo_core*)
				dest="$target/$base"
				if [ -n "${core_source[$dest]:-}" ]; then
					if ! cmp -s "${core_source[$dest]}" "$file"; then
						echo "::error::$file and ${core_source[$dest]} carry different cores for $dest" >&2
						status=1
					fi
					continue
				fi
				core_source[$dest]="$file"
				;;
			*)
				dest="$target/phpstan_turbo-$minor.${base##*.}"
				;;
		esac
		printf '%s\t%s\n' "$file" "$dest"
		mapped=$((mapped + 1))
	done
done

if [ "$mapped" -eq 0 ]; then
	echo "::error::no phpstan_turbo-* artifacts found in $artifacts" >&2
	status=1
fi

exit "$status"
