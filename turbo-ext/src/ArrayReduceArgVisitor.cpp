/*
 * PHPStanTurbo\ArrayReduceArgVisitor — native twin of
 * PHPStan\Parser\ArrayReduceArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the array and initial arguments of an array_reduce() call on its
 * callback argument's value.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ArrayReduceArgVisitor.h"

namespace sigs = ptdecl::ArrayReduceArgVisitor::sig;

static zend_class_entry *pt_ce_array_reduce_arg_visitor = nullptr;

static const char pt_array_reduce_arg_attribute[] = "arrayReduceArgs";
static zend_string *pt_array_reduce_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::FuncCallProps;
using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ArrayReduceArgVisitor. */
class ArrayReduceArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static FuncCallProps call = PT_FUNC_CALL_PROPS;
		static NodeProp nameProp = PT_NAME_PROP;
		static NodeProp argNameProp = PT_NODE_PROP(PT_CLASS_ARG, "name");
		static NodeProp argValueProp = PT_NODE_PROP(PT_CLASS_ARG, "value");
		static NodeProp argUnpackProp = PT_NODE_PROP(PT_CLASS_ARG, "unpack");
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		zval *args = NULL;
		zend_string *functionName = visitors::plainFuncCallName(node, call, nameProp, &args);
		if (functionName == NULL) return !EG(exception);
		if (!visitors::lowerEquals(functionName, "array_reduce")) return true;

		zend_object *arrayArg = NULL;
		zend_object *callbackArg = NULL;
		zend_object *initialArg = NULL;
		for (auto entry : zv::ArrRef(args)) {
			zv::Ref arg = entry.value().deref();
			if (!arg.isObject()) continue;
			zval *unpack = argUnpackProp.of(arg.asObject());
			if (unpack != NULL && zend_is_true(unpack)) return true;

			/* $arg->name !== null ? $arg->name->toString() : null */
			zend_string *name = NULL;
			zval *argName = argNameProp.of(arg.asObject());
			bool named = argName != NULL && Z_TYPE_P(argName) == IS_OBJECT;
			if (named) {
				name = visitors::nameString(Z_OBJ_P(argName), identifierProp);
			}
			bool positional = !named && entry.stringKeyOrNull() == NULL;
			if ((name != NULL && zend_string_equals_literal(name, "array")) || (positional && entry.indexKey() == 0)) {
				arrayArg = arg.asObject();
			} else if ((name != NULL && zend_string_equals_literal(name, "callback")) || (positional && entry.indexKey() == 1)) {
				callbackArg = arg.asObject();
			} else if ((name != NULL && zend_string_equals_literal(name, "initial")) || (positional && entry.indexKey() == 2)) {
				initialArg = arg.asObject();
			}
		}
		if (arrayArg == NULL || callbackArg == NULL) return true;

		zval *value = argValueProp.of(callbackArg);
		if (value == NULL || Z_TYPE_P(value) != IS_OBJECT) return true;

		/* [$arrayArg, $initialArg] */
		zv::Arr reduceArgs = zv::Arr::create(2);
		zval arrayArgZv;
		ZVAL_OBJ(&arrayArgZv, arrayArg);
		reduceArgs.push(zv::Ref(&arrayArgZv));
		zval initialArgZv;
		if (initialArg != NULL) {
			ZVAL_OBJ(&initialArgZv, initialArg);
		} else {
			ZVAL_NULL(&initialArgZv);
		}
		reduceArgs.push(zv::Ref(&initialArgZv));
		visitors::setAttribute(Z_OBJ_P(value), pt_array_reduce_arg_attribute_str, reduceArgs.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayReduceArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_array_reduce_arg_entry = {
	&pt_ce_array_reduce_arg_visitor,
	ArrayReduceArgVisitor::enterNode,
	NULL,
	NULL,
};

PT_MINIT_REGISTRATION(pt_register_array_reduce_arg_visitor)
{
	pt_array_reduce_arg_attribute_str = zend_string_init_interned(pt_array_reduce_arg_attribute, sizeof(pt_array_reduce_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ArrayReduceArgVisitor");
	ptdecl::ArrayReduceArgVisitor::declareClass(cls);
	ptdecl::ArrayReduceArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_array_reduce_arg_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayReduceArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_array_reduce_arg_visitor);
	pt_native_visitor_register(&pt_array_reduce_arg_entry);
}

/* }}} */
