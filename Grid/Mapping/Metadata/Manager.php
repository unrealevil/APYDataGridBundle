<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 * (c) Stanislav Turza
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @todo check for column extensions
 */

namespace APY\DataGridBundle\Grid\Mapping\Metadata;

use APY\DataGridBundle\Grid\Mapping\Driver\DriverInterface;

class Manager
{
    /**
     * @var DriverInterface[]
     */
    protected DriverHeap|array $drivers;

    public function __construct()
    {
        $this->drivers = new DriverHeap();
    }

    public function addDriver($driver, $priority): void
    {
        $this->drivers->insert($driver, $priority);
    }

    /**
     * @todo remove this hack
     *
     * @return DriverHeap
     */
    public function getDrivers(): array|DriverHeap
    {
        return clone $this->drivers;
    }

    public function getMetadata($className, $group = 'default'): Metadata
    {
        $metadata = new Metadata();

        $columns = $fieldsMetadata = $groupBy = [];

        foreach ($this->getDrivers() as $driver) {
            if (!$driver->supports($className)) {
                continue;
            }
            $columns = \array_merge($columns, $driver->getClassColumns($className, $group));
            $fieldsMetadata[] = $driver->getFieldsMetadata($className, $group);
            $groupBy = \array_merge($groupBy, $driver->getGroupBy($className, $group));
        }

        $mappings = $cols = [];

        foreach ($columns as $fieldName) {
            $map = [];

            foreach ($fieldsMetadata as $field) {
                if (isset($field[$fieldName]) && (!isset($field[$fieldName]['groups']) || \in_array($group, (array) $field[$fieldName]['groups'], true))) {
                    $map = \array_merge($map, $field[$fieldName]);
                }
            }

            if (!empty($map)) {
                $mappings[$fieldName] = $map;
                $cols[] = $fieldName;
            }
        }

        $metadata->setFields($cols);
        $metadata->setFieldsMappings($mappings);
        $metadata->setGroupBy($groupBy);

        return $metadata;
    }
}
