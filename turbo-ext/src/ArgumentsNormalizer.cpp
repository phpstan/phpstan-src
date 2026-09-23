/*
 * PHPStanTurbo\ArgumentsNormalizer — native implementation of
 * PHPStan\Analyser\ArgumentsNormalizer.
 *
 * A final class of static methods reordering named call arguments into the
 * positions a ParametersAcceptor expects. The reorder*Arguments() methods are
 * exported as pt_arguments_normalizer_reorder_*() for the native call
 * handlers and helpers; the common call without named arguments answers with
 * the call itself without building the array_values() copy the twin compares
 * against it (identical by construction for a list). The arguments and
 * attributes are read through property sites, the parameters through their
 * DummyParameter slots (ParameterValues.h) or cached sites, the nodes built
 * through the class map; ParametersAcceptorSelector::selectFromArgs() and the
 * callable acceptors of call_user_func*() stay PHP.
 */

#include "support.h"
#include "generated/ArgumentsNormalizer.h"

namespace sigs = ptdecl::ArgumentsNormalizer::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"
#include "AcceptorValues.h"

zend_class_entry *pt_ce_arguments_normalizer = nullptr;

namespace {

/* persistent interned literals, created at registration */
zend_string *pt_an_original_arg = nullptr;
zend_string *pt_an_callback = nullptr;
zend_string *pt_an_args = nullptr;

/* {{{ generic reads and calls */

zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
}

/* the engine's read of $object->name when the slot fast path does not apply;
 * NULL = pending exception */
zend_never_inline zval *readPropertySlow(zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (Z_TYPE_P(object) != IS_OBJECT) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(object));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return NULL;
	}
	if (value == &rv) {
		hold = zv::Val::adopt(rv);
		return hold.raw();
	}
	ZVAL_DEREF(value);
	return value;
}

/* $object->name — the declared slot through the site, the engine's read
 * otherwise; borrowed (or kept alive in hold), NULL = pending exception */
inline zval *readProperty(pt_property_site &site, zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(object) == IS_OBJECT)) {
		zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
		if (EXPECTED(slot != NULL)) {
			ZVAL_DEREF(slot);
			if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return slot;
		}
	}
	return readPropertySlow(object, name, len, hold);
}

/* $value instanceof <class-map class>; false = pending exception */
inline bool isA(zval *value, int classIdx, bool &out)
{
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* a declared `array` result — the TypeError of the array function the twin
 * would hand anything else to; false = pending exception */
inline bool requireArray(zval *value, const char *what)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
	zend_type_error("%s must be of type array, %s given", what, zend_zval_value_name(value));
	return false;
}

/* }}} */

/* {{{ node reads and nodes */

pt_property_site pt_an_arg_value_site;
pt_property_site pt_an_arg_name_site;
pt_property_site pt_an_arg_by_ref_site;
pt_property_site pt_an_arg_unpack_site;
pt_property_site pt_an_identifier_name_site;
pt_property_site pt_an_node_attributes_site;
pt_property_site pt_an_call_var_site;
pt_property_site pt_an_call_name_site;
pt_property_site pt_an_call_class_site;
pt_property_site pt_an_array_items_site;
pt_property_site pt_an_item_key_site;
pt_property_site pt_an_item_value_site;
pt_property_site pt_an_item_by_ref_site;
pt_property_site pt_an_item_unpack_site;
pt_property_site pt_an_string_value_site;
pt_method_site pt_an_identifier_to_string_site;

/* $callLike->getArgs() (pt_call_like_args(): the `args` slot, or the
 * method's result) as an owned value; UNDEF = pending exception */
zv::Val callArgs(zval *callLike)
{
	zv::Val hold;
	zval *args = pt_call_like_args(Z_OBJ_P(callLike), hold);
	if (UNEXPECTED(args == NULL)) return zv::Val();
	if (!hold.isUndef()) return hold;
	return zv::Val::copyOf(zv::Ref(args));
}

/* $identifier->toString(): the $name slot of a php-parser Identifier, the
 * method otherwise; UNDEF = pending exception */
zv::Val identifierToString(zval *identifier)
{
	if (EXPECTED(Z_TYPE_P(identifier) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(identifier);
		if (EXPECTED(ce == pt_class_loaded(PT_CLASS_IDENTIFIER) || ce == pt_class_loaded(PT_CLASS_VAR_LIKE_IDENTIFIER))) {
			zv::Val hold;
			zval *name = readProperty(pt_an_identifier_name_site, identifier, PT_LC("name"), hold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(name));
		}
	}
	return callOn(pt_an_identifier_to_string_site, identifier, PT_LC("tostring"), "toString", 0, NULL);
}

/* $node->getAttributes() (NodeAbstract's: the $attributes slot); UNDEF =
 * pending exception */
zv::Val nodeAttributes(zval *node)
{
	zv::Val hold;
	zval *attributes = readProperty(pt_an_node_attributes_site, node, PT_LC("attributes"), hold);
	if (UNEXPECTED(attributes == NULL)) return zv::Val();
	return zv::Val::copyOf(zv::Ref(attributes));
}

/* self::attributesWithoutPrintedForm($node): the attributes without
 * ExprPrinter::ATTRIBUTE_CACHE_KEY; UNDEF = pending exception */
zv::Val attributesWithoutPrintedForm(zval *node)
{
	zv::Val attributes = nodeAttributes(node);
	if (UNEXPECTED(attributes.isUndef())) return zv::Val();
	if (UNEXPECTED(!requireArray(attributes.raw(), "getAttributes()"))) return zv::Val();
	pt_init_strs();
	if (zend_hash_exists(Z_ARRVAL_P(attributes.raw()), pt_str_cache_printer)) {
		SEPARATE_ARRAY(attributes.raw());
		zend_hash_del(Z_ARRVAL_P(attributes.raw()), pt_str_cache_printer);
	}
	return attributes;
}

/* new Arg($arg->value, $arg->byRef, $arg->unpack, $arg->getAttributes() +
 * [ORIGINAL_ARG_ATTRIBUTE => $arg], null); UNDEF = pending exception */
zv::Val normalizedArg(zval *arg)
{
	zv::Val valueHold, byRefHold, unpackHold;
	zval *value = readProperty(pt_an_arg_value_site, arg, PT_LC("value"), valueHold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	zval *byRef = readProperty(pt_an_arg_by_ref_site, arg, PT_LC("byRef"), byRefHold);
	if (UNEXPECTED(byRef == NULL)) return zv::Val();
	zval *unpack = readProperty(pt_an_arg_unpack_site, arg, PT_LC("unpack"), unpackHold);
	if (UNEXPECTED(unpack == NULL)) return zv::Val();
	zv::Val attributes = nodeAttributes(arg);
	if (UNEXPECTED(attributes.isUndef())) return zv::Val();
	if (UNEXPECTED(!requireArray(attributes.raw(), "getAttributes()"))) return zv::Val();
	SEPARATE_ARRAY(attributes.raw());
	Z_TRY_ADDREF_P(arg);
	zend_symtable_update(Z_ARRVAL_P(attributes.raw()), pt_an_original_arg, arg);
	zval null = {};
	ZVAL_NULL(&null);
	zv::Args argv{value, byRef, unpack, attributes.raw(), &null};
	return pt_type_new(PT_CLASS_ARG, 5, argv);
}

/* array_values($array) */
zv::Val arrayValues(zval *array)
{
	HashTable *table = Z_ARRVAL_P(array);
	if (zend_array_is_list(table)) return zv::Val::copyOf(zv::Ref(array));
	zv::Arr values = zv::Arr::create(zend_hash_num_elements(table));
	for (zv::ArrayEntry entry : zv::TableRef(table)) {
		values.push(zv::Ref(entry.value().deref().raw()));
	}
	return zv::Val(std::move(values));
}

/* $a === $b for two arrays */
bool arraysIdentical(zval *a, zval *b)
{
	return zend_is_identical(a, b);
}

/* }}} */

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_an_get_callable_parameters_acceptors_site;

/* $parameter->getName() (ParameterValues.h) */
zv::Val parameterGetName(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
}

/* $parameter->isVariadic() / ->isOptional() (truthiness); false = pending exception */
bool parameterIsVariadic(zval *parameter, bool &out)
{
	return pt_parameter_reflection_bool(parameter, PT_PR_IS_VARIADIC, out);
}

bool parameterIsOptional(zval *parameter, bool &out)
{
	return pt_parameter_reflection_bool(parameter, PT_PR_IS_OPTIONAL, out);
}

/* $parameter->getDefaultValue() */
zv::Val parameterGetDefaultValue(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_DEFAULT_VALUE);
}

/* $acceptor->getParameters() */
zv::Val acceptorGetParameters(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
}

/* ParametersAcceptorSelector::selectFromArgs($scope, $args, $acceptors, null) */
zv::Val selectFromArgs(zval *scope, zval *args, zval *acceptors)
{
	return pt_parameters_acceptor_selector_select_from_args(scope, args, acceptors, NULL);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ArgumentsNormalizer; UNDEF = pending exception,
 * null for the twin's null. */
class ArgumentsNormalizer
{
public:
	/* the kinds of call reorder*Arguments() rebuild */
	enum CallKind
	{
		FUNC_CALL,
		METHOD_CALL,
		STATIC_CALL,
		NEW_CALL,
	};

	/* Mirrors reorderArgs() */
	static zv::Val reorderArgs(zval *parametersAcceptor, zval *callArgs)
	{
		HashTable *args = Z_ARRVAL_P(callArgs);
		if (zend_hash_num_elements(args) == 0) return zv::Val(zv::Arr::empty());

		bool hasNamedArgs = false;
		for (zv::ArrayEntry entry : zv::TableRef(args)) {
			zv::Val nameHold;
			zval *name = readProperty(pt_an_arg_name_site, entry.value().deref().raw(), PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) != IS_NULL) {
				hasNamedArgs = true;
				break;
			}
		}
		if (EXPECTED(!hasNamedArgs)) return arrayValues(callArgs);

		return reorderNamedArgs(parametersAcceptor, args);
	}

	/* Mirrors reorderFuncArguments() / reorderMethodArguments() /
	 * reorderStaticCallArguments() / reorderNewArguments() */
	static zv::Val reorderCallArguments(CallKind kind, zval *parametersAcceptor, zval *call)
	{
		zv::Val args = callArgs(call);
		if (UNEXPECTED(args.isUndef() || !requireArray(args.raw(), "getArgs()"))) return zv::Val();
		zv::Val reorderedArgs = reorderArgs(parametersAcceptor, args.raw());
		if (UNEXPECTED(reorderedArgs.isUndef())) return zv::Val();
		if (reorderedArgs.isNull()) return zv::Val::null();

		// return identical object if not reordered, as TypeSpecifier relies on object identity
		if (EXPECTED(arraysIdentical(reorderedArgs.raw(), args.raw()))) return zv::Val::copyOf(zv::Ref(call));

		return rebuildCall(kind, call, reorderedArgs.raw());
	}

	/* Mirrors reorderCallUserFuncArguments() */
	static zv::Val reorderCallUserFuncArguments(zval *callUserFuncCall, zval *scope)
	{
		zv::Val args = callArgs(callUserFuncCall);
		if (UNEXPECTED(args.isUndef() || !requireArray(args.raw(), "getArgs()"))) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(args.raw())) < 1) return zv::Val::null();

		zv::Arr passThruArgs = zv::Arr::empty();
		zval *callbackArg = NULL;
		for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
			zval *arg = entry.value().deref().raw();
			if (callbackArg == NULL) {
				bool isCallback = false;
				if (UNEXPECTED(!isCallbackArg(arg, entry, isCallback))) return zv::Val();
				if (isCallback) {
					callbackArg = arg;
					continue;
				}
			}

			passThruArgs.push(zv::Ref(arg));
		}

		if (callbackArg == NULL) return zv::Val::null();
		return reorderedCallUserFunc(callUserFuncCall, scope, callbackArg, passThruArgs);
	}

	/* Mirrors reorderCallUserFuncArrayArguments() */
	static zv::Val reorderCallUserFuncArrayArguments(zval *callUserFuncArrayCall, zval *scope)
	{
		zv::Val args = callArgs(callUserFuncArrayCall);
		if (UNEXPECTED(args.isUndef() || !requireArray(args.raw(), "getArgs()"))) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(args.raw())) < 2) return zv::Val::null();

		zval *callbackArg = NULL;
		zval *argsArrayArg = NULL;
		for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
			zval *arg = entry.value().deref().raw();
			if (callbackArg == NULL) {
				bool isCallback = false;
				if (UNEXPECTED(!isCallbackArg(arg, entry, isCallback))) return zv::Val();
				if (isCallback) {
					callbackArg = arg;
					continue;
				}
			}

			if (argsArrayArg != NULL) continue;
			zv::Val nameHold;
			zval *name = readProperty(pt_an_arg_name_site, arg, PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) == IS_NULL && entry.stringKeyOrNull() == NULL && entry.indexKey() == 1) {
				argsArrayArg = arg;
				continue;
			}
			if (Z_TYPE_P(name) == IS_NULL) continue;
			zv::Val nameString = identifierToString(name);
			if (UNEXPECTED(nameString.isUndef())) return zv::Val();
			if (!nameString.ref().isString() || !zend_string_equals(Z_STR_P(nameString.raw()), pt_an_args)) continue;
			argsArrayArg = arg;
		}

		if (callbackArg == NULL || argsArrayArg == NULL) return zv::Val::null();

		zv::Val arrayHold;
		zval *argsArray = readProperty(pt_an_arg_value_site, argsArrayArg, PT_LC("value"), arrayHold);
		if (UNEXPECTED(argsArray == NULL)) return zv::Val();
		bool isArray = false;
		if (UNEXPECTED(!isA(argsArray, PT_CLASS_ARRAY_EXPR, isArray))) return zv::Val();
		if (!isArray) return zv::Val::null();

		zv::Arr passThruArgs = zv::Arr::empty();
		zv::Val itemsHold;
		zval *items = readProperty(pt_an_array_items_site, argsArray, PT_LC("items"), itemsHold);
		if (UNEXPECTED(items == NULL || !requireArray(items, "foreach() argument"))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(items)) {
			zval *item = entry.value().deref().raw();
			zv::Val keyHold;
			zval *key = readProperty(pt_an_item_key_site, item, PT_LC("key"), keyHold);
			if (UNEXPECTED(key == NULL)) return zv::Val();
			zv::Val stringKey; /* UNDEF: an integer or no key */
			bool isString = false;
			if (UNEXPECTED(!isA(key, PT_CLASS_SCALAR_STRING, isString))) return zv::Val();
			if (isString) {
				zv::Val valueHold;
				zval *keyValue = readProperty(pt_an_string_value_site, key, PT_LC("value"), valueHold);
				if (UNEXPECTED(keyValue == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(keyValue) != IS_STRING)) {
					zend_type_error("Illegal offset type");
					return zv::Val();
				}
				/* key([$value => null]): a numeric string becomes an integer key */
				zend_ulong index;
				if (!ZEND_HANDLE_NUMERIC_STR(ZSTR_VAL(Z_STR_P(keyValue)), ZSTR_LEN(Z_STR_P(keyValue)), index)) {
					if (ZSTR_LEN(Z_STR_P(keyValue)) == 0) return zv::Val::null();
					stringKey = zv::Val::copyOf(zv::Ref(keyValue));
				}
			} else if (Z_TYPE_P(key) != IS_NULL) {
				bool isInt = false;
				if (UNEXPECTED(!isA(key, PT_CLASS_SCALAR_INT, isInt))) return zv::Val();
				// Dynamic key, we cannot be sure.
				if (!isInt) return zv::Val::null();
			}

			zv::Val valueHold, byRefHold, unpackHold;
			zval *value = readProperty(pt_an_item_value_site, item, PT_LC("value"), valueHold);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			zval *byRef = readProperty(pt_an_item_by_ref_site, item, PT_LC("byRef"), byRefHold);
			if (UNEXPECTED(byRef == NULL)) return zv::Val();
			zval *unpack = readProperty(pt_an_item_unpack_site, item, PT_LC("unpack"), unpackHold);
			if (UNEXPECTED(unpack == NULL)) return zv::Val();
			zv::Val attributes = attributesWithoutPrintedForm(item);
			if (UNEXPECTED(attributes.isUndef())) return zv::Val();
			zv::Val name = zv::Val::null();
			if (!stringKey.isUndef()) {
				name = pt_name_node_new(PT_CLASS_IDENTIFIER, stringKey.raw());
				if (UNEXPECTED(name.isUndef())) return zv::Val();
			}
			zv::Args argArgs{value, byRef, unpack, attributes.raw(), name.raw()};
			zv::Val passThruArg = pt_type_new(PT_CLASS_ARG, 5, argArgs);
			if (UNEXPECTED(passThruArg.isUndef())) return zv::Val();
			passThruArgs.push(std::move(passThruArg));
		}

		return reorderedCallUserFunc(callUserFuncArrayCall, scope, callbackArg, passThruArgs);
	}

private:
	/* the callback argument of call_user_func*(): the unnamed first one, or
	 * the one named `callback`; false = pending exception */
	static bool isCallbackArg(zval *arg, zv::ArrayEntry &entry, bool &out)
	{
		out = false;
		zv::Val nameHold;
		zval *name = readProperty(pt_an_arg_name_site, arg, PT_LC("name"), nameHold);
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) == IS_NULL) {
			out = entry.stringKeyOrNull() == NULL && entry.indexKey() == 0;
			return true;
		}
		zv::Val nameString = identifierToString(name);
		if (UNEXPECTED(nameString.isUndef())) return false;
		out = nameString.ref().isString() && zend_string_equals(Z_STR_P(nameString.raw()), pt_an_callback);
		return true;
	}

	/* the shared tail of the call_user_func*() reorderings: the callback's
	 * callable acceptors selected over the pass-through arguments, the
	 * rebuilt call and whether every acceptor takes named arguments */
	static zv::Val reorderedCallUserFunc(zval *call, zval *scope, zval *callbackArg, zv::Arr &passThruArgs)
	{
		zv::Val valueHold;
		zval *callbackValue = readProperty(pt_an_arg_value_site, callbackArg, PT_LC("value"), valueHold);
		if (UNEXPECTED(callbackValue == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(scope));
			return zv::Val();
		}
		zv::Val calledOnType = pt_mutating_scope_get_type(Z_OBJ_P(scope), callbackValue);
		if (UNEXPECTED(calledOnType.isUndef())) return zv::Val();
		if (UNEXPECTED(!calledOnType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(calledOnType.raw()));
			return zv::Val();
		}
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(calledOnType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable != PT_TRI_YES) return zv::Val::null();

		zv::Val callableParametersAcceptors = pt_call_method_cached(pt_an_get_callable_parameters_acceptors_site, Z_OBJ_P(calledOnType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(callableParametersAcceptors.isUndef())) return zv::Val();
		zv::Val parametersAcceptor = selectFromArgs(scope, passThruArgs.raw(), callableParametersAcceptors.raw());
		if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();

		zend_long acceptsNamedArguments = PT_TRI_YES;
		if (UNEXPECTED(!requireArray(callableParametersAcceptors.raw(), "foreach() argument"))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(callableParametersAcceptors.raw())) {
			zv::Val accepts = pt_parameters_acceptor_call(entry.value().deref().raw(), PT_PA_ACCEPTS_NAMED_ARGUMENTS);
			if (UNEXPECTED(accepts.isUndef())) return zv::Val();
			zend_long value = pt_type_trinary_value(accepts.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			acceptsNamedArguments = pt_trinary_and(acceptsNamedArguments, value);
		}

		zv::Val attributes = attributesWithoutPrintedForm(call);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Args funcCallArgs{callbackValue, passThruArgs.raw(), attributes.raw()};
		zv::Val funcCall = pt_type_new(PT_CLASS_FUNC_CALL, 3, funcCallArgs);
		if (UNEXPECTED(funcCall.isUndef())) return zv::Val();

		zv::Arr result = zv::Arr::create(3);
		result.push(std::move(parametersAcceptor));
		result.push(std::move(funcCall));
		result.push(zv::Ref(pt_trinary_singleton(acceptsNamedArguments)));
		return zv::Val(std::move(result));
	}

	/* new FuncCall / MethodCall / StaticCall / New_ over the reordered
	 * arguments and the call's attributes without its printed form */
	static zend_never_inline zv::Val rebuildCall(CallKind kind, zval *call, zval *reorderedArgs)
	{
		zv::Val firstHold, nameHold;
		zval *first;
		switch (kind) {
			case METHOD_CALL:
				first = readProperty(pt_an_call_var_site, call, PT_LC("var"), firstHold);
				break;
			case FUNC_CALL:
				first = readProperty(pt_an_call_name_site, call, PT_LC("name"), firstHold);
				break;
			default:
				first = readProperty(pt_an_call_class_site, call, PT_LC("class"), firstHold);
				break;
		}
		if (UNEXPECTED(first == NULL)) return zv::Val();
		zval *name = NULL;
		if (kind == METHOD_CALL || kind == STATIC_CALL) {
			name = readProperty(pt_an_call_name_site, call, PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
		}
		zv::Val attributes = attributesWithoutPrintedForm(call);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		switch (kind) {
			case METHOD_CALL: {
				zv::Args argv{first, name, reorderedArgs, attributes.raw()};
				return pt_type_new(PT_CLASS_METHOD_CALL, 4, argv);
			}
			case STATIC_CALL: {
				zv::Args argv{first, name, reorderedArgs, attributes.raw()};
				return pt_type_new(PT_CLASS_STATIC_CALL, 4, argv);
			}
			case FUNC_CALL: {
				zv::Args argv{first, reorderedArgs, attributes.raw()};
				return pt_type_new(PT_CLASS_FUNC_CALL, 3, argv);
			}
			default: {
				zv::Args argv{first, reorderedArgs, attributes.raw()};
				return pt_type_new(PT_CLASS_NEW, 3, argv);
			}
		}
	}

	/* the lookup of a key the twin spells as an array index (an integer, or
	 * a string with symtable semantics) */
	static zval *findKey(HashTable *table, zval *key)
	{
		if (Z_TYPE_P(key) == IS_LONG) return zend_hash_index_find(table, (zend_ulong) Z_LVAL_P(key));
		if (Z_TYPE_P(key) == IS_STRING) return zend_symtable_find(table, Z_STR_P(key));
		return NULL;
	}

	/* $array[$key] = $value (owned) for such a key */
	static void setKey(zv::Arr &array, zval *key, zv::Val value)
	{
		array.separate();
		zval raw = value.take();
		if (Z_TYPE_P(key) == IS_STRING) {
			zend_symtable_update(array.table(), Z_STR_P(key), &raw);
		} else {
			zend_hash_index_update(array.table(), (zend_ulong) Z_LVAL_P(key), &raw);
		}
	}

	/* the key of an array entry as a zval */
	static void keyOf(zv::ArrayEntry &entry, zval *out)
	{
		zend_string *stringKey = entry.stringKeyOrNull();
		if (stringKey != NULL) {
			ZVAL_STR(out, stringKey);
		} else {
			ZVAL_LONG(out, (zend_long) entry.indexKey());
		}
	}

	/* the ksort() key order of two buckets */
	static int compareKeys(Bucket *a, Bucket *b)
	{
		/* integer keys compare signed, like ksort() */
		if (a->key == NULL && b->key == NULL) return (zend_long) a->h < (zend_long) b->h ? -1 : ((zend_long) a->h > (zend_long) b->h ? 1 : 0);
		zval first, second;
		if (a->key != NULL) {
			ZVAL_STR(&first, a->key);
		} else {
			ZVAL_LONG(&first, (zend_long) a->h);
		}
		if (b->key != NULL) {
			ZVAL_STR(&second, b->key);
		} else {
			ZVAL_LONG(&second, (zend_long) b->h);
		}
		return zend_compare(&first, &second);
	}

	/* reorderArgs() past its no-named-arguments return */
	static zend_never_inline zv::Val reorderNamedArgs(zval *parametersAcceptor, HashTable *args)
	{
		bool hasVariadic = false;
		zv::Arr argumentPositions = zv::Arr::empty();
		zv::Val signatureParameters = acceptorGetParameters(parametersAcceptor);
		if (UNEXPECTED(signatureParameters.isUndef() || !requireArray(signatureParameters.raw(), "foreach() argument"))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(signatureParameters.raw())) {
			// variadic parameter must be last
			if (hasVariadic) return zv::Val::null();

			zval *parameter = entry.value().deref().raw();
			if (UNEXPECTED(!parameterIsVariadic(parameter, hasVariadic))) return zv::Val();
			zv::Val name = parameterGetName(parameter);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zval position;
			keyOf(entry, &position);
			Z_TRY_ADDREF(position);
			argumentPositions.separate();
			if (name.ref().isString()) {
				zend_symtable_update(argumentPositions.table(), Z_STR_P(name.raw()), &position);
			} else {
				zval_ptr_dtor(&position);
				zend_type_error("Illegal offset type");
				return zv::Val();
			}
		}

		zv::Arr reorderedArgs = zv::Arr::empty();
		zv::Arr additionalNamedArgs = zv::Arr::empty();
		zv::Arr appendArgs = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::TableRef(args)) {
			zval *arg = entry.value().deref().raw();
			zv::Val nameHold;
			zval *name = readProperty(pt_an_arg_name_site, arg, PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) == IS_NULL) {
				// add regular args as is
				zv::Val normalized = normalizedArg(arg);
				if (UNEXPECTED(normalized.isUndef())) return zv::Val();
				zval key;
				keyOf(entry, &key);
				setKey(reorderedArgs, &key, std::move(normalized));
				continue;
			}

			zv::Val argName = identifierToString(name);
			if (UNEXPECTED(argName.isUndef())) return zv::Val();
			if (UNEXPECTED(!argName.ref().isString())) {
				zend_type_error("array_key_exists(): Argument #1 ($key) must be a valid array offset type");
				return zv::Val();
			}
			zval *position = zend_symtable_find(argumentPositions.table(), Z_STR_P(argName.raw()));
			if (position != NULL) {
				// order named args into the position the signature expects them
				if (findKey(reorderedArgs.table(), position) != NULL) continue;
				zv::Val normalized = normalizedArg(arg);
				if (UNEXPECTED(normalized.isUndef())) return zv::Val();
				setKey(reorderedArgs, position, std::move(normalized));
				continue;
			}

			zv::Val normalized = normalizedArg(arg);
			if (UNEXPECTED(normalized.isUndef())) return zv::Val();
			if (!hasVariadic) {
				appendArgs.push(std::move(normalized));
				continue;
			}
			additionalNamedArgs.push(std::move(normalized));
		}

		// replace variadic parameter with additional named args, except if it is already set
		zend_long additionalNamedArgsOffset = (zend_long) zend_hash_num_elements(argumentPositions.table()) - 1;
		if (zend_hash_index_exists(reorderedArgs.table(), (zend_ulong) additionalNamedArgsOffset)) {
			additionalNamedArgsOffset++;
		}
		for (zv::ArrayEntry entry : zv::TableRef(additionalNamedArgs.table())) {
			zval key;
			ZVAL_LONG(&key, additionalNamedArgsOffset + (zend_long) entry.indexKey());
			setKey(reorderedArgs, &key, zv::Val::copyOf(entry.value()));
		}

		if (zend_hash_num_elements(reorderedArgs.table()) == 0) {
			for (zv::ArrayEntry entry : zv::TableRef(appendArgs.table())) {
				reorderedArgs.push(zv::Ref(entry.value().raw()));
			}
			return zv::Val(std::move(reorderedArgs));
		}

		// fill up all holes with default values until the last given argument
		zval maxKey;
		ZVAL_UNDEF(&maxKey);
		for (zv::ArrayEntry entry : zv::TableRef(reorderedArgs.table())) {
			zval key;
			keyOf(entry, &key);
			if (Z_TYPE(maxKey) == IS_UNDEF || zend_compare(&key, &maxKey) > 0) {
				ZVAL_COPY_VALUE(&maxKey, &key);
			}
		}
		HashTable *signature = Z_ARRVAL_P(signatureParameters.raw());
		for (zend_long j = 0;; j++) {
			zval jZv;
			ZVAL_LONG(&jZv, j);
			if (!(zend_compare(&jZv, &maxKey) < 0)) break;
			if (zend_hash_index_exists(reorderedArgs.table(), (zend_ulong) j)) continue;
			zval *parameter = zend_hash_index_find(signature, (zend_ulong) j);
			if (parameter == NULL) return zv::Val::null();
			ZVAL_DEREF(parameter);

			// we can only fill up optional parameters with default values
			bool optional = false;
			if (UNEXPECTED(!parameterIsOptional(parameter, optional))) return zv::Val();
			if (!optional) return zv::Val::null();

			zv::Val defaultValue = parameterGetDefaultValue(parameter);
			if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
			if (defaultValue.isNull()) {
				bool variadic = false;
				if (UNEXPECTED(!parameterIsVariadic(parameter, variadic))) return zv::Val();
				if (!variadic) {
					zv::Val name = parameterGetName(parameter);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
					zend_string *nameString = zval_get_string(name.raw());
					zend_string *message = zend_strpprintf(0, "An optional parameter $%s must have a default value", ZSTR_VAL(nameString));
					zend_string_release(nameString);
					zval messageZv;
					ZVAL_STR(&messageZv, message);
					zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &messageZv);
					zend_string_release(message);
					if (UNEXPECTED(exception.isUndef())) return zv::Val();
					zval raw = exception.take();
					zend_throw_exception_object(&raw);
					return zv::Val();
				}
				zval emptyKeys, emptyValues, constantArray;
				ZVAL_EMPTY_ARRAY(&emptyKeys);
				ZVAL_EMPTY_ARRAY(&emptyValues);
				if (UNEXPECTED(!pt_constant_array_type_new(&constantArray, &emptyKeys, &emptyValues))) return zv::Val();
				defaultValue = zv::Val::adopt(constantArray);
			}

			zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, defaultValue.raw());
			if (UNEXPECTED(typeExpr.isUndef())) return zv::Val();
			zv::Val filler = pt_type_new(PT_CLASS_ARG, 1, typeExpr.raw());
			if (UNEXPECTED(filler.isUndef())) return zv::Val();
			setKey(reorderedArgs, &jZv, std::move(filler));
		}

		reorderedArgs.separate();
		zend_hash_sort(reorderedArgs.table(), compareKeys, false);

		for (zv::ArrayEntry entry : zv::TableRef(appendArgs.table())) {
			reorderedArgs.push(zv::Ref(entry.value().raw()));
		}

		if (!zend_array_is_list(reorderedArgs.table())) {
			return arrayValues(reorderedArgs.raw());
		}

		return zv::Val(std::move(reorderedArgs));
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArgumentsNormalizer;

/* {{{ direct entries (support.h) */

zv::Val pt_arguments_normalizer_reorder_args(zval *parametersAcceptor, zval *callArgs)
{
	return ArgumentsNormalizer::reorderArgs(parametersAcceptor, callArgs);
}

zv::Val pt_arguments_normalizer_reorder_func_arguments(zval *parametersAcceptor, zval *functionCall)
{
	return ArgumentsNormalizer::reorderCallArguments(ArgumentsNormalizer::FUNC_CALL, parametersAcceptor, functionCall);
}

zv::Val pt_arguments_normalizer_reorder_method_arguments(zval *parametersAcceptor, zval *methodCall)
{
	return ArgumentsNormalizer::reorderCallArguments(ArgumentsNormalizer::METHOD_CALL, parametersAcceptor, methodCall);
}

zv::Val pt_arguments_normalizer_reorder_static_call_arguments(zval *parametersAcceptor, zval *staticCall)
{
	return ArgumentsNormalizer::reorderCallArguments(ArgumentsNormalizer::STATIC_CALL, parametersAcceptor, staticCall);
}

zv::Val pt_arguments_normalizer_reorder_new_arguments(zval *parametersAcceptor, zval *newExpr)
{
	return ArgumentsNormalizer::reorderCallArguments(ArgumentsNormalizer::NEW_CALL, parametersAcceptor, newExpr);
}

zv::Val pt_arguments_normalizer_reorder_call_user_func_arguments(zval *callUserFuncCall, zval *scope)
{
	return ArgumentsNormalizer::reorderCallUserFuncArguments(callUserFuncCall, scope);
}

zv::Val pt_arguments_normalizer_reorder_call_user_func_array_arguments(zval *callUserFuncArrayCall, zval *scope)
{
	return ArgumentsNormalizer::reorderCallUserFuncArrayArguments(callUserFuncArrayCall, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* a reorder*Arguments() handler: ($parametersAcceptor, <call class> $call) */
#define PT_AN_REORDER_CALL(kind, classIdx) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *parametersAcceptor, *call; \
		zend_class_entry *callCe = pt_class(classIdx); \
		if (UNEXPECTED(callCe == NULL)) RETURN_THROWS(); \
		ZEND_PARSE_PARAMETERS_START(2, 2) \
			Z_PARAM_OBJECT(parametersAcceptor) \
			Z_PARAM_OBJECT_OF_CLASS(call, callCe) \
		ZEND_PARSE_PARAMETERS_END(); \
		PT_RETURN_VAL(ArgumentsNormalizer::reorderCallArguments(ArgumentsNormalizer::kind, parametersAcceptor, call)); \
	}

/* a reorderCallUserFunc*Arguments() handler: (FuncCall $call, Scope $scope) */
#define PT_AN_REORDER_CALL_USER_FUNC(method) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *call, *scope; \
		zend_class_entry *callCe = pt_class(PT_CLASS_FUNC_CALL); \
		if (UNEXPECTED(callCe == NULL)) RETURN_THROWS(); \
		ZEND_PARSE_PARAMETERS_START(2, 2) \
			Z_PARAM_OBJECT_OF_CLASS(call, callCe) \
			Z_PARAM_OBJECT(scope) \
		ZEND_PARSE_PARAMETERS_END(); \
		PT_RETURN_VAL(ArgumentsNormalizer::method(call, scope)); \
	}

void pt_register_arguments_normalizer()
{
	pt_an_original_arg = zend_string_init_interned(PT_LC("originalArg"), 1);
	pt_an_callback = zend_string_init_interned(PT_LC("callback"), 1);
	pt_an_args = zend_string_init_interned(PT_LC("args"), 1);

	reg::Class cls("PHPStan\\Analyser\\ArgumentsNormalizer");
	ptdecl::ArgumentsNormalizer::declareClass(cls);
	cls.publicClassConstantString("ORIGINAL_ARG_ATTRIBUTE", "originalArg");

	cls.method(sigs::reorderCallUserFuncArguments, PT_AN_REORDER_CALL_USER_FUNC(reorderCallUserFuncArguments));
	cls.method(sigs::reorderCallUserFuncArrayArguments, PT_AN_REORDER_CALL_USER_FUNC(reorderCallUserFuncArrayArguments));
	cls.method(sigs::reorderFuncArguments, PT_AN_REORDER_CALL(FUNC_CALL, PT_CLASS_FUNC_CALL));
	cls.method(sigs::reorderMethodArguments, PT_AN_REORDER_CALL(METHOD_CALL, PT_CLASS_METHOD_CALL));
	cls.method(sigs::reorderStaticCallArguments, PT_AN_REORDER_CALL(STATIC_CALL, PT_CLASS_STATIC_CALL));
	cls.method(sigs::reorderNewArguments, PT_AN_REORDER_CALL(NEW_CALL, PT_CLASS_NEW));

	cls.method(sigs::reorderArgs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parametersAcceptor, *callArgs;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(parametersAcceptor)
			Z_PARAM_ARRAY(callArgs)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArgumentsNormalizer::reorderArgs(parametersAcceptor, callArgs));
	});

	cls.shadow(&pt_ce_arguments_normalizer);
}

/* }}} */
