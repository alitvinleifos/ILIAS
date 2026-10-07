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
use ilAdvancedMDRecord;
use ilAdvancedMDRecordObjectOrderings;
use ilAdvancedMDPermissionHelper;
use ilAdvancedMDFieldDefinition;
use ilLanguage;
use ilAdvancedMDSettingsGUI;
use ilLegacyFormElementsUtil;
use ilUtil;

class RecordDataRetrieval extends AbstractDataRetrieval
{
    public function __construct(
        protected int $context,
        protected ?int $obj_id,
        protected int $ref_id,
        protected $sub_type,
        protected ?string $obj_type,
        protected ilAdvancedMDPermissionHelper $permissions,
        protected ilLanguage $lng,
        protected \ILIAS\UI\Factory $ui_factory,
        protected \ILIAS\Data\Factory $data_factory,
        protected \ILIAS\Refinery\Factory $refinery
    ) {
    }

    public function getRawData(): array
    {
        $res = [];
        $records = ilAdvancedMDRecord::_getRecords();
        $orderings = new ilAdvancedMDRecordObjectOrderings();
        $records = $orderings->sortRecords($records, (int) $this->obj_id);

        $position = 0;
        $namesource = $this->getNameSource();

        foreach ($records as $record) {
            $parent_id = $record->getParentObject();

            if ($this->context == ilAdvancedMDSettingsGUI::CONTEXT_ADMINISTRATION) {
                if ($parent_id) {
                    continue;
                }
            } else {
                if ($parent_id && $parent_id != $this->obj_id) {
                    continue;
                }

                if (!$parent_id && !$record->isActive()) {
                    continue;
                }

                if (ilAdvancedMDRecord::isFilteredByScope($this->ref_id, $record->getScopes())) {
                    continue;
                }
            }

            $position += 10;
            $id = $record->getRecordId();

            $fields = [];
            $defs = ilAdvancedMDFieldDefinition::getInstancesByRecordId($id);
            if (!count($defs)) {
                $fields_str = $this->permissions->hasPermissions(
                    ilAdvancedMDPermissionHelper::CONTEXT_RECORD,
                    $id,
                    [ilAdvancedMDPermissionHelper::ACTION_RECORD_EDIT_FIELDS]
                ) ? $this->lng->txt('md_adv_no_fields') : "";
            } else {
                foreach ($defs as $definition_obj) {
                    $fields[] = $definition_obj->getTitle() . ": " . $this->lng->txt($definition_obj->getTypeTitle());
                }
                $fields_str = implode(', ', $fields);
            }

            $scope_str = "";
            if (!$parent_id && count($record->getScopeRefIds())) {
                $scopes = [];
                foreach ($record->getScopeRefIds() as $ref_id) {
                    if (\ilObject::_exists($ref_id, true)) {
                        $scopes[] = \ilObject::_lookupTitle(\ilObject::_lookupObjId($ref_id));
                    }
                }
                $scope_str = implode(', ', $scopes);
            } else {
                $scope_str = $parent_id ? $this->lng->txt('meta_local') : $this->lng->txt('meta_global');
            }

            $assigned_objects = [];
            $assignable_types = ilAdvancedMDRecord::_getAssignableObjectTypes(true);
            $record_assigned_types = $record->getAssignedObjectTypes();

            $perm = $this->permissions->hasPermissions(
                ilAdvancedMDPermissionHelper::CONTEXT_RECORD,
                (int) $id,
                [
                    ilAdvancedMDPermissionHelper::ACTION_RECORD_EDIT,
                    ilAdvancedMDPermissionHelper::ACTION_RECORD_EDIT_FIELDS,
                    ilAdvancedMDPermissionHelper::ACTION_RECORD_TOGGLE_ACTIVATION,
                    [
                        ilAdvancedMDPermissionHelper::ACTION_RECORD_EDIT_PROPERTY,
                        ilAdvancedMDPermissionHelper::SUBACTION_RECORD_OBJECT_TYPES
                    ]
                ]
            );

            $disabled = !$perm[ilAdvancedMDPermissionHelper::ACTION_RECORD_EDIT_PROPERTY][ilAdvancedMDPermissionHelper::SUBACTION_RECORD_OBJECT_TYPES];

            if ($this->context == ilAdvancedMDSettingsGUI::CONTEXT_OBJECT) {
                $options = array(
                    0 => $this->lng->txt("meta_obj_type_inactive"),
                    1 => $this->lng->txt("meta_obj_type_active")
                );
                $in_object_type_context = (string) $this->obj_type;
            } else {
                $options = array(
                    0 => $this->lng->txt("meta_obj_type_inactive"),
                    1 => $this->lng->txt("meta_obj_type_mandatory"),
                    2 => $this->lng->txt("meta_obj_type_optional")
                );
                $in_object_type_context = "";
            }

            $assigned_html = [];
            foreach ($assignable_types as $assignable) {
                $value = 0;
                $assigned = false;
                foreach ($record_assigned_types as $item) {
                    if ($assignable['obj_type'] === $item['obj_type'] &&
                        $assignable['sub_type'] === $item['sub_type']) {
                        $assigned = true;
                        $value = $item['optional'] ? 2 : 1;
                        break;
                    }
                }

                if ($in_object_type_context !== "" && $in_object_type_context !== $assignable["obj_type"]) {
                    continue;
                }

                if ($in_object_type_context !== "" && !$parent_id && !$assigned) {
                    continue;
                }

                $type_options = $options;
                switch ($assignable["obj_type"]) {
                    case "talt":
                        unset($type_options[1]);
                        break;
                    case "rcrs":
                        unset($type_options[2]);
                        break;
                }

                $select = ilLegacyFormElementsUtil::formSelect(
                    $value,
                    "obj_types[" . $id . "][" . $assignable["obj_type"] . ":" . $assignable["sub_type"] . "]",
                    $type_options,
                    false,
                    true,
                    0,
                    "",
                    array("style" => "min-width:125px"),
                    $disabled
                );

                $assigned_html[] = '<div class="' . (($assignable["obj_type"] == "prtf" && $assignable["sub_type"] == "pfpg") || ($assignable["obj_type"] == "tals" && $assignable["sub_type"] == "etal") ? 'hidden' : 'std') . '">' .
                    $assignable['text'] . ': ' . $select . '</div>';
            }

            $active_input = $this->ui_factory->input()->field()->checkbox(
                '',
                ''
            )
                ->withDedicatedName('active[' . $id . ']')
                ->withNameFrom($namesource)
                ->withValue($record->isActive());

            $res[$id] = [
                'id' => $id,
                'position' => $position,
                'title' => $record->getTitle(),
                'description' => $record->getDescription(),
                'fields' => $fields_str,
                'active' => $active_input,
                'scope' => $scope_str,
                'assigned_objects' => implode('', $assigned_html),
                'local' => (bool) $parent_id,
                'readonly' => (bool) (!$parent_id && $this->context == ilAdvancedMDSettingsGUI::CONTEXT_OBJECT),
                'optional' => (bool) ($record->isActive() && $this->context == ilAdvancedMDSettingsGUI::CONTEXT_OBJECT),
                'perm' => $perm
            ];
        }

        return $res;
    }

    public function getAllIDs(): array
    {
        return array_keys($this->getRawData());
    }
}
