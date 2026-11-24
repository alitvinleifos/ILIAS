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
use ILIAS\StaticURL\StaticURLServices;
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
    public function __construct(
        protected InternalDomainService $domain_service,
        protected InternalGUIService $gui_service,
        protected DataFactory $data_factory
    ) {
    }

    public function buildBreadcrumbs(int $start_ref_id, int $end_ref_id): BuilderInterface
    {
        return new Builder(
            $start_ref_id,
            $end_ref_id,
            $this->domain_service,
            $this->gui_service,
            $this->data_factory
        );
    }
}
