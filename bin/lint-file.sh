#!/usr/bin/env sh

## wrapper bash file which allows to pass a filepath to lint-query

echo "$1"

cat "$1" | vendor/bin/lint-query
