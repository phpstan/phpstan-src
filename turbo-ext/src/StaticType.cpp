/*
 * PHPStanTurbo\StaticType — native implementation of PHPStan\Type\StaticType.
 *
 * Declared as PHPStan\Type\StaticType itself at activation: not final (the
 * native ThisType and GenericStaticType extend it, and so may an extension's
 * PHP class — their constructors call parent::__construct(), so the
 * constructor is a proper method), implementing PHPStan\Type\TypeWithClassName
 * and PHPStan\Type\SubtractableType. State is the twin's `private ?Type
 * $subtractedType`, the `private ?ObjectType $staticObjectType` memo, the
 * `private string $baseClass`, the `private array $methodCache` and the
 * promoted `private ClassReflection $classReflection`, declared typed
 * property slots in the twin's declaration order, so the std object
 * handlers do GC/clone and a subclass's own properties follow them. The
 * three traits the twin is composed of come from the shared registrars in
 * TypeTraits.cpp, run after the class's own methods.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (GenericStaticType's
 * getStaticObjectType(), ThisType's and GenericStaticType's
 * changeBaseClass() and changeSubtractedType()) — with a direct C++ call
 * when the method is StaticType's own. Almost everything delegates to
 * `$this->getStaticObjectType()`, an ObjectType the twin builds through the
 * class map and memoizes.
 *
 * The closures the twin creates — `fn (Type $type): Type =>
 * $this->transformStaticType($type, $scope)` for the prototype
 * reflections, the TypeTraverser::map() callback inside it, the
 * RecursionGuard::run() thunk, and the TemplateTypeMap::map() callback of
 * getStaticObjectType() — are Closures over the methods of the internal
 * PHPStanTurbo\StaticTypeCallbacks holder, which keeps the captured values
 * in its slots (an internal detail with no PHP twin, like the generalize()
 * holder in TypeTraits.cpp).
 */

#include "TypeTraits.h"
#include "generated/StaticType.h"

namespace slots = ptdecl::StaticType::slot;
namespace sigs = ptdecl::StaticType::sig;

zend_class_entry *pt_ce_static_type = nullptr;

/* the callback holder and its methods */
static zend_class_entry *pt_ce_static_type_callbacks = nullptr;
static zend_function *pt_static_type_callbacks_transform = nullptr;
static zend_function *pt_static_type_callbacks_map = nullptr;
static zend_function *pt_static_type_callbacks_guard = nullptr;
static zend_function *pt_static_type_callbacks_to_argument = nullptr;

/* the handlers a $this-call is checked against before the direct path */
static void ZEND_FASTCALL stGetStaticObjectType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stGetClassReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stGetSubtractedType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stGetClassName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stGetAncestorWithClassName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stChangeBaseClass(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stGetClassStringType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL stSubtract(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StaticType. State lives in the PHP object's slots. */
class StaticType
{
public:
	explicit StaticType(zend_object *self) : self(self) {}

	/* __construct(private ClassReflection $classReflection, ?Type
	 * $subtractedType = null): a NeverType subtracted type is dropped, the
	 * base class name is the reflection's; both borrowed, $subtractedType
	 * NULL for null. An exception from getName() leaves the slots as PHP
	 * would: the reflection and the subtracted type written, the name not. */
	void construct(zval *classReflection, zval *subtractedType)
	{
		writeSlot(slots::classReflection, classReflection);
		if (subtractedType != NULL && instanceof_function(Z_OBJCE_P(subtractedType), pt_ce_never_type)) {
			subtractedType = NULL;
		}
		if (subtractedType == NULL) {
			zval null = {};
			ZVAL_NULL(&null);
			writeSlot(slots::subtractedType, &null);
		} else {
			writeSlot(slots::subtractedType, subtractedType);
		}
		zv::Val name = pt_type_call(Z_OBJ_P(classReflection), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return;
		if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::getName() must return string", ZSTR_VAL(Z_OBJCE_P(classReflection)->name));
			return;
		}
		writeSlot(slots::baseClass, name.raw());
	}

	/* new self($classReflection, $subtractedType) — exactly the class, as
	 * the twin's `new self` sites spell it; UNDEF = pending exception */
	static zv::Val create(zval *classReflection, zval *subtractedType = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_static_type) != SUCCESS)) return zv::Val();
		StaticType(Z_OBJ(object)).construct(classReflection, subtractedType);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the slots of StaticType's scope (borrowed); NULL with an Error
	 * pending when the constructor never ran */
	[[nodiscard]] zval *subtractedType() const { return slot(self, slots::subtractedType, "subtractedType"); }
	zval *classReflection() const { return slot(self, slots::classReflection, "classReflection"); }
	zval *baseClass() const { return slot(self, slots::baseClass, "baseClass"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_static_type, name); }

	/* $this->baseClass */
	zv::Val getClassName() const
	{
		zval *base = baseClass();
		if (UNEXPECTED(base == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(base));
	}

	zv::Val getClassReflection() const
	{
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(reflection));
	}

	/* $this->changeBaseClass() of the static object type's ancestor's
	 * reflection, null without an ancestor or a reflection; UNDEF = pending
	 * exception */
	zv::Val getAncestorWithClassName(zval *className) const
	{
		zv::Val staticObject = thisStaticObjectType();
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		zv::Val ancestor = pt_type_call(Z_OBJ_P(staticObject.raw()), PT_LC("getancestorwithclassname"), 1, className);
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		if (ancestor.isNull()) return zv::Val::null();
		if (UNEXPECTED(!zv::Ref(ancestor.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getAncestorWithClassName() must return an object or null");
			return zv::Val();
		}
		zv::Val classReflection = pt_type_call(Z_OBJ_P(ancestor.raw()), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return zv::Val::null();
		return thisChangeBaseClass(classReflection.raw());
	}

	/* the memoized ObjectType of the base class: a GenericObjectType over
	 * the reflection's active template arguments for a generic class, a
	 * plain ObjectType otherwise; UNDEF = pending exception */
	zv::Val getStaticObjectType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::staticObjectType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		bool generic;
		if (UNEXPECTED(!reflectionFlag(reflection, PT_LC("isgeneric"), generic))) return zv::Val();
		zv::Val name = pt_type_call(Z_OBJ_P(reflection), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val objectType;
		if (generic) {
			/* $typeMap = $this->classReflection->getActiveTemplateTypeMap()->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::toArgument($type)) */
			zv::Val activeTypeMap = pt_type_call(Z_OBJ_P(reflection), PT_LC("getactivetemplatetypemap"), 0, NULL);
			if (UNEXPECTED(activeTypeMap.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(activeTypeMap.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getActiveTemplateTypeMap() must return an object");
				return zv::Val();
			}
			zv::Val toArgument = pt_type_closure_over(pt_static_type_callbacks_to_argument, pt_ce_static_type_callbacks, NULL);
			zv::Val typeMap = pt_type_call(Z_OBJ_P(activeTypeMap.raw()), PT_LC("map"), 1, toArgument.raw());
			if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
			zv::Val varianceMap = pt_type_call(Z_OBJ_P(reflection), PT_LC("getcallsitevariancemap"), 0, NULL);
			if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
			zv::Val types = pt_type_call(Z_OBJ_P(reflection), PT_LC("typemaptolist"), 1, typeMap.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			zv::Val variances = pt_type_call(Z_OBJ_P(reflection), PT_LC("variancemaptolist"), 1, varianceMap.raw());
			if (UNEXPECTED(variances.isUndef())) return zv::Val();
			/* new GenericObjectType($name, $types, $this->subtractedType, variances: $variances)
			 * — the skipped $classReflection at its default null */
			zval genericRaw;
			if (UNEXPECTED(!pt_generic_object_type_new(&genericRaw, Z_STR_P(name.raw()), types.raw(), subtracted, NULL, variances.raw()))) return zv::Val();
			objectType = zv::Val::adopt(genericRaw);
		} else {
			/* new ObjectType($name, $this->subtractedType, $this->classReflection) */
			zval objectRaw;
			if (UNEXPECTED(!pt_object_type_new(&objectRaw, Z_STR_P(name.raw()), subtracted, reflection))) return zv::Val();
			objectType = zv::Val::adopt(objectRaw);
		}
		if (UNEXPECTED(objectType.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::staticObjectType, zv::Val::copyOf(zv::Ref(objectType.raw())));
		return objectType;
	}

	/* $this->getStaticObjectType()->method(...$args); UNDEF = pending
	 * exception */
	zv::Val delegate(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zv::Val staticObject = thisStaticObjectType();
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(staticObject.raw()), lcname, len, argc, argv);
	}

	/* the CompoundType callback; no for anything but an instance of the
	 * object's own class; else the static object types' answer; UNDEF =
	 * pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		/* $type instanceof static — the object's own class */
		if (!instanceof_function(Z_OBJCE_P(type), self->ce)) return pt_type_accepts_result(PT_TRI_NO);
		zv::Val typeStaticObject = pt_type_call(Z_OBJ_P(type), PT_LC("getstaticobjecttype"), 0, NULL);
		if (UNEXPECTED(typeStaticObject.isUndef())) return zv::Val();
		zv::Args args{typeStaticObject.raw(), strictTypes};
		return delegate(PT_LC("accepts"), 2, args);
	}

	/* the static object type's answer for another StaticType; maybe for
	 * object; for an ObjectType the answer held to maybe unless yes for a
	 * final class; the CompoundType callback; no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_static_type)) return delegate(PT_LC("issupertypeof"), 1, type);
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		bool objectType;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_object_type, objectType))) return zv::Val();
		if (objectType) {
			zv::Val result = delegate(PT_LC("issupertypeof"), 1, type);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(result.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) {
				zv::Val classReflection = pt_type_call(Z_OBJ_P(type), PT_LC("getclassreflection"), 0, NULL);
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				if (!classReflection.isNull()) {
					if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
						zend_type_error("phpstan_turbo: getClassReflection() must return an object or null");
						return zv::Val();
					}
					bool isFinal;
					if (UNEXPECTED(!reflectionFlag(classReflection.raw(), PT_LC("isfinal"), isFinal))) return zv::Val();
					if (isFinal) return result;
				}
			}
			return andMaybe(std::move(result));
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}
		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $result->and(IsSuperTypeOfResult::createMaybe()); UNDEF = pending
	 * exception */
	static zv::Val andMaybe(zv::Val result)
	{
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, maybe.raw());
	}

	/* the same class (get_class($type) === static::class) with equal static
	 * object types; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (Z_OBJCE_P(type) != self->ce) {
			out = false;
			return true;
		}
		zv::Val typeStaticObject = pt_type_call(Z_OBJ_P(type), PT_LC("getstaticobjecttype"), 0, NULL);
		if (UNEXPECTED(typeStaticObject.isUndef())) return false;
		zv::Val equal = delegate(PT_LC("equals"), 1, typeStaticObject.raw());
		if (UNEXPECTED(equal.isUndef())) return false;
		out = zend_is_true(equal.raw());
		return true;
	}

	/* sprintf('static(%s)', $this->getStaticObjectType()->describe($level)) */
	zv::Val describe(zval *level) const { return describeAs("static(", sizeof("static(") - 1, level); }

	/* the described static object type wrapped in a prefix and ')' — the
	 * shape ThisType's describe() shares; UNDEF = pending exception */
	zv::Val describeAs(const char *prefix, size_t prefixLen, zval *level) const
	{
		zv::Val inner = delegate(PT_LC("describe"), 1, level);
		if (UNEXPECTED(inner.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(inner.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		smart_str_appendl(&description, prefix, prefixLen);
		smart_str_append(&description, zv::Ref(inner.raw()).asString());
		smart_str_appendc(&description, ')');
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* new GenericClassStringType($this) */
	zv::Val getClassStringType() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_type_new_ce(pt_ce_generic_class_string_type, 1, &selfZv);
	}

	/* the prototype reflection over the static object type's naked
	 * member, declared by the ancestor of the member's declaring class
	 * (the declaring class itself without one), transformed through
	 * transformStaticType(); UNDEF = pending exception */
	zv::Val unresolvedPrototype(const char *prototypeLcname, size_t prototypeLen, bool isMethod, zval *name, zval *scope) const
	{
		zv::Val staticObject = thisStaticObjectType();
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		zval args[4];
		ZVAL_COPY_VALUE(&args[0], name);
		ZVAL_COPY_VALUE(&args[1], scope);
		zv::Val prototype = pt_type_call(Z_OBJ_P(staticObject.raw()), prototypeLcname, prototypeLen, 2, args);
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
			return zv::Val();
		}
		zv::Val naked = isMethod
			? pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("getnakedmethod"), 0, NULL)
			: pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("getnakedproperty"), 0, NULL);
		if (UNEXPECTED(naked.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(naked.raw()).isObject())) {
			zend_type_error("phpstan_turbo: the naked member must be an object");
			return zv::Val();
		}
		/* $ancestor = $this->getAncestorWithClassName($naked->getDeclaringClass()->getName()) */
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(naked.raw()), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(declaringClass.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getDeclaringClass() must return an object");
			return zv::Val();
		}
		zv::Val declaringName = pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
		zv::Val ancestor = thisGetAncestorWithClassName(declaringName.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		zv::Val classReflection = zv::Val::null();
		if (!ancestor.isNull()) {
			if (UNEXPECTED(!zv::Ref(ancestor.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getAncestorWithClassName() must return an object or null");
				return zv::Val();
			}
			classReflection = pt_type_call(Z_OBJ_P(ancestor.raw()), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		}
		if (classReflection.isNull()) {
			classReflection = pt_type_call(Z_OBJ_P(naked.raw()), PT_LC("getdeclaringclass"), 0, NULL);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		}
		/* fn (Type $type): Type => $this->transformStaticType($type, $scope) */
		zv::Val callback = callbackHolder(pt_static_type_callbacks_transform, self, scope, NULL, NULL);
		ZVAL_COPY_VALUE(&args[0], naked.raw());
		ZVAL_COPY_VALUE(&args[1], classReflection.raw());
		ZVAL_FALSE(&args[2]);
		ZVAL_COPY_VALUE(&args[3], callback.raw());
		return pt_type_new(isMethod ? PT_CLASS_CALLBACK_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION : PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args);
	}

	/* $this->methodCache[$key] ??= $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod(),
	 * the key the method name suffixed with the scope's class cache key when
	 * in a class; UNDEF = pending exception */
	zv::Val getMethod(zval *methodName, zval *scope) const
	{
		zv::Val inClass = pt_type_call(Z_OBJ_P(scope), PT_LC("isinclass"), 0, NULL);
		if (UNEXPECTED(inClass.isUndef())) return zv::Val();
		zv::Val key;
		if (zend_is_true(inClass.raw())) {
			zv::Val classReflection = pt_type_call(Z_OBJ_P(scope), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClassReflection() must return an object");
				return zv::Val();
			}
			zv::Val cacheKey = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getcachekey"), 0, NULL);
			if (UNEXPECTED(cacheKey.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(cacheKey.raw()).isString() || !zv::Ref(methodName).isString())) {
				zend_type_error("phpstan_turbo: getCacheKey() must return string");
				return zv::Val();
			}
			/* sprintf('%s-%s', $key, $cacheKey) */
			smart_str str = {NULL, 0};
			smart_str_append(&str, Z_STR_P(methodName));
			smart_str_appendc(&str, '-');
			smart_str_append(&str, zv::Ref(cacheKey.raw()).asString());
			smart_str_0(&str);
			key = zv::Val::adoptString(str.s);
		} else {
			key = zv::Val::copyOf(zv::Ref(methodName));
		}
		zval *cache = slot(self, slots::methodCache, "methodCache");
		if (UNEXPECTED(cache == NULL)) return zv::Val();
		if (UNEXPECTED(!zv::Ref(cache).isArray() || !zv::Ref(key.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::$methodCache must be array", ZSTR_VAL(pt_ce_static_type->name));
			return zv::Val();
		}
		zval *cached = zend_symtable_find(Z_ARRVAL_P(cache), zv::Ref(key.raw()).asString());
		if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));
		zv::Val method = pt_type_transformed_member(self, PT_LC("getunresolvedmethodprototype"), true, methodName, scope);
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		/* re-read: the prototype call may have touched the cache */
		cache = OBJ_PROP_NUM(self, slots::methodCache);
		if (EXPECTED(Z_TYPE_P(cache) == IS_ARRAY)) {
			SEPARATE_ARRAY(cache);
			zval stored;
			ZVAL_COPY(&stored, method.raw());
			zend_symtable_update(Z_ARRVAL_P(cache), zv::Ref(key.raw()).asString(), &stored);
		}
		return method;
	}

	/* TypeTraverser::map($type, <the map callback over $this and $scope>);
	 * UNDEF = pending exception */
	zv::Val transformStaticType(zval *type, zval *scope) const
	{
		zv::Val callback = callbackHolder(pt_static_type_callbacks_map, self, scope, NULL, NULL);
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* the TypeTraverser::map() callback: a StaticType is rebased onto the
	 * scope's class (the own reflection outside a class), a ThisType
	 * downgraded to static when $this is none, the own subtracted type
	 * subtracted, and traversed on under the recursion guard — or, in a
	 * final class, its static object type is; anything else is traversed;
	 * UNDEF = pending exception */
	zv::Val mapStaticType(zval *type, zval *scope, zval *traverse) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_static_type)) return pt_type_call_callable(traverse, 1, type);
		zval *ownReflection = classReflection();
		if (UNEXPECTED(ownReflection == NULL)) return zv::Val();
		zv::Val classReflection = zv::Val::copyOf(zv::Ref(ownReflection));
		bool isFinal = false;
		zv::Val inClass = pt_type_call(Z_OBJ_P(scope), PT_LC("isinclass"), 0, NULL);
		if (UNEXPECTED(inClass.isUndef())) return zv::Val();
		if (zend_is_true(inClass.raw())) {
			classReflection = pt_type_call(Z_OBJ_P(scope), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClassReflection() must return an object");
				return zv::Val();
			}
			if (UNEXPECTED(!reflectionFlag(classReflection.raw(), PT_LC("isfinal"), isFinal))) return zv::Val();
		}
		zv::Val mapped = pt_type_call(Z_OBJ_P(type), PT_LC("changebaseclass"), 1, classReflection.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(mapped.raw()).isObject())) {
			zend_type_error("phpstan_turbo: changeBaseClass() must return an object");
			return zv::Val();
		}

		/* When calling a method on a `static` type (not `$this`), `$this`
		 * return type should be downgraded to `static` because we can't
		 * guarantee the exact instance. */
		if (zv::Ref(mapped.raw()).instanceOf(pt_ce_this_type) && !instanceof_function(self->ce, pt_ce_this_type)) {
			zv::Val mappedReflection = pt_type_call(Z_OBJ_P(mapped.raw()), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(mappedReflection.isUndef())) return zv::Val();
			zv::Val mappedSubtracted = pt_type_call(Z_OBJ_P(mapped.raw()), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(mappedSubtracted.isUndef())) return zv::Val();
			mapped = create(mappedReflection.raw(), mappedSubtracted.isNull() ? NULL : mappedSubtracted.raw());
			if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		}

		zv::Val ownSubtracted = thisSubtractedType();
		if (UNEXPECTED(ownSubtracted.isUndef())) return zv::Val();
		if (!ownSubtracted.isNull()) {
			ownSubtracted = thisSubtractedType();
			if (UNEXPECTED(ownSubtracted.isUndef())) return zv::Val();
			mapped = pt_type_call(Z_OBJ_P(mapped.raw()), PT_LC("subtract"), 1, ownSubtracted.raw());
			if (UNEXPECTED(mapped.isUndef())) return zv::Val();
			if (!zv::Ref(mapped.raw()).instanceOf(pt_ce_static_type)) return pt_type_call_callable(traverse, 1, mapped.raw());
		}

		if (!isFinal || zv::Ref(mapped.raw()).instanceOf(pt_ce_this_type)) {
			/* RecursionGuard::run($type, static fn () => $traverse($type)) */
			zv::Val thunk = callbackHolder(pt_static_type_callbacks_guard, NULL, NULL, mapped.raw(), traverse);
			return pt_type_recursion_guard_run(mapped.raw(), thunk.raw());
		}

		zv::Val staticObject = pt_type_call(Z_OBJ_P(mapped.raw()), PT_LC("getstaticobjecttype"), 0, NULL);
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		return pt_type_call_callable(traverse, 1, staticObject.raw());
	}

	/* new self($classReflection, $this->subtractedType); UNDEF = pending
	 * exception */
	zv::Val changeBaseClass(zval *classReflection) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		return create(classReflection, Z_TYPE_P(subtracted) == IS_NULL ? NULL : subtracted);
	}

	/* new IntersectionType([$this->getClassStringType(), new AccessoryLiteralStringType()]);
	 * UNDEF = pending exception */
	zv::Val toClassConstantType() const
	{
		zv::Val classString = thisClassStringType();
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		zv::Val literal = pt_type_new_shadowed(pt_accessory_literal_string_type_new);
		if (UNEXPECTED(literal.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(classString));
		types.push(std::move(literal));
		return pt_intersection_of(std::move(types));
	}

	/* new self($this->classReflection, $cb($this->subtractedType)) when the
	 * callback changed it, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisValue();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtracted, &mapped))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mapped);
		if (Z_TYPE(mapped) == IS_OBJECT && Z_OBJ(mapped) == Z_OBJ_P(subtracted)) return thisValue();
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return create(reflection, Z_TYPE(mapped) == IS_NULL ? NULL : mappedType.raw());
	}

	/* $this without a subtracted type, new self($this->classReflection)
	 * with one; UNDEF = pending exception */
	zv::Val traverseSimultaneously() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisValue();
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return create(reflection);
	}

	/* $this->changeSubtractedType($type unioned with the subtracted type);
	 * UNDEF = pending exception */
	zv::Val subtract(zval *type) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val unioned;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Args args{subtracted, type};
			unioned = pt_type_combinator_call(PT_LC("union"), 2, args);
			if (UNEXPECTED(unioned.isUndef())) return zv::Val();
			type = unioned.raw();
		}
		return thisChangeSubtractedType(type);
	}

	/* $this->changeSubtractedType(null); UNDEF = pending exception */
	zv::Val getTypeWithoutSubtractedType() const
	{
		zval null;
		ZVAL_NULL(&null);
		return thisChangeSubtractedType(&null);
	}

	/* new self($this->classReflection, $subtractedType) — for a class with
	 * allowed subtypes projected through the static object type: never
	 * stays never, an ObjectType's remaining subtracted type is taken over,
	 * anything else intersected with $this; UNDEF = pending exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		if (Z_TYPE_P(subtractedType) != IS_NULL) {
			zv::Val classReflection = thisClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClassReflection() must return an object");
				return zv::Val();
			}
			zv::Val allowedSubTypes = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getallowedsubtypes"), 0, NULL);
			if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
			if (!allowedSubTypes.isNull()) {
				zv::Val objectType = delegate(PT_LC("changesubtractedtype"), 1, subtractedType);
				if (UNEXPECTED(objectType.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(objectType.raw()).isObject())) {
					zend_type_error("phpstan_turbo: changeSubtractedType() must return an object");
					return zv::Val();
				}
				if (zv::Ref(objectType.raw()).instanceOf(pt_ce_never_type)) return objectType;
				bool isObjectType;
				if (UNEXPECTED(!pt_type_instanceof_ce(objectType.raw(), pt_ce_object_type, isObjectType))) return zv::Val();
				if (isObjectType) {
					zv::Val remaining = pt_type_call(Z_OBJ_P(objectType.raw()), PT_LC("getsubtractedtype"), 0, NULL);
					if (UNEXPECTED(remaining.isUndef())) return zv::Val();
					if (!remaining.isNull()) return create(classReflection.raw(), remaining.raw());
				}
				zv::Args args{self, objectType.raw()};
				return pt_type_combinator_call(PT_LC("intersect"), 2, args);
			}
		}
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return create(reflection, Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType);
	}

	/* $this->subtract($typeToRemove) when the static object type is a
	 * supertype of it, null otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val isSuperType = delegate(PT_LC("issupertypeof"), 1, typeToRemove);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value != PT_TRI_YES) return zv::Val::null();
		if (EXPECTED(pt_type_method_is(self, PT_LC("subtract"), stSubtract))) return subtract(typeToRemove);
		return pt_type_call(self, PT_LC("subtract"), 1, typeToRemove);
	}

	/* new IdentifierTypeNode('static') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("static", 6);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	/* the $this-calls a subclass may answer differently, with the direct
	 * path when the method is StaticType's own; UNDEF = pending exception */
	zv::Val thisStaticObjectType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getstaticobjecttype"), stGetStaticObjectType))) return getStaticObjectType();
		return pt_type_call(self, PT_LC("getstaticobjecttype"), 0, NULL);
	}

	zv::Val thisClassReflection() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getclassreflection"), stGetClassReflection))) return getClassReflection();
		return pt_type_call(self, PT_LC("getclassreflection"), 0, NULL);
	}

	zv::Val thisSubtractedType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getsubtractedtype"), stGetSubtractedType))) {
			zval *subtracted = subtractedType();
			if (UNEXPECTED(subtracted == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(subtracted));
		}
		return pt_type_call(self, PT_LC("getsubtractedtype"), 0, NULL);
	}

	zv::Val thisClassName() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getclassname"), stGetClassName))) return getClassName();
		return pt_type_call(self, PT_LC("getclassname"), 0, NULL);
	}

	zv::Val thisGetAncestorWithClassName(zval *className) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getancestorwithclassname"), stGetAncestorWithClassName))) return getAncestorWithClassName(className);
		return pt_type_call(self, PT_LC("getancestorwithclassname"), 1, className);
	}

	zv::Val thisChangeBaseClass(zval *classReflection) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("changebaseclass"), stChangeBaseClass))) return changeBaseClass(classReflection);
		return pt_type_call(self, PT_LC("changebaseclass"), 1, classReflection);
	}

	zv::Val thisChangeSubtractedType(zval *subtractedType) const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("changesubtractedtype"), stChangeSubtractedType))) return changeSubtractedType(subtractedType);
		return pt_type_call(self, PT_LC("changesubtractedtype"), 1, subtractedType);
	}

	zv::Val thisClassStringType() const
	{
		if (EXPECTED(pt_type_method_is(self, PT_LC("getclassstringtype"), stGetClassStringType))) return getClassStringType();
		return pt_type_call(self, PT_LC("getclassstringtype"), 0, NULL);
	}

	/* a Closure over one of the holder's methods with the captured values
	 * in its slots (each borrowed, NULL for none) */
	static zv::Val callbackHolder(zend_function *fn, zend_object *staticType, zval *scope, zval *type, zval *traverse)
	{
		zval holder;
		object_init_ex(&holder, pt_ce_static_type_callbacks);
		zv::ObjRef holderRef(&holder);
		if (staticType != NULL) {
			zval staticTypeZv;
			ZVAL_OBJ(&staticTypeZv, staticType);
			holderRef.propAtWrite(slots::subtractedType, zv::Val::copyOf(zv::Ref(&staticTypeZv)));
		}
		if (scope != NULL) {
			holderRef.propAtWrite(slots::staticObjectType, zv::Val::copyOf(zv::Ref(scope)));
		}
		if (type != NULL) {
			holderRef.propAtWrite(slots::baseClass, zv::Val::copyOf(zv::Ref(type)));
		}
		if (traverse != NULL) {
			holderRef.propAtWrite(slots::methodCache, zv::Val::copyOf(zv::Ref(traverse)));
		}
		zv::Val closure = pt_type_closure_over(fn, pt_ce_static_type_callbacks, Z_OBJ(holder));
		zval_ptr_dtor(&holder); /* the closure holds its own reference */
		return closure;
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $reflection->isGeneric() / isFinal(); false = pending exception */
	[[nodiscard]] static bool reflectionFlag(zval *reflection, const char *lcname, size_t len, bool &out)
	{
		zv::Val flag = pt_type_call(Z_OBJ_P(reflection), lcname, len, 0, NULL);
		if (UNEXPECTED(flag.isUndef())) return false;
		out = zend_is_true(flag.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::StaticType;

bool pt_static_type_new(zval *out, zval *classReflection, zval *subtractedType)
{
	return pt_val_into(StaticType::create(classReflection, subtractedType), out);
}

void pt_static_type_construct(zend_object *self, zval *classReflection, zval *subtractedType)
{
	StaticType(self).construct(classReflection, subtractedType);
}

zval *pt_static_type_subtracted_type(zend_object *object) { return StaticType(object).subtractedType(); }
zval *pt_static_type_class_reflection(zend_object *object) { return StaticType(object).classReflection(); }
zv::Val pt_static_type_get_static_object_type(zend_object *self) { return StaticType(self).getStaticObjectType(); }
zv::Val pt_static_type_is_super_type_of(zend_object *self, zval *type) { return StaticType(self).isSuperTypeOf(type); }
zv::Val pt_static_type_change_subtracted_type(zend_object *self, zval *subtractedType) { return StaticType(self).changeSubtractedType(subtractedType); }
zv::Val pt_static_type_to_class_constant_type(zend_object *self) { return StaticType(self).toClassConstantType(); }
zv::Val pt_static_type_to_php_doc_node() { return StaticType::toPhpDocNode(); }
zv::Val pt_static_type_this_static_object_type(zend_object *self) { return StaticType(self).thisStaticObjectType(); }
zv::Val pt_static_type_this_class_reflection(zend_object *self) { return StaticType(self).thisClassReflection(); }
zv::Val pt_static_type_this_subtracted_type(zend_object *self) { return StaticType(self).thisSubtractedType(); }
zv::Val pt_static_type_this_class_name(zend_object *self) { return StaticType(self).thisClassName(); }

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS StaticType(Z_OBJ_P(ZEND_THIS))

/* the holder's $this, an object of the holder class; NULL with an Error
 * pending */
[[nodiscard]] static zend_object *callbackHolderThis(zval *thisZv)
{
	if (UNEXPECTED(Z_TYPE_P(thisZv) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: StaticType callback called without its holder");
		return NULL;
	}
	return Z_OBJ_P(thisZv);
}

/* StaticTypeCallbacks::transform(Type $type): Type — the prototype
 * reflections' `fn (Type $type): Type => $this->transformStaticType($type, $scope)` */
static void ZEND_FASTCALL callbackTransform(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	zend_object *holder = callbackHolderThis(ZEND_THIS);
	if (UNEXPECTED(holder == NULL)) RETURN_THROWS();
	zval *staticType = OBJ_PROP_NUM(holder, slots::subtractedType);
	if (UNEXPECTED(Z_TYPE_P(staticType) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: StaticType callback holder without its type");
		RETURN_THROWS();
	}
	PT_RETURN_VAL(StaticType(Z_OBJ_P(staticType)).transformStaticType(type, OBJ_PROP_NUM(holder, slots::staticObjectType)));
}

/* StaticTypeCallbacks::map(Type $type, callable $traverse): Type — the
 * TypeTraverser::map() callback of transformStaticType() */
static void ZEND_FASTCALL callbackMap(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type, *traverse;
	if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, traverse)) RETURN_THROWS();
	zend_object *holder = callbackHolderThis(ZEND_THIS);
	if (UNEXPECTED(holder == NULL)) RETURN_THROWS();
	zval *staticType = OBJ_PROP_NUM(holder, slots::subtractedType);
	if (UNEXPECTED(Z_TYPE_P(staticType) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: StaticType callback holder without its type");
		RETURN_THROWS();
	}
	PT_RETURN_VAL(StaticType(Z_OBJ_P(staticType)).mapStaticType(type, OBJ_PROP_NUM(holder, slots::staticObjectType), traverse));
}

/* StaticTypeCallbacks::guard(): Type — the RecursionGuard::run() thunk
 * `static fn () => $traverse($type)` */
static void ZEND_FASTCALL callbackGuard(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_object *holder = callbackHolderThis(ZEND_THIS);
	if (UNEXPECTED(holder == NULL)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_call_callable(OBJ_PROP_NUM(holder, slots::methodCache), 1, OBJ_PROP_NUM(holder, slots::baseClass)));
}

/* StaticTypeCallbacks::toArgument(string $name, Type $type): Type — the
 * TemplateTypeMap::map() callback of getStaticObjectType() */
static void ZEND_FASTCALL callbackToArgument(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *name, *type;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, type)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_HELPER, PT_LC("toargument"), 1, type));
}

/* the delegating bodies: $this->getStaticObjectType()->method(...$args)
 * with the arguments passed through as received (one handler per arity;
 * each method is still declared exactly once, at its registration line) */
static void stDelegate(INTERNAL_FUNCTION_PARAMETERS, const char *lcname, size_t len, uint32_t min, uint32_t max)
{
	PT_ARGS(min, max);
	uint32_t argc = ZEND_NUM_ARGS();
	zval *argv = argc > 0 ? ZEND_CALL_ARG(execute_data, 1) : NULL;
	PT_RETURN_VAL(PT_THIS.delegate(lcname, len, argc, argv));
}

/* the delegating bodies of the optional-argument methods: the omitted
 * arguments passed at their defaults, as the twin forwards them */
static void stDelegateDefault(INTERNAL_FUNCTION_PARAMETERS, const char *lcname, size_t len, uint32_t min, uint32_t max, zval *defaults)
{
	PT_ARGS(min, max);
	uint32_t argc = ZEND_NUM_ARGS();
	zval args[3];
	for (uint32_t i = 0; i < max; i++) {
		if (i < argc) {
			ZVAL_COPY_VALUE(&args[i], ZEND_CALL_ARG(execute_data, i + 1));
		} else {
			ZVAL_COPY_VALUE(&args[i], &defaults[i - min]);
		}
	}
	PT_RETURN_VAL(PT_THIS.delegate(lcname, len, max, args));
}

static void ZEND_FASTCALL stGetStaticObjectType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getStaticObjectType());
}

static void ZEND_FASTCALL stGetClassReflection(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getClassReflection());
}

static void ZEND_FASTCALL stGetSubtractedType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zval *subtracted = PT_THIS.subtractedType();
	if (UNEXPECTED(subtracted == NULL)) RETURN_THROWS();
	RETURN_COPY(subtracted);
}

static void ZEND_FASTCALL stGetClassName(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getClassName());
}

static void ZEND_FASTCALL stGetAncestorWithClassName(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *className;
	if (!zp::parse<zp::Str>(execute_data, className)) RETURN_THROWS();
	zval classNameZv;
	ZVAL_STR(&classNameZv, className);
	PT_RETURN_VAL(PT_THIS.getAncestorWithClassName(&classNameZv));
}

static void ZEND_FASTCALL stChangeBaseClass(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *classReflection;
	if (!zp::parse<zp::Obj>(execute_data, classReflection)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.changeBaseClass(classReflection));
}

static void ZEND_FASTCALL stChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *subtractedType;
	if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
	zval null;
	if (subtractedType == NULL) {
		ZVAL_NULL(&null);
		subtractedType = &null;
	}
	PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
}

static void ZEND_FASTCALL stGetClassStringType(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getClassStringType());
}

static void ZEND_FASTCALL stSubtract(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.subtract(type));
}

static void ZEND_FASTCALL stError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * the transformed member of $this->getUnresolved*Prototype() */
static void stTransformedMember(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_transformed_member(Z_OBJ_P(ZEND_THIS), prototypeLcname, prototypeLen, isMethod, name, scope));
}

/* getUnresolvedPropertyPrototype() & co.: (string $name, ClassMemberAccessAnswerer $scope) */
static void stUnresolvedPrototype(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL(PT_THIS.unresolvedPrototype(prototypeLcname, prototypeLen, isMethod, &nameZv, scope));
}

void pt_register_static_type()
{
	/* the callback holder: registered under a builder name other than
	 * `cls` on purpose — the side-by-side parity scan pairs
	 * `cls.method(...)` lines with the twin's methods, and the holder's
	 * have none */
	reg::Class holder("PHPStanTurbo\\StaticTypeCallbacks");
	holder.privateNullProperty("staticType");
	holder.privateNullProperty("scope");
	holder.privateNullProperty("type");
	holder.privateNullProperty("traverse");
	holder.method("transform", reg::Public, 1, { reg::obj("type", ptcls::type) }, callbackTransform, &ptret::type);
	holder.method("map", reg::Public, 2, { reg::obj("type", ptcls::type), reg::callableArg("traverse") }, callbackMap, &ptret::type);
	holder.method("guard", reg::Public, 0, {}, callbackGuard, &ptret::type);
	holder.method("toArgument", reg::PublicStatic, 2, { reg::stringArg("name"), reg::obj("type", ptcls::type) }, callbackToArgument, &ptret::type);
	pt_ce_static_type_callbacks = holder.register_();
	pt_ce_static_type_callbacks->ce_flags |= ZEND_ACC_FINAL;
	pt_static_type_callbacks_transform = (zend_function *) zend_hash_str_find_ptr(&pt_ce_static_type_callbacks->function_table, PT_LC("transform"));
	pt_static_type_callbacks_map = (zend_function *) zend_hash_str_find_ptr(&pt_ce_static_type_callbacks->function_table, PT_LC("map"));
	pt_static_type_callbacks_guard = (zend_function *) zend_hash_str_find_ptr(&pt_ce_static_type_callbacks->function_table, PT_LC("guard"));
	pt_static_type_callbacks_to_argument = (zend_function *) zend_hash_str_find_ptr(&pt_ce_static_type_callbacks->function_table, PT_LC("toargument"));
	ZEND_ASSERT(pt_static_type_callbacks_transform != NULL && pt_static_type_callbacks_map != NULL && pt_static_type_callbacks_guard != NULL && pt_static_type_callbacks_to_argument != NULL);

	reg::Class cls("PHPStan\\Type\\StaticType");
	ptdecl::StaticType::declareClass(cls);
	/* the slots must stay in this order (PT_ST_PROP_*) */
	cls.privateTypedClassProperty("subtractedType", "PHPStan\\Type\\Type", true);
	cls.privateTypedClassPropertyDefaultNull("staticObjectType", "PHPStan\\Type\\ObjectType");
	cls.privateTypedProperty("baseClass", MAY_BE_STRING);
	cls.privateTypedArrayPropertyDefaultEmpty("methodCache");
	cls.privateTypedClassProperty("classReflection", "PHPStan\\Reflection\\ClassReflection", false);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *subtractedType = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, classReflection, subtractedType)) RETURN_THROWS();
		PT_THIS.construct(classReflection, subtractedType);
	});

	cls.method(sigs::getClassName, stGetClassName);
	cls.method(sigs::getClassReflection, stGetClassReflection);
	cls.method(sigs::getAncestorWithClassName, stGetAncestorWithClassName);
	cls.method(sigs::getStaticObjectType, stGetStaticObjectType);

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getreferencedclasses"), 0, 0);
	});
	cls.method(sigs::getObjectClassNames, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getobjectclassnames"), 0, 0);
	});
	cls.method(sigs::getObjectClassReflections, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getobjectclassreflections"), 0, 0);
	});
	cls.method(sigs::getArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getarrays"), 0, 0);
	});
	cls.method(sigs::getConstantArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstantarrays"), 0, 0);
	});
	cls.method(sigs::getConstantStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstantstrings"), 0, 0);
	});

	cls.method<&StaticType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&StaticType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&StaticType::equals, zp::Obj>(sigs::equals);

	cls.method<&StaticType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("gettemplatetype"), 2, 2);
	});
	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isobject"), 0, 0);
	});
	cls.method(sigs::getClassStringType, stGetClassStringType);
	cls.method(sigs::isEnum, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isenum"), 0, 0);
	});
	cls.method(sigs::canAccessProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("canaccessproperties"), 0, 0);
	});
	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasproperty"), 1, 1);
	});
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		stUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.method(sigs::hasInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasinstanceproperty"), 1, 1);
	});
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		stUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasstaticproperty"), 1, 1);
	});
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		stTransformedMember(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		stUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.method(sigs::canCallMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("cancallmethods"), 0, 0);
	});
	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasmethod"), 1, 1);
	});

	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *methodName;
		zval *scope;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, methodName, scope)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, methodName);
		PT_RETURN_VAL(PT_THIS.getMethod(&nameZv, scope));
	});

	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		stUnresolvedPrototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});

	cls.method(sigs::canAccessConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("canaccessconstants"), 0, 0);
	});
	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasconstant"), 1, 1);
	});
	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstant"), 1, 1);
	});

	cls.method(sigs::changeBaseClass, stChangeBaseClass);

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isiterable"), 0, 0);
	});
	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isiterableatleastonce"), 0, 0);
	});
	cls.method(sigs::getArraySize, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getarraysize"), 0, 0);
	});
	cls.method(sigs::getIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablekeytype"), 0, 0);
	});
	/* the first/last variants delegate to the plain key/value ones, as
	 * the twin does */
	cls.method(sigs::getFirstIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablekeytype"), 0, 0);
	});
	cls.method(sigs::getLastIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablekeytype"), 0, 0);
	});
	cls.method(sigs::getIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablevaluetype"), 0, 0);
	});
	cls.method(sigs::getFirstIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablevaluetype"), 0, 0);
	});
	cls.method(sigs::getLastIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getiterablevaluetype"), 0, 0);
	});
	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isoffsetaccessible"), 0, 0);
	});
	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isoffsetaccesslegal"), 0, 0);
	});
	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hasoffsetvaluetype"), 1, 1);
	});
	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getoffsetvaluetype"), 1, 1);
	});
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval unionValues;
		ZVAL_TRUE(&unionValues);
		stDelegateDefault(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("setoffsetvaluetype"), 2, 3, &unionValues);
	});
	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("setexistingoffsetvaluetype"), 2, 2);
	});
	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("unsetoffset"), 1, 1);
	});
	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getkeysarrayfiltered"), 2, 2);
	});
	cls.method(sigs::getKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getkeysarray"), 0, 0);
	});
	cls.method(sigs::getValuesArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getvaluesarray"), 0, 0);
	});
	cls.method(sigs::chunkArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("chunkarray"), 2, 2);
	});
	cls.method(sigs::fillKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("fillkeysarray"), 1, 1);
	});
	cls.method(sigs::flipArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("fliparray"), 0, 0);
	});
	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("intersectkeyarray"), 1, 1);
	});
	cls.method(sigs::popArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("poparray"), 0, 0);
	});
	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("reversearray"), 1, 1);
	});
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval strict;
		ZVAL_NULL(&strict);
		stDelegateDefault(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("searcharray"), 1, 2, &strict);
	});
	cls.method(sigs::shiftArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("shiftarray"), 0, 0);
	});
	cls.method(sigs::shuffleArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("shufflearray"), 0, 0);
	});
	cls.method(sigs::sliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("slicearray"), 3, 3);
	});
	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("splicearray"), 3, 3);
	});
	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("truncatelisttosize"), 1, 1);
	});
	cls.method(sigs::makeListMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("makelistmaybe"), 0, 0);
	});
	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("mapvaluetype"), 1, 1);
	});
	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("mapkeytype"), 1, 1);
	});
	cls.method(sigs::makeAllArrayKeysOptional, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("makeallarraykeysoptional"), 0, 0);
	});
	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("changekeycasearray"), 1, 1);
	});
	cls.method(sigs::filterArrayRemovingFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("filterarrayremovingfalsey"), 0, 0);
	});
	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("iscallable"), 0, 0);
	});
	cls.method(sigs::getEnumCases, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getenumcases"), 0, 0);
	});
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getenumcaseobject"), 0, 0);
	});
	cls.method(sigs::isArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isarray"), 0, 0);
	});
	cls.method(sigs::isConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isconstantarray"), 0, 0);
	});
	cls.method(sigs::isOversizedArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isoversizedarray"), 0, 0);
	});
	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("islist"), 0, 0);
	});
	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isnull"), 0, 0);
	});
	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isconstantvalue"), 0, 0);
	});
	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isconstantscalarvalue"), 0, 0);
	});
	cls.method(sigs::getConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstantscalartypes"), 0, 0);
	});
	cls.method(sigs::getConstantScalarValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getconstantscalarvalues"), 0, 0);
	});
	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("istrue"), 0, 0);
	});
	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isfalse"), 0, 0);
	});
	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isboolean"), 0, 0);
	});
	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isfloat"), 0, 0);
	});
	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isinteger"), 0, 0);
	});
	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isstring"), 0, 0);
	});
	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isnumericstring"), 0, 0);
	});
	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isdecimalintegerstring"), 0, 0);
	});
	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isnonemptystring"), 0, 0);
	});
	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isnonfalsystring"), 0, 0);
	});
	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isliteralstring"), 0, 0);
	});
	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("islowercasestring"), 0, 0);
	});
	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isuppercasestring"), 0, 0);
	});
	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isclassstring"), 0, 0);
	});
	cls.method(sigs::getClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getclassstringobjecttype"), 0, 0);
	});
	cls.method(sigs::getObjectTypeOrClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isvoid"), 0, 0);
	});
	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("isscalar"), 0, 0);
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getcallableparametersacceptors"), 1, 1);
	});

	cls.method(sigs::isCloneable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY(PT_TRI_YES);
	});

	cls.method(sigs::toNumber, stError0);
	cls.method(sigs::toBitwiseNotType, stError0);

	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* $this->getClassStringType() — through the object's class,
		 * preserving the static binding */
		PT_RETURN_VAL(PT_THIS.thisClassStringType());
	});

	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.toClassConstantType());
	});

	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		/* new ClassNameToObjectTypeResult($this, true) */
		zv::Args args{ZEND_THIS, true};
		PT_RETURN_VAL(pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args));
	});

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_object_type_for_is_a_check(allowString));
	});

	cls.method(sigs::toAbsoluteNumber, stError0);
	cls.method(sigs::toString, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("tostring"), 0, 0);
	});
	cls.method(sigs::toInteger, stError0);
	cls.method(sigs::toFloat, stError0);
	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("toarray"), 0, 0);
	});
	cls.method(sigs::toArrayKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("toarraykey"), 0, 0);
	});
	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("tocoercedargumenttype"), 1, 1);
	});
	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("toboolean"), 0, 0);
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method(sigs::subtract, stSubtract);

	cls.method<&StaticType::getTypeWithoutSubtractedType>(sigs::getTypeWithoutSubtractedType);

	cls.method(sigs::changeSubtractedType, stChangeSubtractedType);
	cls.method(sigs::getSubtractedType, stGetSubtractedType);

	cls.method<&StaticType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("exponentiate"), 1, 1);
	});
	cls.method(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getfinitetypes"), 0, 0);
	});

	cls.method<&StaticType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		stDelegate(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("hastemplateorlateresolvabletype"), 0, 0);
	});

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::StaticType::registerTraits(cls);

	cls.shadow(&pt_ce_static_type);
}

/* }}} */
