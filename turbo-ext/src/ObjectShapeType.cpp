/*
 * PHPStanTurbo\ObjectShapeType — native implementation of
 * PHPStan\Type\ObjectShapeType.
 *
 * Declared as PHPStan\Type\ObjectShapeType itself at activation: not final
 * (the PHP TemplateObjectShapeType extends it — its constructor calls
 * parent::__construct(), so the constructor is a proper method),
 * implementing PHPStan\Type\Type. State is the twin's promoted `private
 * array $properties` and `private array $optionalProperties`, declared
 * typed property slots (IS_PROP_UNINIT until the constructor writes them)
 * in the twin's declaration order. The traits the twin is composed of come
 * from the shared registrars in TypeTraits.cpp (ObjectTypeTrait's `use`
 * chain spelled out), run after the class's own methods.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (TemplateObjectShapeType's
 * compound isSuperTypeOf()) — with a direct C++ call when the object is
 * exactly an ObjectShapeType. The private slots of another instance
 * (`$type->properties`) are read directly, as the twin does from inside the
 * class.
 *
 * The `static fn (string $reason) => sprintf(...)` callback accepts() hands
 * to AcceptsResult::decorateReasons() is a Closure over the __invoke() of
 * the internal PHPStanTurbo\ObjectShapeReasonDecorator holder, which keeps
 * the captured property name, the two types and the verbosity (an internal
 * detail with no PHP twin, like the generalize() holder in TypeTraits.cpp).
 */

#include "TypeTraits.h"
#include "generated/ObjectShapeType.h"

namespace slots = ptdecl::ObjectShapeType::slot;
namespace sigs = ptdecl::ObjectShapeType::sig;

zend_class_entry *pt_ce_object_shape_type = nullptr;

/* the reason decorator holder and its __invoke() */
static zend_class_entry *pt_ce_object_shape_reason_decorator = nullptr;
static zend_function *pt_object_shape_reason_decorator_invoke = nullptr;

#define PT_OSRD_PROP_OTHER_PROPERTY_TYPE 2
#define PT_OSRD_PROP_VERBOSITY 3

namespace phpstanturbo {

/* a $properties value the twin calls $method() on: the twin's
 * array<string, Type> is not checked when it is constructed, so a
 * non-object value surfaces at its first method call as the engine's Error;
 * NULL with that Error pending */
static zend_object *propertyTypeObject(zval *value, const char *method)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) return Z_OBJ_P(value);
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return NULL;
}

/* Mirrors PHPStan\Type\ObjectShapeType. State lives in the PHP object's
 * $properties and $optionalProperties. */
class ObjectShapeType
{
public:
	explicit ObjectShapeType(zend_object *self) : self(self) {}

	/* __construct(private array $properties, private array $optionalProperties);
	 * both borrowed */
	void construct(zval *properties, zval *optionalProperties)
	{
		writeSlot(slots::properties, properties);
		writeSlot(slots::optionalProperties, optionalProperties);
	}

	/* new self($properties, $optionalProperties) — exactly the class, as
	 * the twin's `new self` sites spell it; UNDEF = pending exception */
	static zv::Val create(zval *properties, zval *optionalProperties)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_object_shape_type) != SUCCESS)) return zv::Val();
		ObjectShapeType(Z_OBJ(object)).construct(properties, optionalProperties);
		return zv::Val::adopt(object);
	}

	/* $this->properties / $this->optionalProperties (borrowed arrays);
	 * NULL with an Error pending when the constructor never ran */
	[[nodiscard]] zval *properties() const { return propertiesOf(self); }
	zval *optionalProperties() const { return optionalPropertiesOf(self); }

	static zval *propertiesOf(zend_object *object) { return slotOf(object, slots::properties, "properties"); }
	static zval *optionalPropertiesOf(zend_object *object) { return slotOf(object, slots::optionalProperties, "optionalProperties"); }

	/* the referenced classes of every property type, concatenated; UNDEF
	 * = pending exception */
	zv::Val getReferencedClasses() const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Arr classes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "getReferencedClasses");
			if (UNEXPECTED(propertyType == NULL)) return zv::Val();
			zv::Val referenced = pt_type_call(propertyType, PT_LC("getreferencedclasses"), 0, NULL);
			if (UNEXPECTED(referenced.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(referenced.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getReferencedClasses() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry referencedClass : zv::ArrRef(referenced.raw())) {
				classes.push(referencedClass.value());
			}
		}
		return zv::Val(std::move(classes));
	}

	/* new GenericClassStringType($this) */
	zv::Val getClassStringType() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_type_new_ce(pt_ce_generic_class_string_type, 1, &selfZv);
	}

	/* no for an unknown property, maybe for an optional one, yes otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long hasInstanceProperty(zend_string *propertyName) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return -1;
		if (!zv::ArrRef(props).exists(propertyName)) return PT_TRI_NO;
		zval nameZv;
		ZVAL_STR(&nameZv, propertyName);
		bool optional;
		if (UNEXPECTED(!isOptional(&nameZv, optional))) return -1;
		return optional ? PT_TRI_MAYBE : PT_TRI_YES;
	}

	/* new CallbackUnresolvedPropertyPrototypeReflection(new
	 * ObjectShapePropertyReflection($name, $this->properties[$name]),
	 * $property->getDeclaringClass(), false, static fn (Type $type): Type => $type);
	 * UNDEF = pending exception */
	zv::Val getUnresolvedInstancePropertyPrototype(zend_string *propertyName) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Ref propertyType = zv::ArrRef(props).find(propertyName);
		if (propertyType.raw() == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval args[4];
		ZVAL_STR(&args[0], propertyName);
		ZVAL_COPY_VALUE(&args[1], propertyType.raw());
		zv::Val property = pt_type_new(PT_CLASS_OBJECT_SHAPE_PROPERTY_REFLECTION, 2, args);
		if (UNEXPECTED(property.isUndef())) return zv::Val();
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(property.raw()), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val callback = pt_type_identity_callback();
		ZVAL_COPY_VALUE(&args[0], property.raw());
		ZVAL_COPY_VALUE(&args[1], declaringClass.raw());
		ZVAL_FALSE(&args[2]);
		ZVAL_COPY_VALUE(&args[3], callback.raw());
		return pt_callback_unresolved_property_prototype_reflection_new(4, args);
	}

	/* the CompoundType callback; maybe for a universal object crate; else
	 * property by property: presence, publicness, staticness, readability
	 * and the accepted value type, each carrying its reason; UNDEF =
	 * pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		bool universalObjectCrate;
		if (UNEXPECTED(!isUniversalObjectCrate(type, universalObjectCrate))) return zv::Val();
		if (universalObjectCrate) return pt_type_accepts_result(PT_TRI_MAYBE);

		zv::Val result = pt_type_accepts_result(PT_TRI_YES);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval propertyName;
			keyValue(entry, &propertyName);
			zval *propertyType = entry.value().raw();
			zv::Val propertyNameString = keyString(entry);

			/* $typeHasProperty = $type->hasInstanceProperty((string) $propertyName) */
			zv::Val typeHasProperty = pt_type_call(Z_OBJ_P(type), PT_LC("hasinstanceproperty"), 1, propertyNameString.raw());
			if (UNEXPECTED(typeHasProperty.isUndef())) return zv::Val();
			zend_long typeHasPropertyValue = pt_type_trinary_value(typeHasProperty.raw());
			if (UNEXPECTED(typeHasPropertyValue < 0)) return zv::Val();
			zv::Val hasProperty = memberPresenceResult(pt_ce_accepts_result, type, typeHasProperty.raw(), typeHasPropertyValue, &propertyName);
			if (UNEXPECTED(hasProperty.isUndef())) return zv::Val();

			if (typeHasPropertyValue != PT_TRI_YES) {
				zend_long hasStatic = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasstaticproperty"), 1, propertyNameString.raw());
				if (UNEXPECTED(hasStatic < 0)) return zv::Val();
				if (hasStatic == PT_TRI_YES) {
					/* $type->getStaticProperty((string) $propertyName, $scope)->getDeclaringClass()->getDisplayName() */
					zv::Args args{propertyNameString.raw(), scope.raw()};
					zv::Val staticProperty = pt_type_call(Z_OBJ_P(type), PT_LC("getstaticproperty"), 2, args);
					if (UNEXPECTED(staticProperty.isUndef())) return zv::Val();
					zv::Val reason = memberReason("Property %s::$%s is static.", staticProperty.raw(), &propertyName);
					if (UNEXPECTED(reason.isUndef())) return zv::Val();
					zv::Val isStatic = acceptsResultNo(std::move(reason));
					if (UNEXPECTED(isStatic.isUndef())) return zv::Val();
					result = resultAnd(std::move(result), isStatic.raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					continue;
				}
			}
			bool optional;
			if (UNEXPECTED(!isOptional(&propertyName, optional))) return zv::Val();
			if (typeHasPropertyValue == PT_TRI_NO) {
				if (optional) continue;
				result = resultAnd(std::move(result), hasProperty.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				continue;
			}
			if (typeHasPropertyValue == PT_TRI_MAYBE) {
				if (!optional) {
					result = resultAnd(std::move(result), hasProperty.raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					continue;
				}
				hasProperty = pt_type_accepts_result(PT_TRI_YES);
				if (UNEXPECTED(hasProperty.isUndef())) return zv::Val();
			}

			result = resultAnd(std::move(result), hasProperty.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val otherProperty = instancePropertyOrMissing(type, propertyNameString.raw(), scope.raw());
			if (otherProperty.isUndef()) {
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue; /* MissingPropertyFromReflectionException */
			}

			zv::Val rejection = rejectMember(pt_ce_accepts_result, otherProperty.raw(), &propertyName);
			if (UNEXPECTED(rejection.isUndef())) return zv::Val();
			if (!rejection.isNull()) return rejection;

			zv::Val otherPropertyType = pt_type_call(Z_OBJ_P(otherProperty.raw()), PT_LC("getreadabletype"), 0, NULL);
			if (UNEXPECTED(otherPropertyType.isUndef())) return zv::Val();
			/* $verbosity = VerbosityLevel::getRecommendedLevelByType($propertyType, $otherPropertyType),
			 * whose `Type $acceptingType` parameter is the first to see the property type */
			bool propertyIsType;
			if (UNEXPECTED(!pt_type_instanceof(propertyType, PT_CLASS_TYPE, propertyIsType))) return zv::Val();
			if (UNEXPECTED(!propertyIsType)) {
				zend_type_error("%s::getRecommendedLevelByType(): Argument #1 ($acceptingType) must be of type %s, %s given", ZSTR_VAL(pt_ce_verbosity_level->name), ptcls::type, zend_zval_value_name(propertyType));
				return zv::Val();
			}
			zv::Val verbosity = pt_type_verbosity_recommended(propertyType, otherPropertyType.raw());
			if (UNEXPECTED(verbosity.isUndef())) return zv::Val();
			/* $propertyType->accepts($otherPropertyType, $strictTypes)->decorateReasons(...) */
			zv::Args acceptsArgs{otherPropertyType.raw(), strictTypes};
			zv::Val acceptsValue = pt_type_op(Z_OBJ_P(propertyType), PT_OP_ACCEPTS, 2, acceptsArgs);
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(acceptsValue.raw()).isObject())) {
				zend_type_error("phpstan_turbo: accepts() must return %s", ZSTR_VAL(pt_ce_accepts_result->name));
				return zv::Val();
			}
			zv::Val decorator = reasonDecorator(&propertyName, propertyType, otherPropertyType.raw(), verbosity.raw());
			acceptsValue = pt_type_call(Z_OBJ_P(acceptsValue.raw()), PT_LC("decoratereasons"), 1, decorator.raw());
			if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
			zend_long acceptsValueTrinary = pt_type_result_trinary(acceptsValue.raw());
			if (UNEXPECTED(acceptsValueTrinary < 0)) return zv::Val();
			if (acceptsValueTrinary != PT_TRI_YES) {
				bool noReasons;
				if (UNEXPECTED(!hasNoReasons(acceptsValue.raw(), noReasons))) return zv::Val();
				if (noReasons) {
					/* new AcceptsResult($acceptsValue->result, [sprintf('Property ($%s) type %s does not accept type %s.', ...)]) */
					zval rv;
		ZVAL_UNDEF(&rv);
				ZVAL_UNDEF(&rv);
					ZVAL_UNDEF(&rv);
					zval *trinary = zend_read_property(Z_OBJCE_P(acceptsValue.raw()), Z_OBJ_P(acceptsValue.raw()), PT_LC("result"), 0, &rv);
					if (UNEXPECTED(trinary == NULL || EG(exception))) return zv::Val();
					zv::Val trinaryCopy = zv::Val::copyOf(zv::Ref(trinary));
					zval_ptr_dtor(&rv);
					zv::Val reason = notAcceptedReason(&propertyName, propertyType, otherPropertyType.raw(), verbosity.raw(), NULL);
					if (UNEXPECTED(reason.isUndef())) return zv::Val();
					zv::Arr reasons = zv::Arr::create(1);
					reasons.push(std::move(reason));
					acceptsValue = acceptsResultCreate(trinaryCopy.raw(), std::move(reasons));
					if (UNEXPECTED(acceptsValue.isUndef())) return zv::Val();
				}
			}
			if (acceptsValueTrinary == PT_TRI_NO) return acceptsValue;
			result = resultAnd(std::move(result), acceptsValue.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		/* $result->and(new AcceptsResult($type->isObject(), [])) */
		zv::Val isObject = pt_type_call(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject.isUndef())) return zv::Val();
		zval objectResult;
		zval emptyReasons;
		ZVAL_EMPTY_ARRAY(&emptyReasons);
		if (UNEXPECTED(!pt_accepts_result_create(&objectResult, isObject.raw(), &emptyReasons))) return zv::Val();
		zv::Val objectResultVal = zv::Val::adopt(objectResult);
		return resultAnd(std::move(result), objectResultVal.raw());
	}

	/* the CompoundType callback; maybe for object and a universal object
	 * crate; else property by property as accepts() does, on the
	 * IsSuperTypeOfResult side; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		bool universalObjectCrate;
		if (UNEXPECTED(!isUniversalObjectCrate(type, universalObjectCrate))) return zv::Val();
		if (universalObjectCrate) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		zv::Val result = pt_type_is_super_type_of_result(PT_TRI_YES);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval propertyName;
			keyValue(entry, &propertyName);
			zval *propertyType = entry.value().raw();
			zv::Val propertyNameString = keyString(entry);

			zv::Val typeHasProperty = pt_type_call(Z_OBJ_P(type), PT_LC("hasinstanceproperty"), 1, propertyNameString.raw());
			if (UNEXPECTED(typeHasProperty.isUndef())) return zv::Val();
			zend_long typeHasPropertyValue = pt_type_trinary_value(typeHasProperty.raw());
			if (UNEXPECTED(typeHasPropertyValue < 0)) return zv::Val();
			zv::Val hasProperty = memberPresenceResult(pt_ce_is_super_type_of_result, type, typeHasProperty.raw(), typeHasPropertyValue, &propertyName);
			if (UNEXPECTED(hasProperty.isUndef())) return zv::Val();
			bool optional;
			if (UNEXPECTED(!isOptional(&propertyName, optional))) return zv::Val();
			if (typeHasPropertyValue == PT_TRI_NO) {
				if (optional) continue;
				result = resultAnd(std::move(result), hasProperty.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				continue;
			}
			if (typeHasPropertyValue == PT_TRI_MAYBE) {
				if (!optional) {
					result = resultAnd(std::move(result), hasProperty.raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					continue;
				}
				hasProperty = pt_type_is_super_type_of_result(PT_TRI_YES);
				if (UNEXPECTED(hasProperty.isUndef())) return zv::Val();
			}

			result = resultAnd(std::move(result), hasProperty.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val otherProperty = instancePropertyOrMissing(type, propertyNameString.raw(), scope.raw());
			if (otherProperty.isUndef()) {
				if (UNEXPECTED(EG(exception))) return zv::Val();
				continue; /* MissingPropertyFromReflectionException */
			}

			zv::Val rejection = rejectMember(pt_ce_is_super_type_of_result, otherProperty.raw(), &propertyName);
			if (UNEXPECTED(rejection.isUndef())) return zv::Val();
			if (!rejection.isNull()) return rejection;

			zv::Val otherPropertyType = pt_type_call(Z_OBJ_P(otherProperty.raw()), PT_LC("getreadabletype"), 0, NULL);
			if (UNEXPECTED(otherPropertyType.isUndef())) return zv::Val();
			zend_object *propertyTypeObj = propertyTypeObject(propertyType, "isSuperTypeOf");
			if (UNEXPECTED(propertyTypeObj == NULL)) return zv::Val();
			zv::Val isSuperType = pt_type_op(propertyTypeObj, PT_OP_IS_SUPER_TYPE_OF, 1, otherPropertyType.raw());
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(isSuperTypeValue < 0)) return zv::Val();
			if (isSuperTypeValue == PT_TRI_NO) return isSuperType;
			result = resultAnd(std::move(result), isSuperType.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		/* $result->and(new IsSuperTypeOfResult($type->isObject(), [])) */
		zv::Val isObject = pt_type_call(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], isObject.raw());
		ZVAL_EMPTY_ARRAY(&args[1]);
		zv::Val objectResult = pt_type_new_ce(pt_ce_is_super_type_of_result, 2, args);
		if (UNEXPECTED(objectResult.isUndef())) return zv::Val();
		return resultAnd(std::move(result), objectResult.raw());
	}

	/* another instance (of any subclass) with the same properties (each
	 * equal) and the same optional ones; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_object_shape_type)) {
			out = false;
			return true;
		}
		zval *props = properties();
		zval *typeProps = props != NULL ? propertiesOf(Z_OBJ_P(type)) : NULL;
		if (UNEXPECTED(typeProps == NULL)) return false;
		if (zv::ArrRef(props).size() != zv::ArrRef(typeProps).size()) {
			out = false;
			return true;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval *other = entry.hasStringKey()
				? zend_hash_find(Z_ARRVAL_P(typeProps), entry.stringKey())
				: zend_hash_index_find(Z_ARRVAL_P(typeProps), entry.indexKey());
			if (other == NULL) {
				out = false;
				return true;
			}
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "equals");
			if (UNEXPECTED(propertyType == NULL)) return false;
			zv::Val equal = pt_type_op(propertyType, PT_OP_EQUALS, 1, other);
			if (UNEXPECTED(equal.isUndef())) return false;
			if (!zend_is_true(equal.raw())) {
				out = false;
				return true;
			}
		}

		zval *optional = optionalProperties();
		zval *typeOptional = optional != NULL ? optionalPropertiesOf(Z_OBJ_P(type)) : NULL;
		if (UNEXPECTED(typeOptional == NULL)) return false;
		if (zv::ArrRef(optional).size() != zv::ArrRef(typeOptional).size()) {
			out = false;
			return true;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(optional)) {
			if (!inArrayStrict(entry.value().raw(), typeOptional)) {
				out = false;
				return true;
			}
		}
		out = true;
		return true;
	}

	/* the shape without the property a HasPropertyType names, null for
	 * anything else; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (!instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_has_property_type)) return zv::Val::null();
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		/* $properties = $this->properties; unset($properties[$typeToRemove->getPropertyName()]) */
		zv::Val propertyName = pt_type_call(Z_OBJ_P(typeToRemove), PT_LC("getpropertyname"), 0, NULL);
		if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(propertyName.raw()).isString())) {
			zend_type_error("phpstan_turbo: getPropertyName() must return string");
			return zv::Val();
		}
		zv::Arr remaining = zv::Arr::copyOfTable(Z_ARRVAL_P(props));
		remaining.separate();
		zend_symtable_del(remaining.table(), zv::Ref(propertyName.raw()).asString());
		/* array_values(array_filter($this->optionalProperties, static fn (int|string $propertyName) => $propertyName !== $typeToRemove->getPropertyName())) */
		zv::Val optional = optionalWithout(propertyName.raw(), true, typeToRemove);
		if (UNEXPECTED(optional.isUndef())) return zv::Val();
		return create(remaining.raw(), optional.raw());
	}

	/* the shape with the property no longer optional, $this for an unknown
	 * one; UNDEF = pending exception */
	zv::Val makePropertyRequired(zend_string *propertyName) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		if (!zv::ArrRef(props).exists(propertyName)) return thisValue();
		zval nameZv;
		ZVAL_STR(&nameZv, propertyName);
		zv::Val optional = optionalWithout(&nameZv, false, NULL);
		if (UNEXPECTED(optional.isUndef())) return zv::Val();
		return create(props, optional.raw());
	}

	/* the compound callback for a union/intersection; the union of each
	 * property's inference against the received shape's public non-static
	 * property; empty otherwise; UNDEF = pending exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool compound;
		if (UNEXPECTED(!pt_union_type_instanceof(receivedType, compound))) return zv::Val();
		if (!compound && UNEXPECTED(!pt_intersection_type_instanceof(receivedType, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, &selfZv);
		}

		if (instanceof_function(Z_OBJCE_P(receivedType), pt_ce_object_shape_type)) {
			zv::Val typeMap = pt_type_template_type_map_empty();
			if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
			zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zval *props = properties();
			if (UNEXPECTED(props == NULL)) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(props)) {
				zv::Val nameString = keyString(entry);
				zend_long hasProperty = pt_type_call_trinary(Z_OBJ_P(receivedType), PT_LC("hasinstanceproperty"), 1, nameString.raw());
				if (UNEXPECTED(hasProperty < 0)) return zv::Val();
				if (hasProperty == PT_TRI_NO) continue;
				zv::Val receivedProperty = instancePropertyOrMissing(receivedType, nameString.raw(), scope.raw());
				if (receivedProperty.isUndef()) {
					if (UNEXPECTED(EG(exception))) return zv::Val();
					continue;
				}
				bool skip;
				if (UNEXPECTED(!memberFlag(receivedProperty.raw(), PT_LC("ispublic"), skip))) return zv::Val();
				if (!skip) continue;
				if (UNEXPECTED(!memberFlag(receivedProperty.raw(), PT_LC("isstatic"), skip))) return zv::Val();
				if (skip) continue;
				zv::Val receivedPropertyType = pt_type_call(Z_OBJ_P(receivedProperty.raw()), PT_LC("getreadabletype"), 0, NULL);
				if (UNEXPECTED(receivedPropertyType.isUndef())) return zv::Val();
				zend_object *propertyType = propertyTypeObject(entry.value().raw(), "inferTemplateTypes");
				if (UNEXPECTED(propertyType == NULL)) return zv::Val();
				zv::Val inferred = pt_type_call(propertyType, PT_LC("infertemplatetypes"), 1, receivedPropertyType.raw());
				if (UNEXPECTED(inferred.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(typeMap.raw()).isObject())) {
					zend_type_error("phpstan_turbo: TemplateTypeMap expected");
					return zv::Val();
				}
				typeMap = pt_type_call(Z_OBJ_P(typeMap.raw()), PT_LC("union"), 1, inferred.raw());
				if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
			}
			return typeMap;
		}

		return pt_type_template_type_map_empty();
	}

	/* the references of every property type, in the position variance
	 * composed with covariant; UNDEF = pending exception */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val covariant = pt_type_template_type_variance(PT_TEMPLATE_TYPE_VARIANCE_COVARIANT);
		if (UNEXPECTED(covariant.isUndef())) return zv::Val();
		/* $positionVariance->compose(...) — the native body for the native class */
		zval composedVariance;
		if (UNEXPECTED(!pt_template_type_variance_compose(&composedVariance, positionVariance, covariant.raw()))) return zv::Val();
		zv::Val variance = zv::Val::adopt(composedVariance);
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Arr references = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "getReferencedTemplateTypes");
			if (UNEXPECTED(propertyType == NULL)) return zv::Val();
			zv::Val referenced = pt_type_op(propertyType, PT_OP_GET_REFERENCED_TEMPLATE_TYPES, 1, variance.raw());
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

	/* object{name: type, name?: type, ...} — every level describes the
	 * same way ($level->handle() with one callback for all); UNDEF =
	 * pending exception */
	zv::Val describe(zval *level) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		smart_str description = {NULL, 0};
		smart_str_appendl(&description, "object{", 7);
		bool first = true;
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval propertyName;
			keyValue(entry, &propertyName);
			bool optional;
			if (UNEXPECTED(!isOptional(&propertyName, optional))) {
				smart_str_free(&description);
				return zv::Val();
			}
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "describe");
			if (UNEXPECTED(propertyType == NULL)) {
				smart_str_free(&description);
				return zv::Val();
			}
			zv::Val item = pt_type_op(propertyType, PT_OP_DESCRIBE, 1, level);
			if (UNEXPECTED(item.isUndef())) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (UNEXPECTED(!zv::Ref(item.raw()).isString())) {
				smart_str_free(&description);
				zend_type_error("phpstan_turbo: describe() must return string");
				return zv::Val();
			}
			if (!first) {
				smart_str_appendl(&description, ", ", 2);
			}
			first = false;
			appendKey(&description, entry);
			if (optional) {
				smart_str_appendc(&description, '?');
			}
			smart_str_appendl(&description, ": ", 2);
			smart_str_append(&description, zv::Ref(item.raw()).asString());
		}
		smart_str_appendc(&description, '}');
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* new self with every property mapped by the callback, $this when the
	 * callback returned every property unchanged; UNDEF = pending
	 * exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Arr mapped = zv::Arr::create(zv::ArrRef(props).size());
		bool stillOriginal = true;
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval transformed;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, entry.value().raw(), &transformed))) return zv::Val();
			if (!zend_is_identical(&transformed, entry.value().raw())) { /* `$transformed !== $propertyType` */
				stillOriginal = false;
			}
			setEntry(mapped, entry, zv::Val::adopt(transformed));
		}
		if (stillOriginal) return thisValue();
		zval *optional = optionalProperties();
		if (UNEXPECTED(optional == NULL)) return zv::Val();
		return create(mapped.raw(), optional);
	}

	/* new self with every property mapped together with the right side's
	 * readable property type, $this when the right side is no object, lacks
	 * a property, or nothing changed; UNDEF = pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(right), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject != PT_TRI_YES) return thisValue();
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Arr mapped = zv::Arr::create(zv::ArrRef(props).size());
		bool stillOriginal = true;
		zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zv::Val nameString = keyString(entry);
			zend_long hasProperty = pt_type_call_trinary(Z_OBJ_P(right), PT_LC("hasinstanceproperty"), 1, nameString.raw());
			if (UNEXPECTED(hasProperty < 0)) return zv::Val();
			if (hasProperty != PT_TRI_YES) return thisValue();
			zv::Args args{nameString.raw(), scope.raw()};
			zv::Val rightProperty = pt_type_call(Z_OBJ_P(right), PT_LC("getinstanceproperty"), 2, args);
			if (UNEXPECTED(rightProperty.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(rightProperty.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getInstanceProperty() must return an object");
				return zv::Val();
			}
			zv::Val rightType = pt_type_call(Z_OBJ_P(rightProperty.raw()), PT_LC("getreadabletype"), 0, NULL);
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			zv::Args cbArgs{entry.value().raw(), rightType.raw()};
			zval transformed;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, cbArgs, &transformed))) return zv::Val();
			if (!zend_is_identical(&transformed, entry.value().raw())) { /* `$transformed !== $propertyType` */
				stillOriginal = false;
			}
			setEntry(mapped, entry, zv::Val::adopt(transformed));
		}
		if (stillOriginal) return thisValue();
		zval *optional = optionalProperties();
		if (UNEXPECTED(optional == NULL)) return zv::Val();
		return create(mapped.raw(), optional);
	}

	/* TypeCombinator::union($this, $exponent) unless the exponent is never
	 * or no subtype of $this, float|int (benevolent) otherwise; UNDEF =
	 * pending exception */
	zv::Val exponentiate(zval *exponent) const
	{
		if (!instanceof_function(Z_OBJCE_P(exponent), pt_ce_never_type)) {
			zv::Val isSuperType = isExact() ? isSuperTypeOf(exponent) : pt_type_op(self, PT_OP_IS_SUPER_TYPE_OF, 1, exponent);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value != PT_TRI_NO) {
				zv::Args args{self, exponent};
				return pt_type_combinator_call(PT_LC("union"), 2, args);
			}
		}
		/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
		zval floatRaw, integerRaw;
		if (UNEXPECTED(!pt_float_type_new(&floatRaw))) return zv::Val();
		zv::Val floatType = zv::Val::adopt(floatRaw);
		if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(floatType));
		types.push(zv::Val::adopt(integerRaw));
		return pt_union_benevolent_of(std::move(types));
	}

	/* new ObjectShapeNode([...]): an identifier key for a valid identifier,
	 * else the constant-string node's expression (skipped when that is no
	 * ConstTypeNode); UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Arr items = zv::Arr::create(zv::ArrRef(props).size());
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zval propertyName;
			keyValue(entry, &propertyName);
			zv::Val nameString = keyString(entry);
			bool isValid;
			if (UNEXPECTED(!pt_constant_array_type_is_valid_identifier(zv::Ref(nameString.raw()).asString(), isValid))) return zv::Val();
			zv::Val keyNode;
			if (isValid) {
				keyNode = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameString.raw());
				if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
			} else {
				/* (new ConstantStringType((string) $name))->toPhpDocNode() */
				zval constantStringRaw;
				if (UNEXPECTED(!pt_constant_string_type_new(&constantStringRaw, zv::Ref(nameString.raw()).asString()))) return zv::Val();
				zv::Val constantString = zv::Val::adopt(constantStringRaw);
				zv::Val keyPhpDocNode = pt_type_call(Z_OBJ_P(constantString.raw()), PT_LC("tophpdocnode"), 0, NULL);
				if (UNEXPECTED(keyPhpDocNode.isUndef())) return zv::Val();
				bool constTypeNode;
				if (UNEXPECTED(!pt_type_instanceof(keyPhpDocNode.raw(), PT_CLASS_CONST_TYPE_NODE, constTypeNode))) return zv::Val();
				if (!constTypeNode) continue;
				zval rv;
		ZVAL_UNDEF(&rv);
				ZVAL_UNDEF(&rv);
				zval *constExpr = zend_read_property(Z_OBJCE_P(keyPhpDocNode.raw()), Z_OBJ_P(keyPhpDocNode.raw()), PT_LC("constExpr"), 0, &rv);
				if (UNEXPECTED(constExpr == NULL || EG(exception))) return zv::Val();
				keyNode = zv::Val::copyOf(zv::Ref(constExpr));
				zval_ptr_dtor(&rv);
			}
			bool optional;
			if (UNEXPECTED(!isOptional(&propertyName, optional))) return zv::Val();
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "toPhpDocNode");
			if (UNEXPECTED(propertyType == NULL)) return zv::Val();
			zv::Val valueNode = pt_type_call(propertyType, PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
			zv::Args args{keyNode.raw(), optional, valueNode.raw()};
			zv::Val item = pt_type_new(PT_CLASS_OBJECT_SHAPE_ITEM_NODE, 3, args);
			if (UNEXPECTED(item.isUndef())) return zv::Val();
			items.push(std::move(item));
		}
		return pt_type_new(PT_CLASS_OBJECT_SHAPE_NODE, 1, items.raw());
	}

	/* whether any property type has one; false with an exception pending */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *props = properties();
		if (UNEXPECTED(props == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(props)) {
			zend_object *propertyType = propertyTypeObject(entry.value().raw(), "hasTemplateOrLateResolvableType");
			if (UNEXPECTED(propertyType == NULL)) return false;
			zv::Val has = pt_type_op(propertyType, PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(has.isUndef())) return false;
			if (zend_is_true(has.raw())) {
				out = true;
				return true;
			}
		}
		out = false;
		return true;
	}

	/* the decorator holder's __invoke(string $reason): sprintf('Property
	 * ($%s) type %s does not accept type %s: %s', ...) over the captured
	 * values; UNDEF = pending exception */
	static zv::Val decorateReason(zend_object *holder, zend_string *reason)
	{
		return notAcceptedReason(
			OBJ_PROP_NUM(holder, slots::properties),
			OBJ_PROP_NUM(holder, slots::optionalProperties),
			OBJ_PROP_NUM(holder, PT_OSRD_PROP_OTHER_PROPERTY_TYPE),
			OBJ_PROP_NUM(holder, PT_OSRD_PROP_VERBOSITY),
			reason
		);
	}

private:
	zend_object *self;

	/* exactly an ObjectShapeType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_object_shape_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t slot, zval *value)
	{
		zval *p = OBJ_PROP_NUM(self, slot);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, p);
		ZVAL_COPY(p, value);
		Z_PROP_FLAG_P(p) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	static zval *slotOf(zend_object *object, uint32_t slot, const char *name)
	{
		zval *p = OBJ_PROP_NUM(object, slot);
		if (UNEXPECTED(Z_TYPE_P(p) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_object_shape_type->name), name);
			return NULL;
		}
		if (UNEXPECTED(Z_TYPE_P(p) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: %s::$%s must be array", ZSTR_VAL(pt_ce_object_shape_type->name), name);
			return NULL;
		}
		return p;
	}

	/* new AcceptsResult($trinary, $reasons) — the reasons array consumed;
	 * UNDEF = pending exception */
	static zv::Val acceptsResultCreate(zval *trinary, zv::Arr reasons)
	{
		zval reasonsZv = reasons.take();
		zval created;
		if (UNEXPECTED(!pt_accepts_result_create(&created, trinary, &reasonsZv))) return zv::Val();
		return zv::Val::adopt(created);
	}

	/* the array key as the int|string zval PHP's foreach yields (borrowed
	 * string, no addref) */
	static void keyValue(const zv::ArrayEntry &entry, zval *out)
	{
		if (entry.hasStringKey()) {
			ZVAL_STR(out, entry.stringKey());
		} else {
			ZVAL_LONG(out, (zend_long) entry.indexKey());
		}
	}

	/* (string) $name */
	static zv::Val keyString(const zv::ArrayEntry &entry)
	{
		if (entry.hasStringKey()) return zv::Val::string(entry.stringKey());
		return zv::Val::adoptString(zend_long_to_str((zend_long) entry.indexKey()));
	}

	/* sprintf('%s', $name) */
	static void appendKey(smart_str *str, const zv::ArrayEntry &entry)
	{
		if (entry.hasStringKey()) {
			smart_str_append(str, entry.stringKey());
		} else {
			smart_str_append_long(str, (zend_long) entry.indexKey());
		}
	}

	/* $array[$key] = $value with the entry's own key */
	static void setEntry(zv::Arr &array, const zv::ArrayEntry &entry, zv::Val value)
	{
		zval v = value.take();
		if (entry.hasStringKey()) {
			zend_hash_update(array.table(), entry.stringKey(), &v);
		} else {
			zend_hash_index_update(array.table(), entry.indexKey(), &v);
		}
	}

	/* in_array($needle, $haystack, true) */
	static bool inArrayStrict(zval *needle, zval *haystack)
	{
		for (zv::ArrayEntry entry : zv::ArrRef(haystack)) {
			if (zend_is_identical(needle, entry.value().raw())) return true;
		}
		return false;
	}

	/* in_array($propertyName, $this->optionalProperties, true); false with
	 * an exception pending */
	bool isOptional(zval *propertyName, bool &out) const
	{
		zval *optional = optionalProperties();
		if (UNEXPECTED(optional == NULL)) return false;
		out = inArrayStrict(propertyName, optional);
		return true;
	}

	/* array_values(array_filter($this->optionalProperties, static fn
	 * (int|string $name) => $name !== $excluded)); the excluded name is
	 * re-read from $typeToRemove->getPropertyName() per element when given,
	 * as the twin's closure does; UNDEF = pending exception */
	zv::Val optionalWithout(zval *excluded, bool reread, zval *typeToRemove) const
	{
		zval *optional = optionalProperties();
		if (UNEXPECTED(optional == NULL)) return zv::Val();
		zv::Arr remaining = zv::Arr::create(zv::ArrRef(optional).size());
		for (zv::ArrayEntry entry : zv::ArrRef(optional)) {
			zv::Val current;
			zval *against = excluded;
			if (reread) {
				current = pt_type_call(Z_OBJ_P(typeToRemove), PT_LC("getpropertyname"), 0, NULL);
				if (UNEXPECTED(current.isUndef())) return zv::Val();
				against = current.raw();
			}
			if (zend_is_identical(entry.value().raw(), against)) continue;
			remaining.push(entry.value());
		}
		return zv::Val(std::move(remaining));
	}

	/* whether any of $type->getObjectClassReflections() is a universal
	 * object crate (UniversalObjectCratesClassReflectionExtension over the
	 * static reflection provider); false with an exception pending */
	[[nodiscard]] static bool isUniversalObjectCrate(zval *type, bool &out)
	{
		zv::Val reflectionProvider = pt_reflection_provider_instance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return false;
		zv::Val reflections = pt_type_call(Z_OBJ_P(type), PT_LC("getobjectclassreflections"), 0, NULL);
		if (UNEXPECTED(reflections.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(reflections.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassReflections() must return array");
			return false;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(reflections.raw())) {
			zv::Args args{reflectionProvider.raw(), entry.value().raw()};
			zv::Val isCrate = pt_type_call_static(PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION, PT_LC("isuniversalobjectcrate"), 2, args);
			if (UNEXPECTED(isCrate.isUndef())) return false;
			if (zend_is_true(isCrate.raw())) {
				out = true;
				return true;
			}
		}
		out = false;
		return true;
	}

	/* new <Result>($typeHasProperty, $typeHasProperty->yes() ? [] :
	 * [sprintf('%s %s have property $%s.', $type->describe(VerbosityLevel::typeOnly()),
	 * $typeHasProperty->no() ? 'does not' : 'might not', $propertyName)]);
	 * UNDEF = pending exception */
	static zv::Val memberPresenceResult(zend_class_entry *resultCe, zval *type, zval *typeHasProperty, zend_long value, zval *propertyName)
	{
		zv::Arr reasons;
		if (value == PT_TRI_YES) {
			reasons = zv::Arr::empty();
		} else {
			zv::Val typeOnly = pt_type_verbosity_level(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
			zv::Val description = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, typeOnly.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
				zend_type_error("phpstan_turbo: describe() must return string");
				return zv::Val();
			}
			smart_str reason = {NULL, 0};
			smart_str_append(&reason, zv::Ref(description.raw()).asString());
			if (value == PT_TRI_NO) {
				smart_str_appendl(&reason, " does not have property $", sizeof(" does not have property $") - 1);
			} else {
				smart_str_appendl(&reason, " might not have property $", sizeof(" might not have property $") - 1);
			}
			appendName(&reason, propertyName);
			smart_str_appendc(&reason, '.');
			smart_str_0(&reason);
			reasons = zv::Arr::create(1);
			reasons.push(zv::Val::adoptString(reason.s));
		}
		if (resultCe == pt_ce_accepts_result) return acceptsResultCreate(typeHasProperty, std::move(reasons));
		zv::Args args{typeHasProperty, reasons.raw()};
		return pt_type_new_ce(resultCe, 2, args);
	}

	/* sprintf('%s', $name) for an int|string name */
	static void appendName(smart_str *str, zval *name)
	{
		if (Z_TYPE_P(name) == IS_STRING) {
			smart_str_append(str, Z_STR_P(name));
		} else {
			smart_str_append_long(str, Z_LVAL_P(name));
		}
	}

	/* sprintf($format, $member->getDeclaringClass()->getDisplayName(),
	 * $propertyName) for a '%s::$%s' format; UNDEF = pending exception */
	static zv::Val memberReason(const char *format, zval *member, zval *propertyName)
	{
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(member), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(declaringClass.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getDeclaringClass() must return an object");
			return zv::Val();
		}
		zv::Val displayName = pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getdisplayname"), 0, NULL);
		if (UNEXPECTED(displayName.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(displayName.raw()).isString())) {
			zend_type_error("phpstan_turbo: getDisplayName() must return string");
			return zv::Val();
		}
		/* the format is 'Property %s::$%s <verdict>.' */
		const char *first = strstr(format, "%s");
		const char *second = strstr(first + 2, "%s");
		smart_str reason = {NULL, 0};
		smart_str_appendl(&reason, format, (size_t) (first - format));
		smart_str_append(&reason, zv::Ref(displayName.raw()).asString());
		smart_str_appendl(&reason, first + 2, (size_t) (second - (first + 2)));
		appendName(&reason, propertyName);
		smart_str_appends(&reason, second + 2);
		smart_str_0(&reason);
		return zv::Val::adoptString(reason.s);
	}

	/* new AcceptsResult(TrinaryLogic::createNo(), [$reason]) — the reasons
	 * array consumed */
	static zv::Val acceptsResultNo(zv::Val reason)
	{
		zv::Arr reasons = zv::Arr::create(1);
		reasons.push(std::move(reason));
		return acceptsResultCreate(pt_trinary_singleton(PT_TRI_NO), std::move(reasons));
	}

	/* IsSuperTypeOfResult::createNo([$reason]) */
	static zv::Val isSuperTypeOfResultNo(zv::Val reason)
	{
		zv::Arr reasons = zv::Arr::create(1);
		reasons.push(std::move(reason));
		return pt_type_call_static_ce(pt_ce_is_super_type_of_result, PT_LC("createno"), 1, reasons.raw());
	}

	/* the no result for a property that is not public, static, or not
	 * readable — with its reason; null when the property passes; UNDEF =
	 * pending exception */
	static zv::Val rejectMember(zend_class_entry *resultCe, zval *member, zval *propertyName)
	{
		bool flag;
		if (UNEXPECTED(!memberFlag(member, PT_LC("ispublic"), flag))) return zv::Val();
		const char *format = NULL;
		if (!flag) {
			format = "Property %s::$%s is not public.";
		} else {
			if (UNEXPECTED(!memberFlag(member, PT_LC("isstatic"), flag))) return zv::Val();
			if (flag) {
				format = "Property %s::$%s is static.";
			} else {
				if (UNEXPECTED(!memberFlag(member, PT_LC("isreadable"), flag))) return zv::Val();
				if (!flag) {
					format = "Property %s::$%s is not readable.";
				}
			}
		}
		if (format == NULL) return zv::Val::null();
		zv::Val reason = memberReason(format, member, propertyName);
		if (UNEXPECTED(reason.isUndef())) return zv::Val();
		if (resultCe == pt_ce_accepts_result) return acceptsResultNo(std::move(reason));
		return isSuperTypeOfResultNo(std::move(reason));
	}

	/* $member->isPublic() and friends; false with an exception pending */
	[[nodiscard]] static bool memberFlag(zval *member, const char *lcname, size_t len, bool &out)
	{
		zv::Val flag = pt_type_call(Z_OBJ_P(member), lcname, len, 0, NULL);
		if (UNEXPECTED(flag.isUndef())) return false;
		out = zend_is_true(flag.raw());
		return true;
	}

	/* $type->getInstanceProperty($name, $scope), UNDEF without an exception
	 * pending when it threw MissingPropertyFromReflectionException (the
	 * twin's catch); UNDEF with one otherwise */
	static zv::Val instancePropertyOrMissing(zval *type, zval *name, zval *scope)
	{
		zv::Args args{name, scope};
		zv::Val property = pt_type_call(Z_OBJ_P(type), PT_LC("getinstanceproperty"), 2, args);
		if (UNEXPECTED(property.isUndef())) {
			if (EG(exception) != NULL) {
				zend_class_entry *missingCe = pt_class(PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION);
				if (missingCe != NULL && instanceof_function(EG(exception)->ce, missingCe)) {
					zend_clear_exception();
				}
			}
			return zv::Val();
		}
		if (UNEXPECTED(!zv::Ref(property.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getInstanceProperty() must return an object");
			return zv::Val();
		}
		return property;
	}

	/* $result->and($other) — through the result's class; UNDEF = pending
	 * exception */
	static zv::Val resultAnd(zv::Val result, zval *other)
	{
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: a result object expected");
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, other);
	}

	/* count($result->reasons) === 0; false with an exception pending */
	[[nodiscard]] static bool hasNoReasons(zval *result, bool &out)
	{
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *reasons = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("reasons"), 0, &rv);
		if (UNEXPECTED(reasons == NULL || EG(exception))) return false;
		out = Z_TYPE_P(reasons) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(reasons)) == 0;
		zval_ptr_dtor(&rv);
		return true;
	}

	/* sprintf('Property ($%s) type %s does not accept type %s: %s', $name,
	 * $propertyType->describe($verbosity), $otherPropertyType->describe($verbosity),
	 * $reason) — without the ': %s' tail when $reason is NULL; UNDEF =
	 * pending exception */
	static zv::Val notAcceptedReason(zval *propertyName, zval *propertyType, zval *otherPropertyType, zval *verbosity, zend_string *reason)
	{
		zv::Val propertyDescription = pt_type_op(Z_OBJ_P(propertyType), PT_OP_DESCRIBE, 1, verbosity);
		if (UNEXPECTED(propertyDescription.isUndef())) return zv::Val();
		zv::Val otherDescription = pt_type_op(Z_OBJ_P(otherPropertyType), PT_OP_DESCRIBE, 1, verbosity);
		if (UNEXPECTED(otherDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(propertyDescription.raw()).isString() || !zv::Ref(otherDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		smart_str str = {NULL, 0};
		smart_str_appendl(&str, "Property ($", sizeof("Property ($") - 1);
		appendName(&str, propertyName);
		smart_str_appendl(&str, ") type ", sizeof(") type ") - 1);
		smart_str_append(&str, zv::Ref(propertyDescription.raw()).asString());
		smart_str_appendl(&str, " does not accept type ", sizeof(" does not accept type ") - 1);
		smart_str_append(&str, zv::Ref(otherDescription.raw()).asString());
		if (reason != NULL) {
			smart_str_appendl(&str, ": ", 2);
			smart_str_append(&str, reason);
		} else {
			smart_str_appendc(&str, '.');
		}
		smart_str_0(&str);
		return zv::Val::adoptString(str.s);
	}

	/* the `static fn (string $reason) => sprintf(...)` closure over a
	 * holder of the captured values */
	static zv::Val reasonDecorator(zval *propertyName, zval *propertyType, zval *otherPropertyType, zval *verbosity)
	{
		zval holder;
		object_init_ex(&holder, pt_ce_object_shape_reason_decorator);
		zv::ObjRef holderRef(&holder);
		holderRef.propAtWrite(slots::properties, zv::Val::copyOf(zv::Ref(propertyName)));
		holderRef.propAtWrite(slots::optionalProperties, zv::Val::copyOf(zv::Ref(propertyType)));
		holderRef.propAtWrite(PT_OSRD_PROP_OTHER_PROPERTY_TYPE, zv::Val::copyOf(zv::Ref(otherPropertyType)));
		holderRef.propAtWrite(PT_OSRD_PROP_VERBOSITY, zv::Val::copyOf(zv::Ref(verbosity)));
		zv::Val closure = pt_type_closure_over(pt_object_shape_reason_decorator_invoke, pt_ce_object_shape_reason_decorator, Z_OBJ(holder));
		zval_ptr_dtor(&holder); /* the closure holds its own reference */
		return closure;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ObjectShapeType;

bool pt_object_shape_type_new(zval *out, zval *properties, zval *optionalProperties)
{
	return pt_val_into(ObjectShapeType::create(properties, optionalProperties), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ObjectShapeType(Z_OBJ_P(ZEND_THIS))

/* ObjectShapeReasonDecorator::__invoke(string $reason): string */
static void ZEND_FASTCALL invokeReasonDecorator(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *reason;
	if (!zp::parse<zp::Str>(execute_data, reason)) RETURN_THROWS();
	if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: reason decorator called without its holder");
		RETURN_THROWS();
	}
	PT_RETURN_VAL(ObjectShapeType::decorateReason(Z_OBJ_P(ZEND_THIS), reason));
}

static void ZEND_FASTCALL ostEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL ostNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ostShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

/* hasProperty() / hasInstanceProperty(): (string $propertyName) */
static void ZEND_FASTCALL ostHasInstanceProperty(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *propertyName;
	if (!zp::parse<zp::Str>(execute_data, propertyName)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasInstanceProperty(propertyName));
}

/* getProperty() / getInstanceProperty(): the transformed property of
 * $this->getUnresolvedInstancePropertyPrototype() — the property one goes
 * through getUnresolvedPropertyPrototype(), each through the object's class */
static void ostTransformedProperty(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_transformed_member(Z_OBJ_P(ZEND_THIS), prototypeLcname, prototypeLen, false, name, scope));
}

/* getUnresolvedPropertyPrototype() / getUnresolvedInstancePropertyPrototype():
 * the former is $this->getUnresolvedInstancePropertyPrototype() through the
 * object's class */
static void ZEND_FASTCALL ostUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *propertyName;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, propertyName, scope)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.getUnresolvedInstancePropertyPrototype(propertyName));
}

void pt_register_object_shape_type()
{
	/* the reason decorator holder: registered under a builder name other
	 * than `cls` on purpose — the side-by-side parity scan pairs
	 * `cls.method(...)` lines with the twin's methods, and __invoke() has
	 * none */
	reg::Class holder("PHPStanTurbo\\ObjectShapeReasonDecorator");
	holder.privateNullProperty("propertyName");
	holder.privateNullProperty("propertyType");
	holder.privateNullProperty("otherPropertyType");
	holder.privateNullProperty("verbosity");
	holder.method("__invoke", reg::Public, 1, { reg::stringArg("reason") }, invokeReasonDecorator, &ptret::string);
	pt_ce_object_shape_reason_decorator = holder.register_();
	pt_ce_object_shape_reason_decorator->ce_flags |= ZEND_ACC_FINAL;
	pt_object_shape_reason_decorator_invoke = (zend_function *) zend_hash_str_find_ptr(&pt_ce_object_shape_reason_decorator->function_table, PT_LC("__invoke"));
	ZEND_ASSERT(pt_object_shape_reason_decorator_invoke != NULL);

	reg::Class cls("PHPStan\\Type\\ObjectShapeType");
	ptdecl::ObjectShapeType::declareClass(cls);
	/* "properties" and "optionalProperties" must stay the first two
	 * declared properties (slots::properties, slots::optionalProperties) */
	ptdecl::ObjectShapeType::declareProperties(cls);

	cls.method<&ObjectShapeType::construct, zp::Arr, zp::Arr>(sigs::__construct);

	cls.method(sigs::getProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *properties = PT_THIS.properties();
		if (UNEXPECTED(properties == NULL)) RETURN_THROWS();
		RETURN_COPY(properties);
	});

	cls.method(sigs::getOptionalProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *optionalProperties = PT_THIS.optionalProperties();
		if (UNEXPECTED(optionalProperties == NULL)) RETURN_THROWS();
		RETURN_COPY(optionalProperties);
	});

	cls.method<&ObjectShapeType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &ObjectShapeType::getReferencedClasses>();

	cls.method(sigs::getObjectClassNames, ostEmptyArray0);
	cls.method(sigs::getObjectClassReflections, ostEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });

	cls.method<&ObjectShapeType::getClassStringType>(sigs::getClassStringType);

	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		/* $this->hasInstanceProperty($propertyName) — through the object's class */
		zval *propertyName;
		if (!zp::parse<zp::Zval>(execute_data, propertyName)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("hasinstanceproperty"), 1, propertyName));
	});

	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		/* $this->getInstanceProperty($propertyName, $scope) — through the object's class */
		zval *propertyName, *scope;
		if (!zp::parse<zp::Zval, zp::Obj>(execute_data, propertyName, scope)) RETURN_THROWS();
		zv::Args args{propertyName, scope};
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getinstanceproperty"), 2, args));
	});

	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		/* $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope) — through the object's class */
		zval *propertyName, *scope;
		if (!zp::parse<zp::Zval, zp::Obj>(execute_data, propertyName, scope)) RETURN_THROWS();
		zv::Args args{propertyName, scope};
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getunresolvedinstancepropertyprototype"), 2, args));
	});

	cls.method(sigs::hasInstanceProperty, ostHasInstanceProperty);
	cls.op<PT_OP_HAS_INSTANCE_PROPERTY, &ObjectShapeType::hasInstanceProperty>();

	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ostTransformedProperty(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"));
	});

	cls.method(sigs::getUnresolvedInstancePropertyPrototype, ostUnresolvedInstancePropertyPrototype);
	cls.op(PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, PT_OP_LAMBDA { return ObjectShapeType(self).getUnresolvedInstancePropertyPrototype(Z_STR(argv[0])); });

	cls.method(sigs::hasStaticProperty, ostNo1);
	cls.method(sigs::getStaticProperty, ostShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, ostShouldNotHappen2);

	cls.method<&ObjectShapeType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&ObjectShapeType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ObjectShapeType::equals, zp::TypeObj>(sigs::equals);

	cls.method<&ObjectShapeType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&ObjectShapeType::makePropertyRequired, zp::Str>(sigs::makePropertyRequired);

	cls.method<&ObjectShapeType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&ObjectShapeType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&ObjectShapeType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::getEnumCases, ostEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });

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

	cls.method<&ObjectShapeType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method(sigs::getFiniteTypes, ostEmptyArray0);

	cls.method<&ObjectShapeType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&ObjectShapeType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	/* the traits, in the twin's `use` order (ObjectTypeTrait brings the
	 * MaybeCallable, MaybeIterable, MaybeOffsetAccessible, NonArray and
	 * TruthyBoolean traits with it); the class body above wins over every
	 * name it declares */
	pt_type_trait_object(cls);
	pt_type_trait_maybe_callable(cls);
	pt_type_trait_maybe_iterable(cls);
	pt_type_trait_maybe_offset_accessible(cls);
	pt_type_trait_non_array(cls);
	pt_type_trait_truthy_boolean(cls);
	pt_type_trait_undecided_comparison(cls);
	pt_type_trait_non_generalizable(cls);

	cls.shadow(&pt_ce_object_shape_type);
}

/* }}} */
