<?php

declare(strict_types=1);

namespace ILIAS\AdvancedMetaData\Types\Group\ComplexOptions\Table;

use ILIAS\UI\Component\Table\DataRetrieval as BaseDataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\UI\Component\Table\OrderingRetrieval;
use ILIAS\UI\Component\Table\OrderingRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use Generator;
use ilAdvancedMDFieldDefinition;
use ilAdvancedMDFieldTranslations;
use ilAdvancedMDPermissionHelper;
use ilLanguage;

class FieldDataRetrieval extends AbstractDataRetrieval
{
    public function __construct(
        protected int $record_id,
        protected string $active_language,
        protected ilAdvancedMDPermissionHelper $permissions,
        protected ilLanguage $lng,
        protected \ILIAS\UI\Factory $ui_factory,
        protected \ILIAS\Data\Factory $data_factory,
        protected \ILIAS\Refinery\Factory $refinery
    ) {
    }

    public function getRawData(): array
    {
        $fields = ilAdvancedMDFieldDefinition::getInstancesByRecordId(
            $this->record_id,
            false,
            $this->active_language
        );

        $res = [];
        $counter = 0;
        $namesource = $this->getNameSource();
        $ok_icon = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_ok.svg', '', 'small');
        $not_ok_icon = $this->ui_factory->symbol()->icon()->custom('assets/images/standard/icon_not_ok.svg', '', 'small');

        foreach ($fields as $definition) {
            $field_translations = ilAdvancedMDFieldTranslations::getInstanceByRecordId($definition->getRecordId());
            $id = $definition->getFieldId();
            $counter += 10;

            $properties = $definition->getFieldDefinitionForTableGUI($this->active_language);
            $configuration = "";
            if (is_array($properties) && count($properties) > 0) {
                $cleaned_properties = [];
                foreach ($properties as $key => $value) {
                    $cleaned_properties[(string) $key] = (string) $value;
                }
                $configuration = $this->ui_factory->listing()->descriptive($cleaned_properties);
            }

            $searchable_icon = $definition->isSearchable() ? $ok_icon : $not_ok_icon;

            $res[$id] = [
                'id' => $id,
                'position' => $counter,
                'title' => $field_translations->getTitleForLanguage($id, $this->active_language),
                'description' => $field_translations->getDescriptionForLanguage($id, $this->active_language),
                'type' => $this->lng->txt($definition->getTypeTitle()),
                'configuration' => $configuration,
                'searchable' => $searchable_icon,
                'perm' => $this->permissions->hasPermissions(
                    ilAdvancedMDPermissionHelper::CONTEXT_FIELD,
                    (int) $id,
                    [
                        ilAdvancedMDPermissionHelper::ACTION_FIELD_EDIT,
                        [
                            ilAdvancedMDPermissionHelper::ACTION_FIELD_EDIT_PROPERTY,
                            ilAdvancedMDPermissionHelper::SUBACTION_FIELD_SEARCHABLE
                        ]
                    ]
                )
            ];
        }

        return $res;
    }

    public function getAllIDs(): array
    {
        return array_keys($this->getRawData());
    }
}
