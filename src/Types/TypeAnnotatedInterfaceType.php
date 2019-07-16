<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Types;

use function array_merge;
use function get_class;
use function get_declared_interfaces;
use InvalidArgumentException;
use function is_object;
use ReflectionClass;
use TheCodingMachine\GraphQLite\FieldsBuilder;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapperInterface;
use function get_parent_class;
use TheCodingMachine\GraphQLite\Reflection\ReflectionInterfaceUtils;

/**
 * An object type built from the Type annotation
 */
class TypeAnnotatedInterfaceType extends MutableInterfaceType
{
    /**
     * @param mixed[] $config
     */
    public function __construct(string $className, array $config)
    {
        parent::__construct($config, $className);
    }

    public static function createFromAnnotatedClass(string $typeName, string $className, ?object $annotatedObject, FieldsBuilder $fieldsBuilder, RecursiveTypeMapperInterface $recursiveTypeMapper, bool $doNotMapInterfaces, bool $disableInheritance): self
    {
        return new self($className, [
            'name' => $typeName,
            'fields' => static function () use ($annotatedObject, $recursiveTypeMapper, $className, $fieldsBuilder, $disableInheritance) {

                $interfaces = ReflectionInterfaceUtils::getDirectlyImplementedInterfaces(new ReflectionClass($className));

                $fieldsArray = [];
                foreach ($interfaces as $interfaceName => $interface) {
                    if ($recursiveTypeMapper->canMapClassToType($interfaceName)) {
                        $interfaceType = $recursiveTypeMapper->mapClassToType($interfaceName, null);
                        $fieldsArray[] = $interfaceType->getFields();
                    }
                }

                if (!empty($fieldsArray)) {
                    $interfaceFields = array_merge(...$fieldsArray);
                } else {
                    $interfaceFields = [];
                }

                if ($annotatedObject !== null) {
                    $fields = $fieldsBuilder->getFields($annotatedObject);
                } else {
                    $fields = $fieldsBuilder->getSelfFields($className);
                }

                $fields += $interfaceFields;

                return $fields;
            },
            'resolve' => static function ($value) use ($recursiveTypeMapper) {
                if (! is_object($value)) {
                    throw new InvalidArgumentException('Expected object for resolveType. Got: "' . gettype($value) . '"');
                }

                $className = get_class($value);

                return $recursiveTypeMapper->mapClassToType($className, null);
            },
        ]);
    }
}
