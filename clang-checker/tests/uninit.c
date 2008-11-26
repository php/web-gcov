// RUN: clang -checker-simple -verify %s

int zend_parse_parameters(int num_args, const char *type_spec, ...);
#define SUCCESS 0

void bar(int x);

void foo()
{
	long a, b;

	if (zend_parse_parameters(1, "l|l", &a, &b) != SUCCESS) {
		return;
	}

	bar(a); // no-warning
	bar(b); // expected-warning {{Pass-by-value argument in function is undefined}}
}
