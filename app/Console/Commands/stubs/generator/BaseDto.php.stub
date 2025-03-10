<?php

namespace App\DTO;

use ReflectionClass;
use ReflectionProperty;

/**
 * Base class untuk semua DTO yang mendukung otomatisasi dokumentasi OpenAPI
 */
abstract class BaseDto
{
    /**
     * Convert DTO ke array untuk response API
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $result[$name] = $this->{$name};
        }

        return $result;
    }

    /**
     * Membuat instance DTO dari model atau array
     * 
     * @param mixed $source Model atau array sumber data
     * @return static
     */
    public static function fromSource($source): self
    {
        $dto = new static();
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();

            // Jika sumber adalah array
            if (is_array($source) && array_key_exists($name, $source)) {
                $dto->{$name} = $source[$name];
            }
            // Jika sumber adalah object
            elseif (is_object($source) && property_exists($source, $name)) {
                $dto->{$name} = $source->{$name};
            }
            // Jika sumber adalah model dengan getter
            elseif (is_object($source) && method_exists($source, 'get' . ucfirst($name) . 'Attribute')) {
                $method = 'get' . ucfirst($name) . 'Attribute';
                $dto->{$name} = $source->{$method}();
            }
        }

        return $dto;
    }
}
