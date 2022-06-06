<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace APY\DataGridBundle\Grid\Mapping\Driver;

use APY\DataGridBundle\Grid\Mapping\Column;
use APY\DataGridBundle\Grid\Mapping\Source;
use Doctrine\Common\Annotations\Reader;

class Annotation implements DriverInterface
{
    protected array $columns;
    protected array $filterable;
    protected array $sortable;
    protected array $fields;
    protected array $loaded;
    protected array $groupBy;

    protected Reader $reader;

    public function __construct($reader)
    {
        $this->reader = $reader;
        $this->columns = $this->fields = $this->loaded = $this->groupBy = $this->filterable = $this->sortable = [];
    }

    public function supports(string $class): bool
    {
        $reflection = new \ReflectionClass($class);
        $result = false;
        foreach ($this->reader->getClassAnnotations($reflection) as $annotation) {
            if ($annotation instanceof Source) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function getClassColumns($class, $group = 'default'): array
    {
        $this->loadMetadataFromReader($class, $group);

        return $this->columns[$class][$group];
    }

    public function getFieldsMetadata($class, $group = 'default'): array
    {
        $this->loadMetadataFromReader($class, $group);

        return $this->fields[$class][$group];
    }

    public function getGroupBy($class, $group = 'default'): array
    {
        return $this->groupBy[$class][$group] ?? [];
    }

    protected function loadMetadataFromReader($className, $group = 'default'): void
    {
        if (isset($this->loaded[$className][$group])) {
            return;
        }

        $reflectionCollection = [];

        $reflectionCollection[] = $reflection = new \ReflectionClass($className);
        while (false !== $reflection = $reflection->getParentClass()) {
            $reflectionCollection[] = $reflection;
        }

        while (!empty($reflectionCollection)) {
            $reflection = array_pop($reflectionCollection);

            foreach ($this->reader->getClassAnnotations($reflection) as $class) {
                $this->getMetadataFromClass($className, $class, $group);
            }

            foreach ($reflection->getProperties() as $property) {
                $this->fields[$className][$group][$property->getName()] = [];

                foreach ($this->reader->getPropertyAnnotations($property) as $class) {
                    $this->getMetadataFromClassProperty($className, $class, $property->getName(), $group);
                }
            }
        }

        if (empty($this->columns[$className][$group])) {
            $this->columns[$className][$group] = \array_keys($this->fields[$className][$group]);
        } else {
            foreach ($this->columns[$className][$group] as $columnId) {
                // Ignore mapped fields
                if (!\str_contains($columnId, '.')) {
                    if (!isset($this->fields[$className][$group][$columnId]['filterable'])) {
                        $this->fields[$className][$group][$columnId]['filterable'] = $this->filterable[$className][$group];
                    }
                    if (!isset($this->fields[$className][$group][$columnId]['sortable'])) {
                        $this->fields[$className][$group][$columnId]['sortable'] = $this->sortable[$className][$group];
                    }
                }
            }
        }

        $this->loaded[$className][$group] = true;
    }

    protected function getMetadataFromClassProperty($className, $class, $name = null, $group = 'default'): void
    {
        if ($class instanceof Column) {
            $metadata = $class->getMetadata();

            if (isset($metadata['id']) && $name !== null) {
                throw new \RuntimeException(\sprintf('Parameter `id` can\'t be used in annotations for property `%s`, please remove it from class %s', $name, $className));
            }

            if ($name === null) { // Class Column annotation
                if (isset($metadata['id'])) {
                    $metadata['source'] = false;
                    $this->fields[$className][$group][$metadata['id']] = [];
                } else {
                    throw new \RuntimeException(\sprintf('Missing parameter `id` in annotations for extra column of class %s', $className));
                }
            } else { // Property Column annotation
                // Relationship handle
                if (isset($metadata['field']) && (\str_contains($metadata['field'], '.') || \str_contains($metadata['field'], ':'))) {
                    $metadata['id'] = $metadata['field'];

                    // Title is not set by default like properties of the entity (see getFieldsMetadata method of a source)
                    if (!isset($metadata['title'])) {
                        $metadata['title'] = $metadata['field'];
                    }
                } else {
                    $metadata['id'] = $name;
                }
            }

            // Check the group of the annotation and don't override if an annotation with the group have already been defined
            if (isset($metadata['groups']) && !\in_array($group, (array) $metadata['groups'])
                || isset($this->fields[$className][$group][$metadata['id']]['groups'])) {
                return;
            }

            if (!isset($metadata['filterable'])) {
                $metadata['filterable'] = $this->filterable[$className][$group] ?? true;
            }

            if (!isset($metadata['sortable'])) {
                $metadata['sortable'] = $this->sortable[$className][$group] ?? true;
            }

            if (!isset($metadata['title'])) {
                $metadata['title'] = $metadata['id'];
            }

            if (isset($metadata['field'])) {
                $metadata['source'] = true;
            }

            $this->fields[$className][$group][$metadata['id']] = $metadata;
        }
    }

    protected function getMetadataFromClass($className, $class, $group): void
    {
        if ($class instanceof Source) {
            foreach ($class->getGroups() as $sourceGroup) {
                $this->columns[$className][$sourceGroup] = $class->getColumns();
                $this->filterable[$className][$sourceGroup] = $class->isFilterable();
                $this->sortable[$className][$sourceGroup] = $class->isSortable();
                $this->groupBy[$className][$sourceGroup] = $class->getGroupBy();
            }
        } elseif ($class instanceof Column) {
            $this->getMetadataFromClassProperty($className, $class, null, $group);
        }
    }
}
