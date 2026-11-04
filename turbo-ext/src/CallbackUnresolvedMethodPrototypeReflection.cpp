/*
 * PHPStanTurbo\CallbackUnresolvedMethodPrototypeReflection — native
 * implementation of PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection.
 *
 * Declared under the twin's real name at activation (final, like the
 * twin): the lazy method prototype StaticType, MixedType and the
 * maybe-object types build, whose getTransformedMethod() rewrites the
 * method's signature through the callback it was given. The logic lives
 * in the handle class below, mirroring
 * src/Reflection/Type/CallbackUnresolvedMethodPrototypeReflection.php
 * method for method; transformMethodWithStaticType() is the shared
 * pt_prototype_resolved_method() (TypeTraits.cpp) in its Callback flavour
 * (a transformed type is reused for an equal one, a $this-returning
 * variant narrows the self-out type). State lives in the six declared
 * property slots — the twin's, in its order.
 */

#include "support.h"
#include "generated/CallbackUnresolvedMethodPrototypeReflection.h"

namespace slots = ptdecl::CallbackUnresolvedMethodPrototypeReflection::slot;
namespace sigs = ptdecl::CallbackUnresolvedMethodPrototypeReflection::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_callback_unresolved_method_prototype_reflection = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection.
 * State lives in the PHP object's $transformStaticTypeCallback,
 * $transformedMethod, $cachedDoNotResolveTemplateTypeMapToBounds and the
 * promoted $methodReflection, $resolvedDeclaringClass,
 * $resolveTemplateTypeMapToBounds. */
class CallbackUnresolvedMethodPrototypeReflection
{
public:
	explicit CallbackUnresolvedMethodPrototypeReflection(zend_object *self) : self(self) {}

	/* __construct(private ExtendedMethodReflection $methodReflection,
	 * private ClassReflection $resolvedDeclaringClass, private bool
	 * $resolveTemplateTypeMapToBounds, callable $transformStaticTypeCallback)
	 * { $this->transformStaticTypeCallback = $transformStaticTypeCallback; } */
	void construct(zval *methodReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *callback) const
	{
		write(slots::methodReflection, zv::Val::copyOf(zv::Ref(methodReflection)));
		write(slots::resolvedDeclaringClass, zv::Val::copyOf(zv::Ref(resolvedDeclaringClass)));
		write(slots::resolveTemplateTypeMapToBounds, zv::Val::boolean(resolveTemplateTypeMapToBounds));
		write(slots::transformStaticTypeCallback, zv::Val::copyOf(zv::Ref(callback)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *methodReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *callback)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_callback_unresolved_method_prototype_reflection) != SUCCESS)) return zv::Val();
		CallbackUnresolvedMethodPrototypeReflection(Z_OBJ(object)).construct(methodReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback);
		return zv::Val::adopt(object);
	}

	/* doNotResolveTemplateTypeMapToBounds(): the memoized copy with
	 * $resolveTemplateTypeMapToBounds = false; UNDEF = pending exception */
	zv::Val doNotResolveTemplateTypeMapToBounds() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedDoNotResolveTemplateTypeMapToBounds);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		zval *resolvedDeclaringClass = methodReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		if (UNEXPECTED(resolvedDeclaringClass == NULL)) return zv::Val();
		zv::Val created = create(methodReflection, resolvedDeclaringClass, false, OBJ_PROP_NUM(self, slots::transformStaticTypeCallback));
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		write(slots::cachedDoNotResolveTemplateTypeMapToBounds, zv::Val::copyOf(zv::Ref(created.raw())));
		return created;
	}

	/* getNakedMethod(): $this->methodReflection; UNDEF = pending exception */
	zv::Val getNakedMethod() const
	{
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		return methodReflection != NULL ? zv::Val::copyOf(zv::Ref(methodReflection)) : zv::Val();
	}

	/* getTransformedMethod(): the memo, computed once through
	 * pt_prototype_resolved_method(); UNDEF = pending exception */
	zv::Val getTransformedMethod() const
	{
		zval *transformedMethod = OBJ_PROP_NUM(self, slots::transformedMethod);
		if (EXPECTED(Z_TYPE_P(transformedMethod) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(transformedMethod));
		zval *resolvedDeclaringClass = slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass");
		zval *methodReflection = resolvedDeclaringClass != NULL ? slot(slots::methodReflection, "methodReflection") : NULL;
		zval *resolveToBounds = methodReflection != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		/* $method->getAsserts()->mapTypes($this->transformStaticTypeCallback) */
		PrototypeTransformer transformer = { transformStaticType, self };
		zv::Val result = pt_prototype_resolved_method(PT_PROTOTYPE_CALLBACK, transformer, Z_OBJ_P(resolvedDeclaringClass), Z_OBJ_P(methodReflection), zend_is_true(resolveToBounds), OBJ_PROP_NUM(self, slots::transformStaticTypeCallback));
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		write(slots::transformedMethod, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* withCalledOnType(Type $type): new CalledOnTypeUnresolvedMethodPrototypeReflection(...);
	 * UNDEF = pending exception */
	zv::Val withCalledOnType(zval *type) const
	{
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		zval *resolvedDeclaringClass = methodReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *resolveToBounds = resolvedDeclaringClass != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		zv::Args args{methodReflection, resolvedDeclaringClass, resolveToBounds, type};
		return pt_called_on_type_unresolved_method_prototype_reflection_new(4, args);
	}

private:
	zend_object *self;

	void write(uint32_t index, zv::Val value) const
	{
		zval *slot = OBJ_PROP_NUM(self, index);
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		zval v = value.take();
		ZVAL_COPY_VALUE(slot, &v);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	zval *slot(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_callback_unresolved_method_prototype_reflection->name), propertyName);
			return NULL;
		}
		return value;
	}

	/* transformStaticType($type): `$callback = $this->transformStaticTypeCallback;
	 * return $callback($type);` with the twin's `: Type` return type held;
	 * UNDEF = pending exception */
	static zv::Val transformStaticType(void *context, zval *type)
	{
		zend_object *self = (zend_object *) context;
		zv::Val result = pt_type_call_callable(OBJ_PROP_NUM(self, slots::transformStaticTypeCallback), 1, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(result.raw(), PT_CLASS_TYPE, isType))) return zv::Val();
		if (UNEXPECTED(!isType)) {
			zend_type_error("%s::transformStaticType(): Return value must be of type PHPStan\\Type\\Type, %s returned", ZSTR_VAL(pt_ce_callback_unresolved_method_prototype_reflection->name), zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CallbackUnresolvedMethodPrototypeReflection;

/* {{{ exported helpers: the shadowing class for native callers */

/* new CallbackUnresolvedMethodPrototypeReflection(...$argv) over values as
 * PHP code hands them (borrowed): directly when they already have the
 * parameter types (the callback an object — a Closure or a native callback
 * holder), through the constructor's parameter parsing otherwise; UNDEF =
 * pending exception */
zv::Val pt_callback_unresolved_method_prototype_reflection_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 4 && Z_TYPE(argv[0]) == IS_OBJECT && Z_TYPE(argv[1]) == IS_OBJECT && (Z_TYPE(argv[2]) == IS_TRUE || Z_TYPE(argv[2]) == IS_FALSE) && Z_TYPE(argv[3]) == IS_OBJECT && zend_is_callable(&argv[3], 0, NULL))) {
		return CallbackUnresolvedMethodPrototypeReflection::create(&argv[0], &argv[1], Z_TYPE(argv[2]) == IS_TRUE, &argv[3]);
	}
	return pt_type_new_ce(pt_ce_callback_unresolved_method_prototype_reflection, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_THIS CallbackUnresolvedMethodPrototypeReflection(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_callback_unresolved_method_prototype_reflection)
{
	static const char *selfClass = "PHPStan\\Reflection\\Type\\CallbackUnresolvedMethodPrototypeReflection";
	static const char *extendedMethodReflectionClass = "PHPStan\\Reflection\\ExtendedMethodReflection";

	reg::Class cls("PHPStan\\Reflection\\Type\\CallbackUnresolvedMethodPrototypeReflection");
	ptdecl::CallbackUnresolvedMethodPrototypeReflection::declareClass(cls);
	/* the twin's slots in its order (OBJ_PROP_NUM): the untyped callback,
	 * the two memos defaulting to null, then the promoted parameters */
	cls.privateNullProperty("transformStaticTypeCallback");
	cls.privateTypedClassPropertyDefaultNull("transformedMethod", extendedMethodReflectionClass);
	cls.privateTypedClassPropertyDefaultNull("cachedDoNotResolveTemplateTypeMapToBounds", selfClass);
	cls.privateTypedClassProperty("methodReflection", extendedMethodReflectionClass, false);
	cls.privateTypedClassProperty("resolvedDeclaringClass", ptcls::classReflection, false);
	cls.privateTypedProperty("resolveTemplateTypeMapToBounds", MAY_BE_BOOL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *methodReflection, *resolvedDeclaringClass, *callback;
		bool resolveTemplateTypeMapToBounds;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool, zp::Zval>(execute_data, methodReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback)) RETURN_THROWS();
		/* the twin's `callable` parameter type */
		if (UNEXPECTED(!zend_is_callable(callback, 0, NULL))) {
			zend_argument_type_error(4, "must be of type callable, %s given", zend_zval_value_name(callback));
			RETURN_THROWS();
		}
		PT_THIS.construct(methodReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback);
	});

	cls.method<&CallbackUnresolvedMethodPrototypeReflection::doNotResolveTemplateTypeMapToBounds>(sigs::doNotResolveTemplateTypeMapToBounds);
	cls.op<PT_OP_DO_NOT_RESOLVE_TEMPLATE_TYPE_MAP_TO_BOUNDS, &CallbackUnresolvedMethodPrototypeReflection::doNotResolveTemplateTypeMapToBounds>();

	cls.method<&CallbackUnresolvedMethodPrototypeReflection::getNakedMethod>(sigs::getNakedMethod);
	cls.op<PT_OP_GET_NAKED_METHOD, &CallbackUnresolvedMethodPrototypeReflection::getNakedMethod>();

	cls.method<&CallbackUnresolvedMethodPrototypeReflection::getTransformedMethod>(sigs::getTransformedMethod);
	cls.op<PT_OP_GET_TRANSFORMED_METHOD, &CallbackUnresolvedMethodPrototypeReflection::getTransformedMethod>();

	cls.method<&CallbackUnresolvedMethodPrototypeReflection::withCalledOnType, zp::Obj>(sigs::withCalledOnType);

	cls.shadow(&pt_ce_callback_unresolved_method_prototype_reflection);
}

/* }}} */
