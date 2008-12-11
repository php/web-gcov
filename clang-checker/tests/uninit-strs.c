// RUN: clang -checker-simple -verify %s
// XFAIL: string not correlated with length

int zend_parse_parameters(int num_args, const char *type_spec, ...);
#define SUCCESS 0

void bar(int x);

void foo()
{
	char *str = NULL;
	int len;

	if (zend_parse_parameters(0, "|s", &str, &len) != SUCCESS) {
		return;
	}

	bar(len); // expected-warning {{Pass-by-value argument in function is undefined}}

	// if str is inited, then len is too
	if (str) {
		bar(len); // no-warning
	}
}
