// RUN: clang -checker-simple -verify %s
// XFAIL: storage is not verified

int zend_parse_parameters(int num_args, const char *type_spec, ...);

void foo()
{
	char **str = NULL;
	int *len;

	long x;
	long *num;

	num = &x;

	zend_parse_parameters(0, "sl",
		str, // expected-warning {{Pointer in NULL}}
		len, // expected-warning {{Pass-by-value argument in function is undefined}}
		num  // no-warning
	);
}
