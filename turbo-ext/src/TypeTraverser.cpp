/*
 * PHPStanTurbo\TypeTraverser — native implementation of PHPStan\Type\TypeTraverser.
 *
 * When the extension is active, PHPStan\Type\TypeTraverser is this class,
 * declared under that name at activation (final, like the twin). map()
 * builds one instance per traversal; the callback receives the instance's
 * [$this, 'traverseInternal'] array as its $traverse and Type::traverse()
 * receives [$this, 'mapInternal'] — the same PHP callables the twin hands
 * out, so a callback inspecting them sees no difference.
 */

#include "support.h"
#include "generated/TypeTraverser.h"

namespace slots = ptdecl::TypeTraverser::slot;
namespace sigs = ptdecl::TypeTraverser::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_type_traverser = NULL;

/* the method names of the bound callables, interned once at module
 * startup (the arrays hold them without a refcount) */
static zend_string *pt_tt_str_map_internal = NULL;
static zend_string *pt_tt_str_traverse_internal = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\TypeTraverser. State lives in the PHP object's $cb. */
class TypeTraverser
{
public:
	explicit TypeTraverser(zend_object *self) : self(self) {}

	/* map(): `$self = new self($cb); return $self->mapInternal($type);` —
	 * the instance lives as long as the callables handed out hold it;
	 * UNDEF = pending exception */
	static zv::Val map(zval *type, zval *cb)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_type_traverser) != SUCCESS)) return zv::Val();
		zv::Val holder = zv::Val::adopt(object);
		TypeTraverser traverser(Z_OBJ(object));
		traverser.construct(cb);
		return traverser.mapInternal(type);
	}

	/* __construct(): the callback as given. A TypeTraverserCallable is kept
	 * as the object itself and its traverse() called from mapInternal() —
	 * the twin wraps it in `static fn (Type $type, callable $traverse): Type
	 * => $cb->traverse($type, $traverse)`, and the object receives exactly
	 * the (Type, callable) pair either way. */
	void construct(zval *cb)
	{
		zv::ObjRef(self).propAtWrite(slots::cb, zv::Val::copyOf(zv::Ref(cb)));
	}

	/* ($this->cb)($type, [$this, 'traverseInternal']); UNDEF = pending
	 * exception */
	zv::Val mapInternal(zval *type) const
	{
		zval *cb = OBJ_PROP_NUM(self, slots::cb);
		zv::Val traverse = boundCallable(pt_tt_str_traverse_internal);
		zv::Args args{type, traverse.raw()};
		bool isTraverserCallable;
		if (UNEXPECTED(!pt_type_instanceof(cb, PT_CLASS_TYPE_TRAVERSER_CALLABLE, isTraverserCallable))) return zv::Val();
		zv::Val result = isTraverserCallable
			? pt_type_call(Z_OBJ_P(cb), PT_LC("traverse"), 2, args)
			: pt_type_call_callable(cb, 2, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		/* the twin's `: Type` return type */
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(result.raw(), PT_CLASS_TYPE, isType))) return zv::Val();
		if (UNEXPECTED(!isType)) {
			zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
			zend_type_error("%s::mapInternal(): Return value must be of type %s, %s returned", ZSTR_VAL(pt_ce_type_traverser->name), typeCe != NULL ? ZSTR_VAL(typeCe->name) : "PHPStan\\Type\\Type", zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}

	/* $type->traverse([$this, 'mapInternal']); UNDEF = pending exception */
	zv::Val traverseInternal(zval *type) const
	{
		zv::Val map = boundCallable(pt_tt_str_map_internal);
		return pt_type_call(Z_OBJ_P(type), PT_LC("traverse"), 1, map.raw());
	}

private:
	zend_object *self;

	/* [$this, '<method>'] */
	zv::Val boundCallable(zend_string *method) const
	{
		zv::Arr callable = zv::Arr::create(2);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		callable.push(zv::Ref(&selfZv));
		zval methodZv;
		ZVAL_INTERNED_STR(&methodZv, method);
		callable.push(zv::Ref(&methodZv));
		return zv::Val(std::move(callable));
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeTraverser;

/* {{{ exported helpers */

bool pt_type_traverser_map(zval *out, zval *type, zval *cb)
{
	zv::Val result = TypeTraverser::map(type, cb);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_type_traverser_traverse(zval *out, zval *traverse, zval *type)
{
	zval *callable = traverse;
	ZVAL_DEREF(callable);
	zv::Val result;
	if (Z_TYPE_P(callable) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(callable)) == 2) {
		zval *object = zend_hash_index_find(Z_ARRVAL_P(callable), 0);
		zval *method = zend_hash_index_find(Z_ARRVAL_P(callable), 1);
		if (object != NULL && method != NULL) {
			ZVAL_DEREF(object);
			ZVAL_DEREF(method);
			if (Z_TYPE_P(object) == IS_OBJECT && Z_OBJCE_P(object) == pt_ce_type_traverser
				&& Z_TYPE_P(method) == IS_STRING && zend_string_equals(Z_STR_P(method), pt_tt_str_traverse_internal)) {
				result = TypeTraverser(Z_OBJ_P(object)).traverseInternal(type);
				if (UNEXPECTED(result.isUndef())) return false;
				result.intoReturnValue(out);
				return true;
			}
		}
	}
	result = pt_type_call_callable(traverse, 1, type);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* the twin's `TypeTraverserCallable|callable $cb` parameter check; false
 * with a TypeError pending */
static bool pt_tt_check_cb(zval *cb, uint32_t argNum)
{
	bool isTraverserCallable;
	if (UNEXPECTED(!pt_type_instanceof(cb, PT_CLASS_TYPE_TRAVERSER_CALLABLE, isTraverserCallable))) return false;
	if (isTraverserCallable || zend_is_callable(cb, 0, NULL)) return true;
	zend_class_entry *iface = pt_class(PT_CLASS_TYPE_TRAVERSER_CALLABLE);
	zend_argument_type_error(argNum, "must be of type %s|callable, %s given", iface != NULL ? ZSTR_VAL(iface->name) : "PHPStan\\Type\\TypeTraverserCallable", zend_zval_value_name(cb));
	return false;
}

void pt_register_type_traverser()
{
	pt_tt_str_map_internal = zend_string_init_interned("mapInternal", sizeof("mapInternal") - 1, 1);
	pt_tt_str_traverse_internal = zend_string_init_interned("traverseInternal", sizeof("traverseInternal") - 1, 1);

	/* `TypeTraverserCallable|callable $cb` — a class name next to the
	 * callable bit, as the compiler encodes that union */

	reg::Class cls("PHPStan\\Type\\TypeTraverser");
	ptdecl::TypeTraverser::declareClass(cls);
	/* "cb" must stay the first declared property (OBJ_PROP_NUM slot 0) */
	ptdecl::TypeTraverser::declareProperties(cls);

	cls.method(sigs::map, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *cb;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, cb)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tt_check_cb(cb, 2))) RETURN_THROWS();
		PT_RETURN_VAL(TypeTraverser::map(type, cb));
	});

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cb;
		if (!zp::parse<zp::Zval>(execute_data, cb)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tt_check_cb(cb, 1))) RETURN_THROWS();
		TypeTraverser(Z_OBJ_P(ZEND_THIS)).construct(cb);
	});

	cls.method(sigs::mapInternal, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeTraverser(Z_OBJ_P(ZEND_THIS)).mapInternal(type));
	});

	cls.method(sigs::traverseInternal, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TypeTraverser(Z_OBJ_P(ZEND_THIS)).traverseInternal(type));
	});

	cls.shadow(&pt_ce_type_traverser);
}

/* }}} */
