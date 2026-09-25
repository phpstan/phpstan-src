/*
 * PHPStanTurbo\ArrayMapArgVisitor — native twin of
 * PHPStan\Parser\ArrayMapArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the array arguments of an array_map() call on its callback
 * argument's value.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ArrayMapArgVisitor.h"

namespace sigs = ptdecl::ArrayMapArgVisitor::sig;

static zend_class_entry *pt_ce_array_map_arg_visitor = nullptr;

static const char pt_array_map_arg_attribute[] = "arrayMapArgs";
static zend_string *pt_array_map_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::FuncCallProps;
using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ArrayMapArgVisitor. */
class ArrayMapArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static FuncCallProps call = PT_FUNC_CALL_PROPS;
		static NodeProp nameProp = PT_NAME_PROP;
		static NodeProp argNameProp = PT_NODE_PROP(PT_CLASS_ARG, "name");
		static NodeProp argValueProp = PT_NODE_PROP(PT_CLASS_ARG, "value");
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		zval *args = NULL;
		zend_string *functionName = visitors::plainFuncCallName(node, call, nameProp, &args);
		if (functionName == NULL) return !EG(exception);
		if (!visitors::lowerEquals(functionName, "array_map")) return true;

		/* every argument except the positional first one and the one named
		 * "callback" */
		zv::Arr arrayArgs = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(args)));
		for (auto entry : zv::ArrRef(args)) {
			zv::Ref arg = entry.value().deref();
			if (!arg.isObject()) continue;
			zval *argName = argNameProp.of(arg.asObject());
			bool named = argName != NULL && Z_TYPE_P(argName) == IS_OBJECT;
			bool firstPosition = entry.stringKeyOrNull() == NULL && entry.indexKey() == 0;
			if (!named && firstPosition) continue;
			if (named) {
				zend_string *name = visitors::nameString(Z_OBJ_P(argName), identifierProp);
				if (name != NULL && zend_string_equals_literal(name, "callback")) continue;
			}
			arrayArgs.push(arg);
		}

		/* isset($args[0]) && count(array_slice($args, 1)) > 0 */
		zend_object *firstArg = visitors::argAt(args, 0);
		if (firstArg == NULL || zend_hash_num_elements(Z_ARRVAL_P(args)) <= 1) return true;
		zval *value = argValueProp.of(firstArg);
		if (value == NULL || Z_TYPE_P(value) != IS_OBJECT) return true;
		visitors::setAttribute(Z_OBJ_P(value), pt_array_map_arg_attribute_str, arrayArgs.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayMapArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_array_map_arg_entry = {
	&pt_ce_array_map_arg_visitor,
	ArrayMapArgVisitor::enterNode,
	NULL,
	NULL,
};

PT_MINIT_REGISTRATION(pt_register_array_map_arg_visitor)
{
	pt_array_map_arg_attribute_str = zend_string_init_interned(pt_array_map_arg_attribute, sizeof(pt_array_map_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ArrayMapArgVisitor");
	ptdecl::ArrayMapArgVisitor::declareClass(cls);
	ptdecl::ArrayMapArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_array_map_arg_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayMapArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_array_map_arg_visitor);
	pt_native_visitor_register(&pt_array_map_arg_entry);
}

/* }}} */
