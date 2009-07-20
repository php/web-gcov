#!/bin/sh

# where the files (sources, etc..) will be stored
PHPROOT=/tmp/gcov

# the output dir (aka the place of the php-gcov-web checkout)
OUTROOT=/www/gcov

# set this to null if you don't want to run valgrind tests
VALGRIND=1

# use this to set specific valgrind options, like a suppressions file
#export VALGRIND_OPTS="--suppressions=/tmp/gcov/valgrind.supp"
