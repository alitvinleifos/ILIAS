<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\StaticURL\Services as StaticUrl;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Repository\InternalDomainService;
use ILIAS\Repository\InternalGUIService;
use ilTree;
use ilLanguage;
use ilAccessHandler;
use ilObjectDefinition;
use ilCtrlInterface;

class Factory implements FactoryInterface
{
    protected ObjectPropertiesWrapperInterface $presentation;
    protected AccessWrapperInterface $access_wrapper;

    public function __construct(
        protected ilTree $tree,
        protected ilAccessHandler $access,
        protected ilLanguage $lng,
        protected ilObjectDefinition $obj_definition,
        protected ilCtrlInterface $ctrl,
        protected StaticUrl $static_url,
        protected UIFactory $ui_factory,
        protected DataFactory $data_factory
    ) {
    }

    public function objectPropertiesWrapper(): ObjectPropertiesWrapperInterface
    {
        return $this->presentation ??= new ObjectPropertiesWrapper(
            $this->lng,
            $this->obj_definition,
            $this->ctrl,
            $this->static_url,
            $this->data_factory
        );
    }

    public function accessWrapper(): AccessWrapperInterface
    {
        return $this->access_wrapper ??= new AccessWrapper($this->access);
    }

    public function buildBreadcrumbs(int $start_ref_id, int $end_ref_id): BuilderInterface
    {
        return new Builder(
            $start_ref_id,
            $end_ref_id,
            $this->tree,
            $this->ui_factory,
            $this->objectPropertiesWrapper(),
            $this->accessWrapper()
        );
    }
}
