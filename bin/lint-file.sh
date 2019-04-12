#!/usr/bin/env sh

# give you the full directory name of the script no matter where it is being called from
# stolen from https://stackoverflow.com/a/246128
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

echo "$1"
cat "$1" | "$DIR/../vendor/bin/lint-query"