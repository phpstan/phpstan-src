PHP_ARG_ENABLE([forkunsafe], [whether to enable forkunsafe], [AS_HELP_STRING([--enable-forkunsafe], [Enable forkunsafe])], [yes])
if test "$PHP_FORKUNSAFE" != "no"; then
  PHP_NEW_EXTENSION([forkunsafe], [forkunsafe.c], [$ext_shared])
fi
