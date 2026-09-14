/*
 * PHPStanTurbo\CallbackUnresolvedPropertyPrototypeReflection — native
 * implementation of PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection.
 *
 * Declared under the twin's real name at activation (final, like the
 * twin): the lazy property prototype StaticType, MixedType, ObjectShapeType
 * and the maybe-object types build, whose getTransformedProperty()
 * rewrites the property's types through the callback it was given. The
 * logic lives in the handle class below, mirroring
 * src/Reflection/Type/CallbackUnresolvedPropertyPrototypeReflection.php
 * method for method; transformPropertyWithStaticType() is the shared
 * pt_prototype_resolved_property() (TypeTraits.cpp) in its Callback
 * flavour (a transformed type is reused for an equal one). State lives in
 * the six declared property slots — the twin's, in its order.
 */

#include "support.h"
#include "generated/CallbackUnresolvedPropertyPrototypeReflection.h"

namespace slots = ptdecl::CallbackUnresolvedPropertyPrototypeReflection::slot;
namespace sigs = ptdecl::CallbackUnresolvedPropertyPrototypeReflection::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_callback_unresolved_property_prototype_reflection = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection.
 * State lives in the PHP object's $transformStaticTypeCallback,
 * $transformedProperty, $cachedDoNotResolveTemplateTypeMapToBounds and the
 * promoted $propertyReflection, $resolvedDeclaringClass,
 * $resolveTemplateTypeMapToBounds. */
class CallbackUnresolvedPropertyPrototypeReflection
{
public:
	explicit CallbackUnresolvedPropertyPrototypeReflection(zend_object *self) : self(self) {}

	/* __construct(private ExtendedPropertyReflection $propertyReflection,
	 * private ClassReflection $resolvedDeclaringClass, private bool
	 * $resolveTemplateTypeMapToBounds, callable $transformStaticTypeCallback) */
	void construct(zval *propertyReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *callback) const
	{
		write(slots::propertyReflection, zv::Val::copyOf(zv::Ref(propertyReflection)));
		write(slots::resolvedDeclaringClass, zv::Val::copyOf(zv::Ref(resolvedDeclaringClass)));
		write(slots::resolveTemplateTypeMapToBounds, zv::Val::boolean(resolveTemplateTypeMapToBounds));
		write(slots::transformStaticTypeCallback, zv::Val::copyOf(zv::Ref(callback)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *propertyReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *callback)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_callback_unresolved_property_prototype_reflection) != SUCCESS)) return zv::Val();
		CallbackUnresolvedPropertyPrototypeReflection(Z_OBJ(object)).construct(propertyReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback);
		return zv::Val::adopt(object);
	}

	/* doNotResolveTemplateTypeMapToBounds(): the memoized copy with
	 * $resolveTemplateTypeMapToBounds = false; UNDEF = pending exception */
	zv::Val doNotResolveTemplateTypeMapToBounds() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedDoNotResolveTemplateTypeMapToBounds);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		zval *resolvedDeclaringClass = propertyReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		if (UNEXPECTED(resolvedDeclaringClass == NULL)) return zv::Val();
		zv::Val created = create(propertyReflection, resolvedDeclaringClass, false, OBJ_PROP_NUM(self, slots::transformStaticTypeCallback));
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		write(slots::cachedDoNotResolveTemplateTypeMapToBounds, zv::Val::copyOf(zv::Ref(created.raw())));
		return created;
	}

	/* getNakedProperty(): $this->propertyReflection; UNDEF = pending exception */
	zv::Val getNakedProperty() const
	{
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		return propertyReflection != NULL ? zv::Val::copyOf(zv::Ref(propertyReflection)) : zv::Val();
	}

	/* getTransformedProperty(): the memo, computed once through
	 * pt_prototype_resolved_property(); UNDEF = pending exception */
	zv::Val getTransformedProperty() const
	{
		zval *transformedProperty = OBJ_PROP_NUM(self, slots::transformedProperty);
		if (EXPECTED(Z_TYPE_P(transformedProperty) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(transformedProperty));
		zval *resolvedDeclaringClass = slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass");
		zval *propertyReflection = resolvedDeclaringClass != NULL ? slot(slots::propertyReflection, "propertyReflection") : NULL;
		zval *resolveToBounds = propertyReflection != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		PrototypeTransformer transformer = { transformStaticType, self };
		zv::Val result = pt_prototype_resolved_property(PT_PROTOTYPE_CALLBACK, transformer, Z_OBJ_P(resolvedDeclaringClass), Z_OBJ_P(propertyReflection), zend_is_true(resolveToBounds));
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		write(slots::transformedProperty, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* withFechedOnType(Type $type): new CalledOnTypeUnresolvedPropertyPrototypeReflection(...);
	 * UNDEF = pending exception */
	zv::Val withFechedOnType(zval *type) const
	{
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		zval *resolvedDeclaringClass = propertyReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *resolveToBounds = resolvedDeclaringClass != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		zv::Args args{propertyReflection, resolvedDeclaringClass, resolveToBounds, type};
		return pt_called_on_type_unresolved_property_prototype_reflection_new(4, args);
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
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_callback_unresolved_property_prototype_reflection->name), propertyName);
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
			zend_type_error("%s::transformStaticType(): Return value must be of type PHPStan\\Type\\Type, %s returned", ZSTR_VAL(pt_ce_callback_unresolved_property_prototype_reflection->name), zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CallbackUnresolvedPropertyPrototypeReflection;

/* {{{ exported helpers: the shadowing class for native callers */

/* new CallbackUnresolvedPropertyPrototypeReflection(...$argv) over values
 * as PHP code hands them (borrowed): directly when they already have the
 * parameter types (the callback an object — a Closure or a native callback
 * holder), through the constructor's parameter parsing otherwise; UNDEF =
 * pending exception */
zv::Val pt_callback_unresolved_property_prototype_reflection_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 4 && Z_TYPE(argv[0]) == IS_OBJECT && Z_TYPE(argv[1]) == IS_OBJECT && (Z_TYPE(argv[2]) == IS_TRUE || Z_TYPE(argv[2]) == IS_FALSE) && Z_TYPE(argv[3]) == IS_OBJECT && zend_is_callable(&argv[3], 0, NULL))) {
		return CallbackUnresolvedPropertyPrototypeReflection::create(&argv[0], &argv[1], Z_TYPE(argv[2]) == IS_TRUE, &argv[3]);
	}
	return pt_type_new_ce(pt_ce_callback_unresolved_property_prototype_reflection, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_THIS CallbackUnresolvedPropertyPrototypeReflection(Z_OBJ_P(ZEND_THIS))

void pt_register_callback_unresolved_property_prototype_reflection()
{
	static const char *selfClass = "PHPStan\\Reflection\\Type\\CallbackUnresolvedPropertyPrototypeReflection";
	static const char *extendedPropertyReflectionClass = "PHPStan\\Reflection\\ExtendedPropertyReflection";

	reg::Class cls("PHPStan\\Reflection\\Type\\CallbackUnresolvedPropertyPrototypeReflection");
	ptdecl::CallbackUnresolvedPropertyPrototypeReflection::declareClass(cls);
	cls.privateNullProperty("transformStaticTypeCallback");
	cls.privateTypedClassPropertyDefaultNull("transformedProperty", extendedPropertyReflectionClass);
	cls.privateTypedClassPropertyDefaultNull("cachedDoNotResolveTemplateTypeMapToBounds", selfClass);
	cls.privateTypedClassProperty("propertyReflection", extendedPropertyReflectionClass, false);
	cls.privateTypedClassProperty("resolvedDeclaringClass", ptcls::classReflection, false);
	cls.privateTypedProperty("resolveTemplateTypeMapToBounds", MAY_BE_BOOL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *propertyReflection, *resolvedDeclaringClass, *callback;
		bool resolveTemplateTypeMapToBounds;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool, zp::Zval>(execute_data, propertyReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback)) RETURN_THROWS();
		if (UNEXPECTED(!zend_is_callable(callback, 0, NULL))) {
			zend_argument_type_error(4, "must be of type callable, %s given", zend_zval_value_name(callback));
			RETURN_THROWS();
		}
		PT_THIS.construct(propertyReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, callback);
	});

	cls.method<&CallbackUnresolvedPropertyPrototypeReflection::doNotResolveTemplateTypeMapToBounds>(sigs::doNotResolveTemplateTypeMapToBounds);

	cls.method<&CallbackUnresolvedPropertyPrototypeReflection::getNakedProperty>(sigs::getNakedProperty);

	cls.method<&CallbackUnresolvedPropertyPrototypeReflection::getTransformedProperty>(sigs::getTransformedProperty);

	cls.method<&CallbackUnresolvedPropertyPrototypeReflection::withFechedOnType, zp::Obj>(sigs::withFechedOnType);

	cls.shadow(&pt_ce_callback_unresolved_property_prototype_reflection);
}

/* }}} */
