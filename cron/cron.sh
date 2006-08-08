#!/bin/sh

export LC_ALL=C

# file that contains the PHP version tags
filename=tags.inc

# the maximum number of elements for a single php version
php_max_version_elements=4

WORKDIR=`dirname "$0"`
echo "$WORKDIR" | grep -q '^/' || WORKDIR="`pwd`/$WORKDIR"  # get absolute path

. ${WORKDIR}/config.sh

# set up a one dimensional array to store all php version information
declare -a php_version_array
php_version_array=( `cat "$filename"` )

# Calculate how many elements there are in a php version array
php_version_totalcount=${#php_version_array[@]}

# loop through each PHP version and perform the required builds
for (( i = 0 ; i < ${php_version_totalcount} ; i += ${php_max_version_elements} ))
do
	#set up the php version information
	echo ${php_version_array[i]}
	PHPVERSION=${php_version_array[i]}
	echo ${php_version_array[i+1]}
	PHPSRC=${php_version_array[i+1]}
        echo ${php_version_array[i+2]}
	TMPDIR=${php_version_array[i+2]}
        echo ${php_version_array[i+3]}
	OUTDIR=${php_version_array[i+3]}

	# run the loop for each php version
	cd ${PHPSRC}
	./cvsclean
	cvs -q up
	./buildconf
	./config.nice

	#test for success of the make operation
	if ( make > /dev/null 2> ${TMPDIR}/php_build.log ); then
		MAKESTATUS=pass

		export TEST_PHP_ARGS="-m -U -n -q -s ${TMPDIR}/php_test.log"

		make lcov
		mv lcov_html ${OUTDIR}
		php ${WORKDIR}/valgrind.php ${TMPDIR} ${OUTDIR} ${PHPSRC}

		echo "make successful"
	else
		MAKESTATUS=fail
		echo "make failed"
	fi

	php ${WORKDIR}/cron.php ${TMPDIR} ${OUTDIR} ${PHPSRC} ${MAKESTATUS} ${PHPVERSION}
done
