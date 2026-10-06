/*
 * PHPStanTurbo\ClosureBindArgVisitor — native twin of
 * PHPStan\Parser\ClosureBindArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the closure argument of a Closure::bind() call that also passes a
 * new $this, each argument found by position or by name.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ClosureBindArgVisitor.h"

namespace sigs = ptdecl::ClosureBindArgVisitor::sig;

static zend_class_entry *pt_ce_closure_bind_arg_visitor = nullptr;

static const char pt_closure_bind_arg_attribute[] = "closureBindArg";
static zend_string *pt_closure_bind_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ClosureBindArgVisitor. */
class ClosureBindArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		zval *args = NULL;
		if (!isClosureBindCall(node, &args)) return !EG(exception);

		BindArgs bindArgs;
		findBindArgs(args, bindArgs);
		if (bindArgs.closure != NULL && bindArgs.newThis != NULL) {
			visitors::setAttributeTrue(bindArgs.closure, pt_closure_bind_arg_attribute_str);
		}
		return !EG(exception);
	}

private:
	/* the $closureArg / $newThisArg the twin picks out of the call's
	 * arguments; NULL for null */
	struct BindArgs
	{
		zend_object *closure = NULL;
		zend_object *newThis = NULL;
	};

	/*
	 * foreach ($node->getArgs() as $i => $arg): an unnamed argument by its
	 * position (0, 1), a named one by its name (closure, newThis); a named
	 * argument does not replace one already found (`??=`)
	 */
	static void findBindArgs(zval *args, BindArgs &out)
	{
		static NodeProp argNameProp = PT_NODE_PROP(PT_CLASS_ARG, "name");
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		if (args == NULL || Z_TYPE_P(args) != IS_ARRAY) return;
		for (auto entry : zv::ArrRef(args)) {
			zv::Ref value = entry.value().deref();
			if (!value.isObject()) continue;
			zend_object *arg = value.asObject();
			zend_object *name = visitors::isInstanceOf(arg, PT_CLASS_ARG) ? argNameProp.objectOf(arg, PT_CLASS_IDENTIFIER) : NULL;
			if (name == NULL) {
				if (entry.hasStringKey()) continue;
				zend_ulong i = entry.indexKey();
				if (i == 0) {
					out.closure = arg;
				} else if (i == 1) {
					out.newThis = arg;
				}
				continue;
			}

			/* $arg->name->toString() */
			zend_string *argName = visitors::nameString(name, identifierProp);
			if (argName == NULL) continue;
			if (zend_string_equals_literal(argName, "closure")) {
				if (out.closure == NULL) out.closure = arg;
			} else if (zend_string_equals_literal(argName, "newThis")) {
				if (out.newThis == NULL) out.newThis = arg;
			}
		}
	}

	/*
	 * `$node instanceof StaticCall && $node->class instanceof Name &&
	 * $node->class->toLowerString() === 'closure' && $node->name instanceof
	 * Identifier && $node->name->toLowerString() === 'bind' &&
	 * !$node->isFirstClassCallable()`, plus the call's $args slot
	 */
	static bool isClosureBindCall(zend_object *node, zval **argsOut)
	{
		static NodeProp classProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
		static NodeProp methodProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
		static NodeProp argsProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "args");
		static NodeProp nameProp = PT_NAME_PROP;
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		if (!visitors::isInstanceOf(node, PT_CLASS_STATIC_CALL)) return false;
		zend_object *className = classProp.objectOf(node, PT_CLASS_NAME);
		if (className == NULL) return false;
		zend_string *classString = visitors::nameString(className, nameProp);
		if (classString == NULL || !visitors::lowerEquals(classString, "closure")) return false;
		zend_object *method = methodProp.objectOf(node, PT_CLASS_IDENTIFIER);
		if (method == NULL) return false;
		zend_string *methodName = visitors::nameString(method, identifierProp);
		if (methodName == NULL || !visitors::lowerEquals(methodName, "bind")) return false;
		zval *args = argsProp.of(node);
		if (visitors::isFirstClassCallable(args)) return false;
		*argsOut = args;
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureBindArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_closure_bind_arg_entry = {
	&pt_ce_closure_bind_arg_visitor,
	ClosureBindArgVisitor::enterNode,
	NULL,
	NULL,
};

PT_MINIT_REGISTRATION(pt_register_closure_bind_arg_visitor)
{
	pt_closure_bind_arg_attribute_str = zend_string_init_interned(pt_closure_bind_arg_attribute, sizeof(pt_closure_bind_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ClosureBindArgVisitor");
	ptdecl::ClosureBindArgVisitor::declareClass(cls);
	ptdecl::ClosureBindArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_closure_bind_arg_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ClosureBindArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_closure_bind_arg_visitor);
	pt_native_visitor_register(&pt_closure_bind_arg_entry);
}

/* }}} */
