/*
 * PHPStanTurbo\GenericObjectType — native implementation of
 * PHPStan\Type\Generic\GenericObjectType.
 *
 * Declared as PHPStan\Type\Generic\GenericObjectType itself at activation,
 * extending the native ObjectType: not final (the PHP
 * TemplateGenericObjectType extends it, calling parent::__construct() and
 * overriding recreate()). State is the twin's three promoted constructor
 * properties — `private array $types`, `private ?ClassReflection
 * $classReflection` (its own, distinct from ObjectType's private slot of
 * the same name) and `private array $variances` — declared typed property
 * slots following the parent's ten. Every parent:: call goes to the C++
 * bodies ObjectType.cpp exports; every `$this->method()` through the
 * object's class entry with a direct C++ call when the object's method is
 * the native one.
 */

#include "TypeTraits.h"
#include "generated/GenericObjectType.h"

namespace sigs = ptdecl::GenericObjectType::sig;

#include <vector>

zend_class_entry *pt_ce_generic_object_type = nullptr;

/* OBJ_PROP_NUM slots: the parent's ten first (ObjectType.cpp), then the
 * twin's promoted properties in parameter order */
#define PT_OT_PROP_COUNT 10
#define PT_GOT_PROP_TYPES (PT_OT_PROP_COUNT + 0)
#define PT_GOT_PROP_CLASS_REFLECTION (PT_OT_PROP_COUNT + 1)
#define PT_GOT_PROP_VARIANCES (PT_OT_PROP_COUNT + 2)

/* the handlers the $this-dispatch fast paths identify */
static void ZEND_FASTCALL gotGetClassReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotGetTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotRecreate(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotGetUnresolvedPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotGetUnresolvedStaticPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL gotGetUnresolvedMethodPrototype(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\GenericObjectType. */
class GenericObjectType
{
public:
	explicit GenericObjectType(zend_object *self) : self(self) {}

	/* __construct(string $mainType, private array $types, ?Type
	 * $subtractedType = null, private ?ClassReflection $classReflection =
	 * null, private array $variances = []): the promoted properties first
	 * (as the engine assigns them), then parent::__construct(); every
	 * argument borrowed, NULL for null / a default */
	void construct(zend_string *mainType, zval *types, zval *subtractedType, zval *classReflection, zval *variances)
	{
		writeSlot(PT_GOT_PROP_TYPES, zv::Val::copyOf(zv::Ref(types)));
		writeSlot(PT_GOT_PROP_CLASS_REFLECTION, classReflection == NULL || Z_TYPE_P(classReflection) != IS_OBJECT ? zv::Val::null() : zv::Val::copyOf(zv::Ref(classReflection)));
		writeSlot(PT_GOT_PROP_VARIANCES, variances == NULL ? zv::Val(zv::Arr::empty()) : zv::Val::copyOf(zv::Ref(variances)));
		pt_object_type_construct(self, mainType, subtractedType, classReflection);
	}

	/* new self($className, $types, $subtractedType, $classReflection, $variances);
	 * UNDEF = pending exception */
	static zv::Val create(zend_string *className, zval *types, zval *subtractedType, zval *classReflection, zval *variances)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_generic_object_type) != SUCCESS)) return zv::Val();
		GenericObjectType(Z_OBJ(object)).construct(className, types, subtractedType, classReflection, variances);
		return zv::Val::adopt(object);
	}

	/* {{{ the slots */

	/* $this->types / $type->types (borrowed); NULL with an Error pending
	 * when uninitialized */
	[[nodiscard]] static zval *typesOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, PT_GOT_PROP_TYPES);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$types must not be accessed before initialization", ZSTR_VAL(pt_ce_generic_object_type->name));
			return NULL;
		}
		return slot;
	}

	zval *types() const { return typesOf(self); }

	/* $this->variances / $type->variances (borrowed; defaults to []) */
	static zval *variancesOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, PT_GOT_PROP_VARIANCES);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$variances must not be accessed before initialization", ZSTR_VAL(pt_ce_generic_object_type->name));
			return NULL;
		}
		return slot;
	}

	zval *variances() const { return variancesOf(self); }

	/* $this->classReflection — this class's own (borrowed, IS_NULL or
	 * IS_OBJECT); NULL with an Error pending when uninitialized */
	[[nodiscard]] zval *classReflection() const
	{
		zval *slot = OBJ_PROP_NUM(self, PT_GOT_PROP_CLASS_REFLECTION);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$classReflection must not be accessed before initialization", ZSTR_VAL(pt_ce_generic_object_type->name));
			return NULL;
		}
		return slot;
	}

	/* }}} */

	/* parent::describe($level) . '<' . the projected type arguments . '>';
	 * UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val parentDescription = pt_object_type_describe(self, level);
		if (UNEXPECTED(parentDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(parentDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		/* array_map() over two arrays pairs them by position, padding the
		 * shorter one with null */
		std::vector<zval *> typeValues, varianceValues;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			typeValues.push_back(entry.value().deref().raw());
		}
		for (zv::ArrayEntry entry : zv::ArrRef(variances)) {
			varianceValues.push_back(entry.value().deref().raw());
		}
		size_t count = typeValues.size() > varianceValues.size() ? typeValues.size() : varianceValues.size();

		smart_str description = {NULL, 0};
		smart_str_append(&description, zv::Ref(parentDescription.raw()).asString());
		smart_str_appendc(&description, '<');
		zval null;
		ZVAL_NULL(&null);
		for (size_t i = 0; i < count; i++) {
			zval args[3];
			ZVAL_COPY_VALUE(&args[0], i < typeValues.size() ? typeValues[i] : &null);
			ZVAL_COPY_VALUE(&args[1], i < varianceValues.size() ? varianceValues[i] : &null);
			ZVAL_COPY_VALUE(&args[2], level);
			zv::Val projected = pt_type_call_static(PT_CLASS_TYPE_PROJECTION_HELPER, PT_LC("describe"), 3, args);
			if (UNEXPECTED(projected.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (i > 0) {
				smart_str_appendl(&description, ", ", 2);
			}
			zv::Str projectedStr = zv::Str::adopt(zval_get_string(projected.raw()));
			smart_str_append(&description, projectedStr.get());
		}
		smart_str_appendc(&description, '>');
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* a GenericObjectType equal as an ObjectType with pairwise equal type
	 * arguments and variances; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!zv::Ref(type).instanceOf(pt_ce_generic_object_type)) {
			out = false;
			return true;
		}
		bool parentEqual;
		if (UNEXPECTED(!pt_object_type_equals(self, type, parentEqual))) return false;
		if (!parentEqual) {
			out = false;
			return true;
		}

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return false;
		zval *otherTypes = typesOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherTypes == NULL)) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) != zend_hash_num_elements(Z_ARRVAL_P(otherTypes))) {
			out = false;
			return true;
		}

		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return false;
		zval *otherVariances = variancesOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherVariances == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref genericType = entry.value().deref();
			zv::Val otherGenericType = readKey(Z_ARRVAL_P(otherTypes), entry);
			if (UNEXPECTED(otherGenericType.isUndef())) return false;
			if (UNEXPECTED(!genericType.isObject())) {
				zend_type_error("phpstan_turbo: a type argument must be %s", ptcls::type);
				return false;
			}
			bool equal;
			if (UNEXPECTED(!pt_type_call_bool(genericType.asObject(), PT_LC("equals"), 1, otherGenericType.raw(), equal))) return false;
			if (!equal) {
				out = false;
				return true;
			}

			zv::Val variance = varianceAt(Z_ARRVAL_P(variances), entry);
			if (UNEXPECTED(variance.isUndef())) return false;
			zv::Val otherVariance = varianceAt(Z_ARRVAL_P(otherVariances), entry);
			if (UNEXPECTED(otherVariance.isUndef())) return false;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(variance.raw()), PT_LC("equals"), 1, otherVariance.raw(), equal))) return false;
			if (!equal) {
				out = false;
				return true;
			}
		}

		out = true;
		return true;
	}

	/* the parent's classes plus those of the type arguments, each behind
	 * RecursionGuard::runOnObjectIdentity(); UNDEF = pending exception */
	zv::Val getReferencedClasses() const
	{
		zv::Val classes = pt_object_type_get_referenced_classes(self);
		if (UNEXPECTED(classes.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(classes.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getReferencedClasses() must return array");
			return zv::Val();
		}
		zv::Arr result = zv::Arr::adoptVal(std::move(classes));
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref type = entry.value().deref();
			zv::Val callback = pt_object_type_referenced_classes_callback(type.raw());
			if (UNEXPECTED(callback.isUndef())) return zv::Val();
			zval args[2];
			ZVAL_COPY_VALUE(&args[0], type.raw());
			ZVAL_COPY_VALUE(&args[1], callback.raw());
			zv::Val referencedClasses = pt_type_call_static(PT_CLASS_RECURSION_GUARD, PT_LC("runonobjectidentity"), 2, args);
			if (UNEXPECTED(referencedClasses.isUndef())) return zv::Val();
			bool isError;
			if (UNEXPECTED(!pt_type_instanceof(referencedClasses.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
			if (isError) continue;
			if (UNEXPECTED(!zv::Ref(referencedClasses.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getReferencedClasses() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry referenced : zv::ArrRef(referencedClasses.raw())) {
				result.push(referenced.value());
			}
		}

		return zv::Val(std::move(result));
	}

	zv::Val getTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(types));
	}

	zv::Val getVariances() const
	{
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(variances));
	}

	/* UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zv::Val isSuperType = isSuperTypeOfInternal(type, true);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	/* UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		return isSuperTypeOfInternal(type, false);
	}

	/* the naked (parent's) verdict, refined by the variance of each type
	 * argument against the type's ancestor of this class; UNDEF = pending
	 * exception */
	zv::Val isSuperTypeOfInternal(zval *type, bool acceptsContext) const
	{
		zv::Val nakedSuperTypeOf = pt_object_type_is_super_type_of(self, type);
		if (UNEXPECTED(nakedSuperTypeOf.isUndef())) return zv::Val();
		zend_long naked = pt_type_result_trinary(nakedSuperTypeOf.raw());
		if (UNEXPECTED(naked < 0)) return zv::Val();
		if (naked == PT_TRI_NO) return nakedSuperTypeOf;

		if (!zv::Ref(type).instanceOf(pt_ce_object_type)) return nakedSuperTypeOf;

		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val ancestor = pt_type_call(Z_OBJ_P(type), PT_LC("getancestorwithclassname"), 1, className.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		if (ancestor.isNull()) return nakedSuperTypeOf;
		if (!zv::Ref(ancestor.raw()).instanceOf(pt_ce_generic_object_type)) {
			if (acceptsContext) return nakedSuperTypeOf;

			return andMaybe(std::move(nakedSuperTypeOf));
		}
		zend_object *ancestorObject = Z_OBJ_P(ancestor.raw());

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *ancestorTypes = typesOf(ancestorObject);
		if (UNEXPECTED(ancestorTypes == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) != zend_hash_num_elements(Z_ARRVAL_P(ancestorTypes))) return pt_type_is_super_type_of_result(PT_TRI_NO);

		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return nakedSuperTypeOf;

		// Type arguments of a class name written without them are resolved to the
		// template bounds, so a `mixed` there means "not parameterized" and stays
		// compatible with any other type argument. When the other side is explicitly
		// parameterized, `mixed` is a type argument like any other and the variance
		// has to be evaluated strictly - otherwise `Foo<mixed>` and `Foo<int>` would
		// be supertypes of each other and TypeCombinator::union() would discard one
		// of them depending on their order.
		bool strictVariance = !acceptsContext && zv::Ref(type).instanceOf(pt_ce_generic_object_type);

		zv::Val typeList = templateTypeList(Z_OBJ_P(classReflection.raw()), PT_LC("gettemplatetypemap"));
		if (UNEXPECTED(typeList.isUndef())) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		zval *ancestorVariances = variancesOf(ancestorObject);
		if (UNEXPECTED(ancestorVariances == NULL)) return zv::Val();

		zv::Arr results = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(typeList.raw())) * 2);
		for (zv::ArrayEntry entry : zv::ArrRef(typeList.raw())) {
			zv::Ref templateType = entry.value().deref();
			zval *ancestorType = findKey(Z_ARRVAL_P(ancestorTypes), entry);
			if (ancestorType == NULL || Z_TYPE_P(ancestorType) == IS_NULL) continue;
			zval *thisType = findKey(Z_ARRVAL_P(types), entry);
			if (thisType == NULL || Z_TYPE_P(thisType) == IS_NULL) continue;
			bool isError;
			if (UNEXPECTED(!pt_type_instanceof(templateType.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
			if (isError) continue;
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(templateType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (!isTemplate) {
				pt_throw_should_not_happen();
				return zv::Val();
			}

			zv::Val thisVariance = varianceAt(Z_ARRVAL_P(variances), entry);
			if (UNEXPECTED(thisVariance.isUndef())) return zv::Val();
			zv::Val ancestorVariance = varianceAt(Z_ARRVAL_P(ancestorVariances), entry);
			if (UNEXPECTED(ancestorVariance.isUndef())) return zv::Val();
			bool invariant;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thisVariance.raw()), PT_LC("invariant"), 0, NULL, invariant))) return zv::Val();
			zv::Val validVariance;
			if (!invariant) {
				zv::Args args{templateType.raw(), thisType, ancestorType, strictVariance};
				validVariance = pt_type_call(Z_OBJ_P(thisVariance.raw()), PT_LC("isvalidvariance"), 4, args);
			} else {
				zv::Args args{thisType, ancestorType, strictVariance};
				validVariance = pt_type_call(templateType.asObject(), PT_LC("isvalidvariance"), 3, args);
			}
			if (UNEXPECTED(validVariance.isUndef())) return zv::Val();
			results.push(std::move(validVariance));

			bool validPosition;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thisVariance.raw()), PT_LC("validposition"), 1, ancestorVariance.raw(), validPosition))) return zv::Val();
			zv::Val positionResult = pt_type_is_super_type_of_result(validPosition ? PT_TRI_YES : PT_TRI_NO);
			if (UNEXPECTED(positionResult.isUndef())) return zv::Val();
			results.push(std::move(positionResult));
		}

		if (results.arrRef().size() == 0) return nakedSuperTypeOf;

		zv::Val result = pt_type_is_super_type_of_result(PT_TRI_YES);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(results.raw())) {
			if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
				zend_type_error("phpstan_turbo: and() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
				return zv::Val();
			}
			result = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, entry.value().raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		return result;
	}

	/* the constructor's reflection, else the provider's parameterized with
	 * the type arguments and variances (memoized); null for an unknown
	 * class; UNDEF = pending exception */
	zv::Val getClassReflection() const
	{
		zval *cached = classReflection();
		if (UNEXPECTED(cached == NULL)) return zv::Val();
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val provider = pt_type_call_static(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
		if (UNEXPECTED(provider.isUndef())) return zv::Val();
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, className.raw(), hasClass))) return zv::Val();
		if (!hasClass) return zv::Val::null();

		className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val reflection = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, className.raw());
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClass() must return an object");
			return zv::Val();
		}
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		reflection = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("withtypes"), 1, types);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: withTypes() must return an object");
			return zv::Val();
		}
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		reflection = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("withvariances"), 1, variances);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(PT_GOT_PROP_CLASS_REFLECTION, zv::Val::copyOf(zv::Ref(reflection.raw())));
		return reflection;
	}

	/* $this->getUnresolved*Prototype($name, $scope)->getTransformedProperty()
	 * / ->getTransformedMethod(); UNDEF = pending exception */
	zv::Val transformedMember(const char *prototypeLcname, size_t prototypeLen, zif_handler prototypeHandler, zv::Val (*parentBody)(zend_object *, zval *, zval *), bool isMethod, zval *name, zval *scope) const
	{
		zv::Args args{name, scope};
		zv::Val prototype = thisCall(prototypeLcname, prototypeLen, prototypeHandler, 2, args, [&]() { return unresolvedPrototype(parentBody, name, scope); });
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
			return zv::Val();
		}
		if (isMethod) return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedmethod"), 0, NULL);
		return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedproperty"), 0, NULL);
	}

	/* parent::getUnresolved*Prototype($name, $scope)->doNotResolveTemplateTypeMapToBounds();
	 * UNDEF = pending exception */
	zv::Val unresolvedPrototype(zv::Val (*parentBody)(zend_object *, zval *, zval *), zval *name, zval *scope) const
	{
		zv::Val prototype = parentBody(self, name, scope);
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
			zend_type_error("phpstan_turbo: the parent's prototype must be an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("donotresolvetemplatetypemaptobounds"), 0, NULL);
	}

	zv::Val getProperty(zval *name, zval *scope) const { return transformedMember(PT_LC("getunresolvedpropertyprototype"), gotGetUnresolvedPropertyPrototype, pt_object_type_get_unresolved_property_prototype, false, name, scope); }
	zv::Val getUnresolvedPropertyPrototype(zval *name, zval *scope) const { return unresolvedPrototype(pt_object_type_get_unresolved_property_prototype, name, scope); }
	zv::Val getInstanceProperty(zval *name, zval *scope) const { return transformedMember(PT_LC("getunresolvedinstancepropertyprototype"), gotGetUnresolvedInstancePropertyPrototype, pt_object_type_get_unresolved_instance_property_prototype, false, name, scope); }
	zv::Val getUnresolvedInstancePropertyPrototype(zval *name, zval *scope) const { return unresolvedPrototype(pt_object_type_get_unresolved_instance_property_prototype, name, scope); }
	zv::Val getStaticProperty(zval *name, zval *scope) const { return transformedMember(PT_LC("getunresolvedstaticpropertyprototype"), gotGetUnresolvedStaticPropertyPrototype, pt_object_type_get_unresolved_static_property_prototype, false, name, scope); }
	zv::Val getUnresolvedStaticPropertyPrototype(zval *name, zval *scope) const { return unresolvedPrototype(pt_object_type_get_unresolved_static_property_prototype, name, scope); }
	zv::Val getMethod(zval *name, zval *scope) const { return transformedMember(PT_LC("getunresolvedmethodprototype"), gotGetUnresolvedMethodPrototype, pt_object_type_get_unresolved_method_prototype, true, name, scope); }
	zv::Val getUnresolvedMethodPrototype(zval *name, zval *scope) const { return unresolvedPrototype(pt_object_type_get_unresolved_method_prototype, name, scope); }

	/* the template types inferred from the received type's ancestor of
	 * this class, argument by argument; UNDEF = pending exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool is;
		if (UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_UNION_TYPE, is))) return zv::Val();
		if (!is) {
			if (UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_INTERSECTION_TYPE, is))) return zv::Val();
		}
		if (is) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, &selfZv);
		}

		if (UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_TYPE_WITH_CLASS_NAME, is))) return zv::Val();
		if (!is) return emptyTemplateTypeMap();

		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val ancestor = pt_type_call(Z_OBJ_P(receivedType), PT_LC("getancestorwithclassname"), 1, className.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		if (ancestor.isNull()) return emptyTemplateTypeMap();
		zv::Val ancestorClassReflection = pt_type_call(Z_OBJ_P(ancestor.raw()), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(ancestorClassReflection.isUndef())) return zv::Val();
		if (ancestorClassReflection.isNull()) return emptyTemplateTypeMap();

		zv::Val otherTypes = templateTypeList(Z_OBJ_P(ancestorClassReflection.raw()), PT_LC("getactivetemplatetypemap"));
		if (UNEXPECTED(otherTypes.isUndef())) return zv::Val();
		zv::Val typeMap = emptyTemplateTypeMap();
		if (UNEXPECTED(typeMap.isUndef())) return zv::Val();

		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getTypes() must return array");
			return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Ref type = entry.value().deref();
			if (UNEXPECTED(!type.isObject())) {
				zend_type_error("phpstan_turbo: a type argument must be %s", ptcls::type);
				return zv::Val();
			}
			zval *found = findKey(Z_ARRVAL_P(otherTypes.raw()), entry);
			zv::Val other;
			if (found != NULL && Z_TYPE_P(found) != IS_NULL) {
				other = zv::Val::copyOf(zv::Ref(found));
			} else {
				other = pt_type_new_error_type();
				if (UNEXPECTED(other.isUndef())) return zv::Val();
			}
			zv::Val inferred = pt_type_call(type.asObject(), PT_LC("infertemplatetypes"), 1, other.raw());
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			typeMap = pt_type_call(Z_OBJ_P(typeMap.raw()), PT_LC("union"), 1, inferred.raw());
			if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(typeMap.raw()).isObject())) {
				zend_type_error("phpstan_turbo: union() must return an object");
				return zv::Val();
			}
		}

		return typeMap;
	}

	/* the template types the arguments reference, each at the composed
	 * variance; UNDEF = pending exception */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val typeList;
		if (!classReflection.isNull()) {
			typeList = templateTypeList(Z_OBJ_P(classReflection.raw()), PT_LC("gettemplatetypemap"));
			if (UNEXPECTED(typeList.isUndef())) return zv::Val();
		} else {
			typeList = zv::Val(zv::Arr::empty());
		}

		zv::Arr references = zv::Arr::create(4);
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref type = entry.value().deref();
			zv::Val effectiveVariance = varianceAt(Z_ARRVAL_P(variances), entry);
			if (UNEXPECTED(effectiveVariance.isUndef())) return zv::Val();
			bool invariant;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(effectiveVariance.raw()), PT_LC("invariant"), 0, NULL, invariant))) return zv::Val();
			if (invariant) {
				zval *templateType = findKey(Z_ARRVAL_P(typeList.raw()), entry);
				if (templateType != NULL && Z_TYPE_P(templateType) != IS_NULL) {
					bool isTemplate;
					if (UNEXPECTED(!pt_type_instanceof(templateType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
					if (isTemplate) {
						effectiveVariance = pt_type_call(Z_OBJ_P(templateType), PT_LC("getvariance"), 0, NULL);
						if (UNEXPECTED(effectiveVariance.isUndef())) return zv::Val();
					}
				}
			}

			zv::Val variance = pt_type_call(Z_OBJ_P(positionVariance), PT_LC("compose"), 1, effectiveVariance.raw());
			if (UNEXPECTED(variance.isUndef())) return zv::Val();
			if (UNEXPECTED(!type.isObject())) {
				zend_type_error("phpstan_turbo: a type argument must be %s", ptcls::type);
				return zv::Val();
			}
			zv::Val referenced = pt_type_call(type.asObject(), PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
			if (UNEXPECTED(referenced.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(referenced.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getReferencedTemplateTypes() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry reference : zv::ArrRef(referenced.raw())) {
				references.push(reference.value());
			}
		}

		return zv::Val(std::move(references));
	}

	/* $cb over the subtracted type and every type argument; $this when
	 * nothing changed, $this->recreate() otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val subtractedType = thisGetSubtractedType();
		if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
		zv::Val newSubtractedType;
		if (!subtractedType.isNull()) {
			subtractedType = thisGetSubtractedType();
			if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
			zval result;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtractedType.raw(), &result))) return zv::Val();
			newSubtractedType = zv::Val::adopt(result);
		} else {
			newSubtractedType = zv::Val::null();
		}

		bool typesChanged = false;
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Arr newTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types)));
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref type = entry.value().deref();
			zval result;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, type.raw(), &result))) return zv::Val();
			zv::Val newType = zv::Val::adopt(result);
			bool same = zend_is_identical(newType.raw(), type.raw());
			newTypes.push(std::move(newType));
			if (same) continue;

			typesChanged = true;
		}

		zv::Val currentSubtractedType = thisGetSubtractedType();
		if (UNEXPECTED(currentSubtractedType.isUndef())) return zv::Val();
		if (!zend_is_identical(newSubtractedType.raw(), currentSubtractedType.raw()) || typesChanged) {
			zval *variances = this->variances();
			if (UNEXPECTED(variances == NULL)) return zv::Val();
			return thisRecreate(newTypes.raw(), newSubtractedType.raw(), variances);
		}

		return thisValue();
	}

	/* $cb over the type arguments paired with the right type's ancestor's;
	 * $this when nothing changed or the right type has no such ancestor;
	 * UNDEF = pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		bool is;
		if (UNEXPECTED(!pt_type_instanceof(right, PT_CLASS_TYPE_WITH_CLASS_NAME, is))) return zv::Val();
		if (!is) return thisValue();

		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val ancestor = pt_type_call(Z_OBJ_P(right), PT_LC("getancestorwithclassname"), 1, className.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		if (!zv::Ref(ancestor.raw()).instanceOf(pt_ce_generic_object_type)) return thisValue();

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *ancestorTypes = typesOf(Z_OBJ_P(ancestor.raw()));
		if (UNEXPECTED(ancestorTypes == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) != zend_hash_num_elements(Z_ARRVAL_P(ancestorTypes))) return thisValue();

		bool typesChanged = false;
		zv::Arr newTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types)));
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref leftType = entry.value().deref();
			zv::Val rightType = readKey(Z_ARRVAL_P(ancestorTypes), entry);
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			zv::Args args{leftType.raw(), rightType.raw()};
			zval result;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &result))) return zv::Val();
			zv::Val newType = zv::Val::adopt(result);
			bool same = zend_is_identical(newType.raw(), leftType.raw());
			newTypes.push(std::move(newType));
			if (same) continue;

			typesChanged = true;
		}

		if (typesChanged) {
			zval null;
			ZVAL_NULL(&null);
			return thisRecreate(newTypes.raw(), &null, NULL);
		}

		return thisValue();
	}

	/* new self($className, $types, $subtractedType, null, $variances)
	 * ($subtractedType IS_NULL for null, $variances NULL for []) */
	static zv::Val recreate(zval *className, zval *types, zval *subtractedType, zval *variances)
	{
		zv::Str name = zv::Str::adopt(zval_get_string(className));
		return create(name.get(), types, Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType, NULL, variances);
	}

	/* $this->recreate($this->getClassName(), $this->getTypes(), $this->getSubtractedType(), $variances);
	 * UNDEF = pending exception */
	zv::Val changeVariances(zval *variances) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val subtractedType = thisGetSubtractedType();
		if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
		return thisRecreate(types.raw(), subtractedType.raw(), variances);
	}

	/* $this without the ClassReflection::asFinal() flavour of the
	 * constructor's reflection; UNDEF = pending exception */
	zv::Val withoutFinalByKeywordOverride() const
	{
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		bool override = false;
		if (Z_TYPE_P(reflection) == IS_OBJECT) {
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflection), PT_LC("hasfinalbykeywordoverride"), 0, NULL, override))) return zv::Val();
		}
		if (Z_TYPE_P(reflection) != IS_OBJECT || !override) return thisValue();

		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val subtractedType = thisGetSubtractedType();
		if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
		zv::Val withoutOverride = pt_type_call(Z_OBJ_P(reflection), PT_LC("withoutfinalbykeywordoverride"), 0, NULL);
		if (UNEXPECTED(withoutOverride.isUndef())) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		zv::Str name = zv::Str::adopt(zval_get_string(className.raw()));
		return create(name.get(), types, subtractedType.isNull() ? NULL : subtractedType.raw(), withoutOverride.raw(), variances);
	}

	/* the parent's sealed-hierarchy verdict when it changes the class, a
	 * new self with the type arguments otherwise; $subtractedType IS_NULL
	 * for null; UNDEF = pending exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		zv::Val result = pt_object_type_change_subtracted_type(self, subtractedType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		// Parent handles sealed type exhaustiveness (returning NeverType when all
		// allowed subtypes are subtracted, or a single remaining subtype).
		if (!zv::Ref(result.raw()).instanceOf(pt_ce_object_type)) return result;
		zv::Val resultClassName = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(resultClassName.isUndef())) return zv::Val();
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		if (!zend_is_identical(resultClassName.raw(), className.raw())) return result;

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		zv::Str name = zv::Str::adopt(zval_get_string(className.raw()));
		return create(name.get(), types, Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType, NULL, variances);
	}

	/* new GenericTypeNode(parent::toPhpDocNode(), the arguments' nodes, the
	 * variances' nodes); UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zv::Val parent = pt_object_type_to_php_doc_node(self);
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val genericTypes = mapped(types, PT_LC("tophpdocnode"));
		if (UNEXPECTED(genericTypes.isUndef())) return zv::Val();
		zval *variances = this->variances();
		if (UNEXPECTED(variances == NULL)) return zv::Val();
		zv::Val varianceNodes = mapped(variances, PT_LC("tophpdocnodevariance"));
		if (UNEXPECTED(varianceNodes.isUndef())) return zv::Val();
		zv::Args args{parent.raw(), genericTypes.raw(), varianceNodes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 3, args);
	}

	/* any argument's answer, else the subtracted type's; false with an
	 * exception pending */
	bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zv::Ref type = entry.value().deref();
			if (UNEXPECTED(!type.isObject())) {
				zend_type_error("phpstan_turbo: a type argument must be %s", ptcls::type);
				return false;
			}
			bool has;
			if (UNEXPECTED(!pt_type_call_bool(type.asObject(), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, has))) return false;
			if (!has) continue;

			out = true;
			return true;
		}

		zv::Val subtractedType = thisGetSubtractedType();
		if (UNEXPECTED(subtractedType.isUndef())) return false;
		if (subtractedType.isNull()) {
			out = false;
			return true;
		}

		subtractedType = thisGetSubtractedType();
		if (UNEXPECTED(subtractedType.isUndef())) return false;
		return pt_type_call_bool(Z_OBJ_P(subtractedType.raw()), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, out);
	}

private:
	zend_object *self;

	/* exactly a GenericObjectType, none of its methods overridden */
	bool isExact() const { return self->ce == pt_ce_generic_object_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t slot, zv::Val value) const
	{
		zval *p = OBJ_PROP_NUM(self, slot);
		zv::ObjRef(self).propAtWrite(slot, std::move(value));
		Z_PROP_FLAG_P(p) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* $this->method(...) through the object's class entry, straight to the
	 * C++ body when the object's method is the native one */
	template <typename Direct>
	zv::Val thisCall(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) const { return pt_this_call(self, isExact(), lcname, len, handler, argc, argv, direct); }

	/* $this->getClassName() / $this->getSubtractedType() — the parent's,
	 * straight to the slots when the object is exactly this class */
	zv::Val thisGetClassName() const
	{
		if (EXPECTED(isExact())) {
			zend_string *name = pt_object_type_class_name(self);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::string(name);
		}
		return pt_type_call(self, PT_LC("getclassname"), 0, NULL);
	}

	zv::Val thisGetSubtractedType() const
	{
		if (EXPECTED(isExact())) {
			zval *subtracted = pt_object_type_subtracted_type(self);
			if (UNEXPECTED(subtracted == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(subtracted));
		}
		return pt_type_call(self, PT_LC("getsubtractedtype"), 0, NULL);
	}

	zv::Val thisGetClassReflection() const { return thisCall(PT_LC("getclassreflection"), gotGetClassReflection, 0, NULL, [&]() { return getClassReflection(); }); }
	zv::Val thisGetTypes() const { return thisCall(PT_LC("gettypes"), gotGetTypes, 0, NULL, [&]() { return getTypes(); }); }

	/* $this->recreate($this->getClassName(), $types, $subtractedType, $variances)
	 * — protected, TemplateGenericObjectType overrides it; $variances NULL
	 * for the default []; UNDEF = pending exception */
	zv::Val thisRecreate(zval *types, zval *subtractedType, zval *variances) const
	{
		zv::Val className = thisGetClassName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zval args[4];
		ZVAL_COPY_VALUE(&args[0], className.raw());
		ZVAL_COPY_VALUE(&args[1], types);
		ZVAL_COPY_VALUE(&args[2], subtractedType);
		if (variances != NULL) {
			ZVAL_COPY_VALUE(&args[3], variances);
		} else {
			ZVAL_EMPTY_ARRAY(&args[3]);
		}
		return thisCall(PT_LC("recreate"), gotRecreate, variances != NULL ? 4 : 3, args, [&]() { return recreate(className.raw(), types, subtractedType, variances); });
	}

	/* $array[$key] for the key of an entry of another array (borrowed;
	 * NULL when absent) */
	static zval *findKey(HashTable *ht, const zv::ArrayEntry &entry)
	{
		if (entry.hasStringKey()) return zend_symtable_find(ht, entry.stringKey());
		return zend_hash_index_find(ht, entry.indexKey());
	}

	/* $array[$key] read the way PHP reads it: the engine's warning and null
	 * for a missing key; UNDEF = pending exception */
	static zv::Val readKey(HashTable *ht, const zv::ArrayEntry &entry)
	{
		zval *found = findKey(ht, entry);
		if (found == NULL) {
			if (entry.hasStringKey()) {
				zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(entry.stringKey()));
			} else {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, (zend_long) entry.indexKey());
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		return zv::Val::copyOf(zv::Ref(found).deref());
	}

	/* $variances[$i] ?? TemplateTypeVariance::createInvariant(); UNDEF =
	 * pending exception */
	static zv::Val varianceAt(HashTable *variances, const zv::ArrayEntry &entry)
	{
		zval *found = findKey(variances, entry);
		if (found != NULL && Z_TYPE_P(found) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(found) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: a variance must be %s", ptcls::templateTypeVariance);
				return zv::Val();
			}
			return zv::Val::copyOf(zv::Ref(found));
		}
		return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_VARIANCE, PT_LC("createinvariant"), 0, NULL);
	}

	/* $classReflection->typeMapToList($classReflection->get*TemplateTypeMap());
	 * an array, UNDEF = pending exception */
	static zv::Val templateTypeList(zend_object *classReflection, const char *mapLcname, size_t mapLen)
	{
		zv::Val map = pt_type_call(classReflection, mapLcname, mapLen, 0, NULL);
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		zv::Val list = pt_type_call(classReflection, PT_LC("typemaptolist"), 1, map.raw());
		if (UNEXPECTED(list.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(list.raw()).isArray())) {
			zend_type_error("phpstan_turbo: typeMapToList() must return array");
			return zv::Val();
		}
		return list;
	}

	/* TemplateTypeMap::createEmpty() */
	static zv::Val emptyTemplateTypeMap() { return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL); }

	/* $result->and(IsSuperTypeOfResult::createMaybe()); UNDEF = pending exception */
	static zv::Val andMaybe(zv::Val result)
	{
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, maybe.raw());
	}

	/* array_map(fn ($x) => $x->method(), $array) — keys kept, as
	 * array_map() over one array keeps them; UNDEF = pending exception */
	static zv::Val mapped(zval *array, const char *lcname, size_t len)
	{
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(array)));
		for (zv::ArrayEntry entry : zv::ArrRef(array)) {
			zv::Ref value = entry.value().deref();
			if (UNEXPECTED(!value.isObject())) {
				zend_type_error("phpstan_turbo: %s() needs an object", lcname);
				return zv::Val();
			}
			zv::Val mappedValue = pt_type_call(value.asObject(), lcname, len, 0, NULL);
			if (UNEXPECTED(mappedValue.isUndef())) return zv::Val();
			if (entry.hasStringKey()) {
				result.set(entry.stringKey(), std::move(mappedValue));
			} else {
				zval v = mappedValue.take();
				result.separate();
				zend_hash_index_update(result.table(), entry.indexKey(), &v);
			}
		}
		return zv::Val(std::move(result));
	}
};

} // namespace phpstanturbo

using phpstanturbo::GenericObjectType;

bool pt_generic_object_type_new(zval *out, zend_string *mainType, zval *types, zval *subtractedType, zval *classReflection, zval *variances)
{
	return pt_val_into(GenericObjectType::create(mainType, types, subtractedType, classReflection, variances), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS GenericObjectType(Z_OBJ_P(ZEND_THIS))

/* (string $name, ClassMemberAccessAnswerer $scope) → a value */
static void pt_got_member(INTERNAL_FUNCTION_PARAMETERS, zv::Val (GenericObjectType::*method)(zval *, zval *) const)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL((PT_THIS.*method)(&nameZv, scope));
}

/* the no-argument methods returning a value */
static void pt_got_value(INTERNAL_FUNCTION_PARAMETERS, zv::Val (GenericObjectType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (object $x) → a value */
static void pt_got_value_of(INTERNAL_FUNCTION_PARAMETERS, zv::Val (GenericObjectType::*method)(zval *) const)
{
	zval *x;
	if (!zp::parse<zp::Obj>(execute_data, x)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(x));
}

static void ZEND_FASTCALL gotGetClassReflection(INTERNAL_FUNCTION_PARAMETERS) { pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getClassReflection); }
static void ZEND_FASTCALL gotGetTypes(INTERNAL_FUNCTION_PARAMETERS) { pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getTypes); }

static void ZEND_FASTCALL gotRecreate(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *className;
	zval *types, *subtractedType, *variances = NULL;
	if (!zp::parse<zp::Str, zp::Arr, zp::ObjOrNull, zp::Opt<zp::Arr>>(execute_data, className, types, subtractedType, variances)) RETURN_THROWS();
	zval classNameZv, nullZv;
	ZVAL_STR(&classNameZv, className);
	if (subtractedType == NULL) {
		ZVAL_NULL(&nullZv);
		subtractedType = &nullZv;
	}
	PT_RETURN_VAL(GenericObjectType::recreate(&classNameZv, types, subtractedType, variances));
}

static void ZEND_FASTCALL gotGetUnresolvedPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getUnresolvedPropertyPrototype); }
static void ZEND_FASTCALL gotGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getUnresolvedInstancePropertyPrototype); }
static void ZEND_FASTCALL gotGetUnresolvedStaticPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getUnresolvedStaticPropertyPrototype); }
static void ZEND_FASTCALL gotGetUnresolvedMethodPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getUnresolvedMethodPrototype); }

void pt_register_generic_object_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\GenericObjectType");
	ptdecl::GenericObjectType::declareClass(cls);
	/* the twin's parameter order defines the PT_GOT_PROP_* slots (after
	 * the parent's) */
	ptdecl::GenericObjectType::declareProperties(cls);
	/* promoted parameters' defaults never reach the properties without
	 * the constructor: uninitialized, like the twin's */

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *mainType;
		zval *types, *subtractedType = NULL, *classReflection = NULL, *variances = NULL;
		if (!zp::parse<zp::Str, zp::Arr, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::Arr>>(execute_data, mainType, types, subtractedType, classReflection, variances)) RETURN_THROWS();
		PT_THIS.construct(mainType, types, subtractedType, classReflection, variances);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::describe);
	});

	cls.method<&GenericObjectType::equals, zp::Obj>(sigs::equals);

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getReferencedClasses);
	});
	cls.method(sigs::getTypes, gotGetTypes);
	cls.method(sigs::getVariances, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getVariances);
	});

	cls.method<&GenericObjectType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::isSuperTypeOf);
	});

	cls.method(sigs::getClassReflection, gotGetClassReflection);

	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getProperty);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, gotGetUnresolvedPropertyPrototype);
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getInstanceProperty);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, gotGetUnresolvedInstancePropertyPrototype);
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getStaticProperty);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, gotGetUnresolvedStaticPropertyPrototype);
	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getMethod);
	});
	cls.method(sigs::getUnresolvedMethodPrototype, gotGetUnresolvedMethodPrototype);

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::inferTemplateTypes);
	});
	cls.method(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value_of(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::getReferencedTemplateTypes);
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
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method(sigs::recreate, gotRecreate);
	cls.method<&GenericObjectType::changeVariances, zp::Arr>(sigs::changeVariances);
	cls.method(sigs::withoutFinalByKeywordOverride, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::withoutFinalByKeywordOverride);
	});
	cls.method(sigs::changeSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType;
		if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
		zval nullZv;
		if (subtractedType == NULL) {
			ZVAL_NULL(&nullZv);
			subtractedType = &nullZv;
		}
		PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
	});
	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_got_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &GenericObjectType::toPhpDocNode);
	});
	cls.method<&GenericObjectType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	cls.shadow(&pt_ce_generic_object_type);
}

/* }}} */
