#!/bin/sh
# Put gdmedia/ss-auto-git-ignore 1.0.2 into Composer's download cache before `composer install`.
#
# Why: composer.lock (unchanged from production) pins this package, required by
# leochenftw/ss-arsenal, but its upstream repo (guru-digital/SSAutoGitIgnore) was
# deleted, so Composer can no longer download it. Production has it in vendor/
# already. Seeding the cache keeps composer.json and composer.lock identical to
# production (stage 1 is a 1:1 copy).
#
# The zip is the original 0fd761b archive, recovered from Software Heritage and
# checked byte-for-byte against the archived directory hash. BSD-2 licence.
# The cache file name is sha1 of the dist URL in composer.lock.
set -e
here=$(cd "$(dirname "$0")" && pwd)
cache=$(composer config --global cache-files-dir 2>/dev/null || echo "$HOME/.composer/cache/files")
dir="$cache/gdmedia/ss-auto-git-ignore"
mkdir -p "$dir"
cp "$here/gdmedia-ss-auto-git-ignore-1.0.2.zip" "$dir/1150f2c564d62767ca4ea679c0a443c4a995a3ec.zip"
echo "seeded gdmedia/ss-auto-git-ignore 1.0.2 into $dir"
