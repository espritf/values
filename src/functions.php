<?php
namespace Makasim\Values;

/**
 * @param object $object
 * @param array $values
 * @param bool $byReference
 *
 * @return object
 */
function set_values($object, array &$values, $byReference = false)
{
    $function = \Closure::bind(function ($object, array &$values, $byReference) {
        if ($byReference) {
            $object->values = &$values;
        } else {
            $object->values = $values;
        }

        $object->changedValues = [];

        if (property_exists($object, 'objects')) {
            $object->objects = [];
        }
    }, null, $object);

    $function($object, $values, $byReference);

    return $object;
}

function get_values($object)
{
    $function = \Closure::bind(function ($object) {
        return $object->values;
    }, null, $object);

    return $function($object);
}

function get_changed_values($object)
{
    $function = \Closure::bind(function ($object) {
        $changedValues = $object->changedValues;

        // hack I know
        if (property_exists($object, 'objects')) {
            foreach ($object->objects as $namespace => $namespaceValues) {
                foreach ($namespaceValues as $name => $values) {
                    if (is_array($values)) {
                        foreach ($values as $valueKey => $value) {
                            if (false == empty(get_changed_values($value))) {
                                $changedValues[$namespace][$name][$valueKey] = get_changed_values($value);
                            }
                        }
                    } elseif (is_object($values)) {
                        if (false == empty(get_changed_values($values))) {
                            $changedValues[$namespace][$name] = get_changed_values($values);
                        }
                    }
                }
            }
        }

        return $changedValues;
    }, null, $object);

    return $function($object);
}

function build_object($classOrClosure, array &$values, \Closure $objectBuilder = null)
{
    if ($classOrClosure instanceof \Closure) {
        $class = $classOrClosure($values);
    } else {
        $class = (string) $classOrClosure;
    }

    $object = new $class();
    set_values($object, $values, true);

    $objectBuilder && $objectBuilder($object);

    return $object;
}

function clone_object($object)
{
    $values = get_values($object);

    return build_object(get_class($object), $values);
}