#!/bin/sh

export LC_ALL=C

# file that contains the PHP version tags
FILENAME=tags.inc

WORKDIR=`dirname "$0"`
echo "$WORKDIR" | grep -q '^/' || WORKDIR="`pwd`/$WORKDIR"  # get absolute path

# set up a one dimensional array to store all php version information
declare -a TAGS_ARRAY
TAGS_ARRAY=( `cat "$FILENAME"` )

# Calculate how many elements there are in a php version array
TAGS_COUNT=${#TAGS_ARRAY[@]}

PHPROOT=${TAGS_ARRAY[0]}
OUTROOT=${TAGS_ARRAY[1]}

# Check for a build version passed to the script
if [ $# -eq 1 ]; then
	BUILD=$1
else
	BUILD="_all_"
fi

# loop through each PHP version and perform the required builds
for (( i = 2 ; i < $TAGS_COUNT ; i += 1 ))
do
	PHPTAG=${TAGS_ARRAY[i]}

	# Build all has no exceptions
	if [ $BUILD = "_all_" ]; then
		BUILD_VERSION=1
	else
		if [ $BUILD = $PHPTAG ]; then
			BUILD_VERSION=1
			#todo: if tag does not exist, output error
		else
			BUILD_VERSION=0
		fi
	fi

	# If this version should be built
	if [ $BUILD_VERSION = 1 ]; then

		# todo: make sure all the following directories exist
		OUTDIR=${OUTROOT}/${PHPTAG}
		PHPSRC=${PHPROOT}/${PHPTAG}
		TMPDIR=${PHPROOT}/tmp/${PHPTAG}

		cd ${PHPSRC}
		./cvsclean
		cvs -q up
		./buildconf --force
		./config.nice

		if ( make > /dev/null 2> ${TMPDIR}/php_build.log ); then

			#test for success of the make operation
			MAKESTATUS=pass

			export TEST_PHP_ARGS="-m -U -n -q --keep-all"

			# LCOV operations
			make lcov > ${TMPDIR}/php_test.log
			rm -fr ${OUTDIR}/lcov_html
			mv lcov_html ${OUTDIR}

			echo "make successful"
		else
			MAKESTATUS=fail
			echo "make failed"
		fi # End build failure or success

		php ${WORKDIR}/cron.php ${TMPDIR} ${OUTDIR} ${PHPSRC} ${MAKESTATUS} ${PHPTAG}

	fi # End verify build PHP version
done
