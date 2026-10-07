<?php

declare(strict_types=1);

namespace ILIAS\AdvancedMetaData\Types\Group\ComplexOptions\Table;

use ILIAS\UI\Implementation\Component\Table\Column\Column;
use ILIAS\UI\Component\Component;

class PresentationColumn extends Column
{
    public function format($value): string|Component
    {
        return $value;
    }

    public function getOrderingLabels(): array
    {
        return [
            $this->asc_label ?? $this->getTitle() . self::SEPERATOR . $this->lng->txt('order_option_alphabetical_ascending'),
            $this->desc_label ?? $this->getTitle() . self::SEPERATOR . $this->lng->txt('order_option_alphabetical_descending')
        ];
    }
}
