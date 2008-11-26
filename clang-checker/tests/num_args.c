// RUN: clang -checker-simple -verify %s
// XFAIL: ZEND_NUM_ARGS not checked yet

int zend_parse_parameters(int num_args, const char *type_spec, ...);
#define ZEND_NUM_ARGS() ht

void bar(char c);

void foo(int ht)
{
	char *str;
	int l;

	zend_parse_parameters(ZEND_NUM_ARGS(), "|s", &str, &l);

	if (ZEND_NUM_ARGS() > 0) {
		bar(*str); // no-warning
	}

	bar(*str); // expected-warning {{Dereference of undefined value}}
}
