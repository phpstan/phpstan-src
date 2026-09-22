/*
 * PHPStanTurbo\CurlSetOptArgVisitor — native twin of
 * PHPStan\Parser\CurlSetOptArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the curl handle argument of a curl_setopt() call.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/CurlSetOptArgVisitor.h"

namespace sigs = ptdecl::CurlSetOptArgVisitor::sig;

static zend_class_entry *pt_ce_curl_set_opt_arg_visitor = nullptr;

static const char pt_curl_set_opt_arg_attribute[] = "isCurlSetOptArg";
static zend_string *pt_curl_set_opt_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::FuncCallProps;
using visitors::NodeProp;

/* Mirrors PHPStan\Parser\CurlSetOptArgVisitor. */
class CurlSetOptArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static FuncCallProps call = PT_FUNC_CALL_PROPS;
		static NodeProp nameProp = PT_NAME_PROP;

		zval *args = NULL;
		zend_string *functionName = visitors::plainFuncCallName(node, call, nameProp, &args);
		if (functionName == NULL) return !EG(exception);
		if (!(visitors::lowerEquals(functionName, "curl_setopt"))) return true;
		zend_object *arg = visitors::argAt(args, 0);
		if (arg != NULL) {
			visitors::setAttributeTrue(arg, pt_curl_set_opt_arg_attribute_str);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CurlSetOptArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_curl_set_opt_arg_entry = {
	&pt_ce_curl_set_opt_arg_visitor,
	CurlSetOptArgVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_curl_set_opt_arg_visitor()
{
	pt_curl_set_opt_arg_attribute_str = zend_string_init_interned(pt_curl_set_opt_arg_attribute, sizeof(pt_curl_set_opt_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\CurlSetOptArgVisitor");
	ptdecl::CurlSetOptArgVisitor::declareClass(cls);
	ptdecl::CurlSetOptArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_curl_set_opt_arg_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!CurlSetOptArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_curl_set_opt_arg_visitor);
	pt_native_visitor_register(&pt_curl_set_opt_arg_entry);
}

/* }}} */
