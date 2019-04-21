#!/bin/sh

#  +----------------------------------------------------------------------+
#  | PHP QA GCOV Website                                                  |
#  +----------------------------------------------------------------------+
#  | Copyright (c) The PHP Group                                          |
#  +----------------------------------------------------------------------+
#  | This source file is subject to version 3.01 of the PHP license,      |
#  | that is bundled with this package in the file LICENSE, and is        |
#  | available through the world-wide-web at the following url:           |
#  | http://www.php.net/license/3_01.txt                                  |
#  | If you did not receive a copy of the PHP license and are unable to   |
#  | obtain it through the world-wide-web, please send a note to          |
#  | license@php.net so we can mail you a copy immediately.               |
#  +----------------------------------------------------------------------+
#  | Author: Daniel Pronych <pronych@php.net>                             |
#  |         Nuno Lopes <nlopess@php.net>                                 |
#  +----------------------------------------------------------------------+

source ./config.sh
export LC_ALL=C
export CCACHE_DISABLE=1
export REPORT_EXIT_STATUS=0

# the file with the pid of this process
GLOBALPIDFILE="${PHPROOT}/build.pid"

# check if we are alone. if not, quit.
if [ -f ${GLOBALPIDFILE} ]; then
	if ( ps -p `cat "$GLOBALPIDFILE"` > /dev/null ); then
		echo -n "Process already running with PID: "
		cat ${GLOBALPIDFILE}
		exit 1
	fi
fi

echo $$ > ${GLOBALPIDFILE}

# file that contains the PHP version tags
FILENAME=tags.inc

WORKDIR=`dirname "$0"`
echo "$WORKDIR" | grep -q '^/' || WORKDIR="`pwd`/$WORKDIR"  # get absolute path

# make genhtml use our header/footer
export LTP_GENHTML="genhtml --html-prolog ${WORKDIR}/lcov_prolog.inc --html-epilog ${WORKDIR}/lcov_epilog.inc --html-extension php"

# set up a one dimensional array to store all php version information
declare -a TAGS_ARRAY
TAGS_ARRAY=( `cat "$FILENAME"` )

# Calculate how many elements there are in a php version array
TAGS_COUNT=${#TAGS_ARRAY[@]}

BUILT_SOME=0

# Check for a build version passed to the script
if [ $# -eq 1 ]; then
	BUILD=$1
else
	BUILD="_all_"
fi

# loop through each PHP version and perform the required builds
for (( i = 0 ; i < $TAGS_COUNT ; i += 1 ))
do
	PHPTAG=${TAGS_ARRAY[i]}

	# Build all has no exceptions
	if [ $BUILD = "_all_" ]; then
		BUILD_VERSION=1
	else
		if [ $BUILD = $PHPTAG ]; then
			BUILD_VERSION=1
		else
			BUILD_VERSION=0
		fi
	fi

	# If this version should be built
	if [ $BUILD_VERSION = 1 ]; then

		BUILT_SOME=1
		BEGIN=`date +%s`

		OUTDIR=${OUTROOT}/${PHPTAG}
		PHPSRC=${PHPROOT}/${PHPTAG}
		TMPDIR=${PHPROOT}/tmp/${PHPTAG}
		PIDFILE=${OUTDIR}/build.pid

		if [ "${PHPTAG}" = "PHP_HEAD" ]; then
			GITBRANCH="master"
		else
			GITBRANCH=`echo "$PHPTAG" | sed 's/_\([0-9]*\)/-\1/' | sed 's/_/./g'`
		fi

		mkdir -p $OUTDIR
		mkdir -p $TMPDIR

		echo $$ > ${PIDFILE}

		cd ${PHPROOT}
		if [ -d ${PHPTAG} ]; then
			cd ${PHPTAG}
			git reset --hard
			git pull
		else
			git clone http://git.php.net/repository/php-src.git -b $GITBRANCH $PHPTAG
			cd ${PHPTAG}
		fi
		git clean -xfd > /dev/null
		cp "../config.$PHPTAG" config.nice

		./buildconf --force > /dev/null

		if [ -x ./config.nice ]; then
			./config.nice > /dev/null
		else
			# try to run with the default options
			./configure > /dev/null
		fi

		if ( make ${MAKEOPTS} > /dev/null 2> ${TMPDIR}/php_build.log ); then

			MAKESTATUS=pass

			TEST_PHP_ARGS="-q --keep-all"

			# only run valgrind testing if it is available
			if (valgrind --version >/dev/null 2>&1 && test "$VALGRIND" ); then
				TEST_PHP_ARGS="${TEST_PHP_ARGS} -m"
			fi

			export TEST_PHP_ARGS

			# test for lcov support
			if ( grep -q lcov Makefile ); then
				echo "Doing lcov build" > ${TMPDIR}/php_test.log
				make lcov >> ${TMPDIR}/php_test.log
				if [ -d lcov_html ]; then
					rm -fr ${OUTDIR}/lcov_html
					mv lcov_html ${OUTDIR}
				fi
			else
				echo "Doing non-lcov build" > ${TMPDIR}/php_test.log
				make test >> ${TMPDIR}/php_test.log
			fi

			echo "make successful: ${PHPTAG}"
		else
			MAKESTATUS=fail
			echo "make failed"
		fi # End build failure or success

		BUILD_TIME=$[`date +%s` - ${BEGIN}]

		php ${WORKDIR}/cron.php ${TMPDIR} ${OUTDIR} ${PHPSRC} ${MAKESTATUS} ${PHPTAG} ${BUILD_TIME} &

		# run find_tested script from source dir to give relative paths in output
		cd ${PHPSRC}
		php ${WORKDIR}/find_tested.php . > "${TMPDIR}/tested_functions.inc"
		mv "${TMPDIR}/tested_functions.inc" ${OUTDIR}/tested_functions.inc

		wait
		rm -f "$PIDFILE"

	fi # End verify build PHP version
done


rm -f "$GLOBALPIDFILE"


# display an error if the tag doesn't exist
if [ $BUILT_SOME = 0 ]; then
	echo "Invalid tag specified: '$BUILD'"
	echo
	exit 1
fi
