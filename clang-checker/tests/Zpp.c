// RUN: clang -fsyntax-only -verify %s

int zend_parse_parameters(int num_args, char *type_spec, ...);
int zend_parse_parameters_ex(int flags, int num_args, char *type_spec, ...);

typedef struct _zval zval;
typedef unsigned char zend_bool;
typedef struct _zend_class_entry zend_class_entry;
typedef struct _zend_fcall_info zend_fcall_info;
typedef struct _zend_fcall_info_cache zend_fcall_info_cache;
typedef struct _HashTable HashTable;

#define FOO

void tests() {
  {
    zval *z;
    zend_bool b;
    zend_class_entry *zce;
    double d;
    zend_fcall_info *zfi;
    zend_fcall_info_cache *zfic;
    HashTable *ht;
    long l;
    char *c;
    int i;
    zval **zp;
    zval ***zpp;
    zend_parse_parameters(13, "abCdfh|loOr/sz!Z+",
        &z, &b, &zce, &d, zfi, zfic, &ht, &l,
        &z, &z, zce, &z, &c, &i, &z, &zp, &zpp, &i);
  }
  {
    zend_parse_parameters(0, "");
  }
  {
    int l;
    char *s;
    int s_len;
    char format[] = "sl";
    zend_parse_parameters(2, format, &s, &s_len, &l); // expected-warning {{format string is not a string literal (potentially insecure)}}
  }
  {
    int l;
    char *s;
    int s_len;
    zend_parse_parameters(2, "sl", &s, &s_len, &l); // expected-warning {{incompatible pointer types passing 'int *', expected 'long *'}}
  }
  {
    long l;
    char *s;
    long s_len;
    zend_parse_parameters(2, "sl", &s, &s_len, &l); // expected-warning {{incompatible pointer types passing 'long *', expected 'int *'}}
  }
  {
    zval *z;
    zend_parse_parameters(13, "Z", &z); // expected-warning {{incompatible pointer types passing 'zval **', expected 'zval ***'}}
  }
  {
    long l;
    zval **z;
    int i;
    zend_parse_parameters(2, "l*", &l, &z, &i); // expected-warning {{incompatible pointer types passing 'zval ***', expected 'zval ****'}}
  }
  {
    long l;
    zend_parse_parameters(1, "l", l); // expected-warning {{incompatible pointer to integer conversion passing 'long', expected 'long *'}}
  }
  {
#undef FOO
#define FOO l
    long l;
    zend_parse_parameters(1, "l", FOO); // expected-warning {{incompatible pointer to integer conversion passing 'long', expected 'long *'}}
  }
  {
    typedef long * foobar_t;
    foobar_t l;
    void * v;
    zend_parse_parameters(2, "ll", l, (foobar_t)v);
  }
  {
#undef FOO
#define FOO "l"
    long l;
    zend_parse_parameters(1, FOO, &l);
  }
  {
    long l;
    char *s;
    int s_len;
    zend_parse_parameters(2, "s\000l", &s, &s_len, &l); // expected-warning {{format string contains '\0' within the string body}}
  }
  {
#undef FOO
#define FOO "l\000l"
    long l;
    zend_parse_parameters(1, FOO, &l, &l); // expected-warning {{format string contains '\0' within the string body}}
  }
  {
    long l = 0;
    char *s = 0;
    int s_len = 0;
    zend_parse_parameters(2, "|l|s", &l, &s, &s_len); // expected-warning {{duplicated specifier '|'}}
  }
  {
    long l;
    zval ***z;
    int i;
    zend_parse_parameters(2, "l+*", &l, &z, &i, &z, &i); // expected-warning {{duplicated specifier '*'}}
  }
  {
    zval *z;
    zend_parse_parameters(2, "z//", &z); // expected-warning {{duplicated specifier '/'}}
  }
  {
    long l = 0;
    char *s = 0;
    int s_len = 0;
    zend_parse_parameters(2, "l|/s", &l, &s, &s_len); // expected-warning {{specifier '/' cannot be applied to 'l'}}
  }
  {
    long l = 0;
    char *s = 0;
    int s_len = 0;
    zend_parse_parameters(2, "l|!s", &l, &s, &s_len); // expected-warning {{specifier '!' cannot be applied to 'l'}}
  }
  {
    long l = 0;
    char *s = 0;
    int s_len = 0;
    zend_parse_parameters(2, "ls", &l, &s); // expected-warning {{more '%' conversions than data arguments}}
  }
  {
    long l = 0;
    zend_parse_parameters(2, "ll", &l); // expected-warning {{more '%' conversions than data arguments}}
  }
  {
    long l = 0;
    zend_parse_parameters(2, "A", &l); // expected-warning {{invalid conversion 'A'}}
  }
  {
    long l = 0;
    zend_parse_parameters(2, "l", &l, &l); // expected-warning {{more data arguments than '%' conversions}}
  }
}
