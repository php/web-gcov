#!/bin/sh

LC_ALL=C
export LC_ALL

VALGRIND=valgrind

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
	# cvs -q up
	./buildconf
	./config.nice

	#test for success of the make operation
	#if ( make > ${OUTDIR}/make_log.inc 2> ${TMPDIR}/php_build.log ); then
	if ( make > /dev/null 2> ${TMPDIR}/php_build.log ); then
		#echo pass > ${OUTDIR}/last_make_status.inc

		MAKESTATUS=pass

		export NO_INTERACTION=1
		export TEST_PHP_VALGRIND=${VALGRIND}
		make test > ${TMPDIR}/php_test_valgrind.log
		php ${WORKDIR}/valgrind.php ${TMPDIR} ${OUTDIR} ${PHPSRC}

		#rm .php_cov_info.ltpdata
		#rm -r "*.gcov"
		make lcov --warn-undefined-variables
		make lcov-html --warn-undefined-variables
		rm -fr ${OUTDIR}/lcov_html
		mv lcov_html ${OUTDIR}
	
		echo make successful
	else
		#if make failed

		#echo fail > ${OUTDIR}/last_make_status.inc
		MAKESTATUS=fail
		echo make failed
	fi	
	php ${WORKDIR}/cron.php ${TMPDIR} ${OUTDIR} ${PHPSRC} ${MAKESTATUS}
done
