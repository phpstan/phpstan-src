/*
 * PHPStanTurbo\ExprPrinter — native implementation of
 * PHPStan\Node\Printer\ExprPrinter.
 *
 * Declared as PHPStan\Node\Printer\ExprPrinter itself at activation (final,
 * like the twin), so every expression key printed anywhere in the analyser
 * comes through here.
 *
 * printExpr() is a cache in front of PHPStan\Node\Printer\Printer: a plain
 * Variable prints as '$' . name without touching the printer at all, and
 * every other expression's printed form is remembered on the node itself,
 * under the ATTRIBUTE_CACHE_KEY attribute — the very cache
 * PHPStan\Node\Printer\Printer::p() fills as it descends, so a form printed
 * once is never rebuilt. Only a genuine miss crosses back into PHP, to
 * prettyPrintExpr(); the pretty printer itself (PrettyPrinterAbstract and
 * its Standard subclass) stays PHP.
 */

#include "support.h"
#include "generated/ExprPrinter.h"

namespace slots = ptdecl::ExprPrinter::slot;
namespace sigs = ptdecl::ExprPrinter::sig;
#include "TypeTraits.h"
#include "zv.h"

zend_class_entry *pt_ce_expr_printer = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Node\Printer\ExprPrinter. The printer collaborator lives
 * in the PHP object's printer property. */
class ExprPrinter
{
public:
	explicit ExprPrinter(zval *self) : self(self) {}

	/* printExpr(); owned string, NULL with an exception pending */
	[[nodiscard]] zend_string *printExpr(zend_object *expr) const
	{
		zend_string *variable = variableFastPath(expr);
		if (variable != NULL) return variable;

		zval *cached = pt_node_attribute(expr, pt_str_cache_printer);
		if (cached != NULL && Z_TYPE_P(cached) == IS_STRING) return zend_string_copy(Z_STR_P(cached));

		return printUncached(expr);
	}

	/* the miss half of printExpr(): print through the printer and remember
	 * the result on the node, for a caller that has already taken the two
	 * fast paths above */
	zend_string *printUncached(zend_object *expr) const
	{
		zval *printer = zv::ObjRef(self).propAt(slots::printer).raw();
		ZVAL_DEREF(printer);
		if (UNEXPECTED(Z_TYPE_P(printer) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: ExprPrinter::$printer is not initialized");
			return NULL;
		}

		zend_class_entry *ce = Z_OBJCE_P(printer);
		zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, "prettyprintexpr", sizeof("prettyprintexpr") - 1);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: prettyPrintExpr not found");
			return NULL;
		}

		zval arg, printed;
		ZVAL_OBJ(&arg, expr);
		zend_call_known_function(fn, Z_OBJ_P(printer), ce, &printed, 1, &arg, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&printed);
			return NULL;
		}
		if (UNEXPECTED(Z_TYPE(printed) != IS_STRING)) {
			zval_ptr_dtor(&printed);
			zend_throw_error(NULL, "phpstan_turbo: prettyPrintExpr did not return a string");
			return NULL;
		}

		/* the twin's setAttribute(), unconditionally — Printer::p() skips
		 * the write for a form containing a newline, printExpr() does not */
		pt_node_set_attribute(expr, pt_str_cache_printer, &printed);

		return Z_STR(printed); /* take ownership */
	}

private:
	/* '$' . $expr->name for a Variable with a string name, NULL for anything
	 * else (including a Variable whose name is an Expr, which the twin lets
	 * fall through to the printer) */
	static zend_string *variableFastPath(zend_object *expr)
	{
		pt_node_class_info *info = pt_get_node_class_info(expr->ce);
		if (info == NULL || !info->is_variable || info->name_offset < 0) return NULL;

		zval *name = OBJ_PROP(expr, info->name_offset);
		ZVAL_DEREF(name);
		if (Z_TYPE_P(name) != IS_STRING) return NULL;

		zend_string *nameStr = Z_STR_P(name);
		zend_string *key = zend_string_alloc(ZSTR_LEN(nameStr) + 1, 0);
		ZSTR_VAL(key)[0] = '$';
		memcpy(ZSTR_VAL(key) + 1, ZSTR_VAL(nameStr), ZSTR_LEN(nameStr));
		ZSTR_VAL(key)[ZSTR_LEN(key)] = '\0';
		return key;
	}

	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExprPrinter;

zend_string *pt_expr_printer_print_uncached(zval *exprPrinter, zend_object *node)
{
	pt_init_strs();

	/* the twin is final: an instance of the native class entry takes the
	 * native path, anything else (the PHP twin declared next to the native
	 * class in the differential tests) the method — whose two fast paths
	 * the caller has already taken, to the same result */
	if (EXPECTED(Z_OBJCE_P(exprPrinter) == pt_ce_expr_printer)) return ExprPrinter(exprPrinter).printUncached(node);

	zval nodeArg;
	ZVAL_OBJ(&nodeArg, node);
	zv::Val printed = pt_type_call(Z_OBJ_P(exprPrinter), "printexpr", sizeof("printexpr") - 1, 1, &nodeArg);
	if (UNEXPECTED(printed.isUndef())) return NULL;
	if (UNEXPECTED(!printed.ref().isString())) {
		zend_throw_error(NULL, "phpstan_turbo: printExpr did not return a string");
		return NULL;
	}
	zval printedZv = printed.take();
	return Z_STR(printedZv); /* take ownership */
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_expr_printer)
{
	reg::Class cls("PHPStan\\Node\\Printer\\ExprPrinter");
	ptdecl::ExprPrinter::declareClass(cls);
	cls.publicClassConstantString("ATTRIBUTE_CACHE_KEY", "phpstan_cache_printer");
	ptdecl::ExprPrinter::declareProperties(cls);

	/* the real parameter class name: the DI container autowires the service
	 * by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *printer;
		if (!zp::parse<zp::Obj>(execute_data, printer)) RETURN_THROWS();
		zv::ObjRef(ZEND_THIS).propAtWrite(slots::printer, zv::Val::copyOf(zv::Ref(printer)));
	});

	cls.method(sigs::printExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		pt_init_strs();
		zend_string *printed = ExprPrinter(ZEND_THIS).printExpr(Z_OBJ_P(expr));
		if (UNEXPECTED(printed == NULL)) RETURN_THROWS();
		RETURN_STR(printed);
	});

	cls.shadow(&pt_ce_expr_printer);
}

/* }}} */

/* $exprPrinter->printExpr($expr) for native callers: the native body (both
 * fast paths, then the printer) for the shadowing ExprPrinter, the method of
 * anything else; owned string, NULL with an exception pending */
zend_string *pt_expr_printer_print(zval *exprPrinter, zend_object *expr)
{
	if (EXPECTED(Z_TYPE_P(exprPrinter) == IS_OBJECT && Z_OBJCE_P(exprPrinter) == pt_ce_expr_printer)) {
		pt_init_strs();
		return ExprPrinter(exprPrinter).printExpr(expr);
	}
	if (UNEXPECTED(Z_TYPE_P(exprPrinter) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function printExpr() on %s", zend_zval_value_name(exprPrinter));
		return NULL;
	}
	zval exprArg;
	ZVAL_OBJ(&exprArg, expr);
	zv::Val printed = pt_type_call(Z_OBJ_P(exprPrinter), PT_LC("printexpr"), 1, &exprArg);
	if (UNEXPECTED(printed.isUndef())) return NULL;
	if (UNEXPECTED(!printed.ref().isString())) {
		zend_throw_error(NULL, "phpstan_turbo: printExpr did not return a string");
		return NULL;
	}
	zval printedZv = printed.take();
	return Z_STR(printedZv);
}
