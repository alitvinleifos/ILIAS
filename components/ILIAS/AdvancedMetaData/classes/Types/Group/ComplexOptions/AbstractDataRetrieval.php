<?php

declare(strict_types=1);

namespace ILIAS\AdvancedMetaData\Types\Group\ComplexOptions\Table;

use ILIAS\UI\Component\Table\DataRetrieval as BaseDataRetrieval;
use ILIAS\UI\Component\Table\OrderingRetrieval;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use Generator;
use ILIAS\UI\Implementation\Component\Input\NameSource;

abstract class AbstractDataRetrieval implements BaseDataRetrieval, OrderingRetrieval
{
    protected function getNameSource(): NameSource
    {
        return new class () implements NameSource {
            public function getNewName(): string
            {
                return '';
            }

            public function getNewDedicatedName(string $dedicated_name): string
            {
                return $dedicated_name;
            }
        };
    }

    public function getRows(
        $row_builder,
        array $visible_column_ids,
        Range $range = null,
        Order $order = null,
        mixed $additional_viewcontrol_data = null,
        mixed $filter_data = null,
        mixed $additional_parameters = null
    ): Generator {
        $data = $this->getRawData();
        uasort($data, fn($a, $b) => $a['position'] <=> $b['position']);

        foreach ($data as $id => $datum) {
            yield $row_builder->buildOrderingRow(
                (string) $id,
                $datum
            )->withPosition((int) ($datum['position'] / 10));
        }
    }

    public function getTotalRowCount(
        mixed $additional_viewcontrol_data,
        mixed $filter_data,
        mixed $additional_parameters
    ): ?int {
        return count($this->getRawData());
    }

    abstract public function getRawData(): array;

    public function getAllIDs(): array
    {
        return array_keys($this->getRawData());
    }
}
