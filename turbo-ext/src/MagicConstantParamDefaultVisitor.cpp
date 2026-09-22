/*
 * PHPStanTurbo\MagicConstantParamDefaultVisitor — native twin of
 * PHPStan\Parser\MagicConstantParamDefaultVisitor, declared under that name
 * at activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks a magic constant used as a parameter's default value.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/MagicConstantParamDefaultVisitor.h"

namespace sigs = ptdecl::MagicConstantParamDefaultVisitor::sig;

static zend_class_entry *pt_ce_magic_constant_param_default_visitor = nullptr;

static const char pt_magic_constant_param_default_attribute[] = "isMagicConstantParamDefault";
static zend_string *pt_magic_constant_param_default_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\MagicConstantParamDefaultVisitor. */
class MagicConstantParamDefaultVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp defaultProp = PT_NODE_PROP(PT_CLASS_PARAM, "default");

		if (!visitors::isInstanceOf(node, PT_CLASS_PARAM)) return !EG(exception);
		zend_object *defaultValue = defaultProp.objectOf(node, PT_CLASS_MAGIC_CONST);
		if (defaultValue == NULL) return !EG(exception);
		visitors::setAttributeTrue(defaultValue, pt_magic_constant_param_default_attribute_str);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::MagicConstantParamDefaultVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_magic_constant_param_default_entry = {
	&pt_ce_magic_constant_param_default_visitor,
	MagicConstantParamDefaultVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_magic_constant_param_default_visitor()
{
	pt_magic_constant_param_default_attribute_str = zend_string_init_interned(pt_magic_constant_param_default_attribute, sizeof(pt_magic_constant_param_default_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\MagicConstantParamDefaultVisitor");
	ptdecl::MagicConstantParamDefaultVisitor::declareClass(cls);
	ptdecl::MagicConstantParamDefaultVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_magic_constant_param_default_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!MagicConstantParamDefaultVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_magic_constant_param_default_visitor);
	pt_native_visitor_register(&pt_magic_constant_param_default_entry);
}

/* }}} */
