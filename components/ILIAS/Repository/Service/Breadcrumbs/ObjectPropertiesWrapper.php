<?php

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\StaticURL\Services as StaticUrl;
use ILIAS\Data\Factory as DataFactory;
use ilLanguage;
use ilObjectDefinition;
use ilCtrlInterface;
use ilObject;
use ilAdministrationGUI;

class ObjectPropertiesWrapper implements ObjectPropertiesWrapperInterface
{
    public function __construct(
        protected ilLanguage $lng,
        protected ilObjectDefinition $obj_definition,
        protected ilCtrlInterface $ctrl,
        protected StaticUrl $static_url,
        protected DataFactory $data_factory
    ) {
    }

    public function lookupObjId(int $ref_id): int
    {
        return (int) ilObject::_lookupObjId($ref_id);
    }

    public function lookupType(int $obj_id): string
    {
        return (string) ilObject::_lookupType($obj_id);
    }

    public function lookupTitle(int $obj_id): string
    {
        return (string) ilObject::_lookupTitle($obj_id);
    }

    public function buildTitle(int $ref_id, int $obj_id, string $type): string
    {
        if ($ref_id === (int) ROOT_FOLDER_ID) {
            return $this->lng->txt('repository');
        }

        if ($this->obj_definition->isAdministrationObject($type)) {
            return $this->lng->txt('obj_' . $type);
        }

        return $this->lookupTitle($obj_id);
    }

    public function buildLink(int $ref_id, string $type): string
    {
        if ($this->obj_definition->isAdministrationObject($type)) {
            $this->ctrl->setParameterByClass(ilAdministrationGUI::class, 'ref_id', $ref_id);
            $link = $this->ctrl->getLinkTargetByClass(ilAdministrationGUI::class, 'jump');
            $this->ctrl->clearParametersByClass(ilAdministrationGUI::class);
            return $link;
        }

        return (string) $this->static_url->builder()->build($type, $this->data_factory->refId($ref_id));
    }
}
