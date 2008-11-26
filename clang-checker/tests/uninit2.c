// RUN: clang -checker-simple -verify %s
// XFAIL: return value not checked yet

int zend_parse_parameters(int num_args, const char *type_spec, ...);
#define SUCCESS 0

void bar(int x);

void foo2()
{
	long a, b;

	zend_parse_parameters(1, "l|l", &a, &b);

	bar(a); // expected-warning {{Pass-by-value argument in function is undefined}}
	bar(b); // expected-warning {{Pass-by-value argument in function is undefined}}
}
