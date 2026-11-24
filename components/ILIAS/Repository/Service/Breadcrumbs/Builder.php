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

use ILIAS\UI\Component\Breadcrumbs\Breadcrumbs;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\StaticURL\StaticURLServices;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Repository\InternalDomainService;
use ILIAS\Repository\InternalGUIService;
use ilTree;
use ilLanguage;
use ilAccessHandler;
use ilObjectDefinition;
use ilObject;
use ilCtrlInterface;
use ilAdministrationGUI;
use ILIAS\UI\Component\Link\Standard as StandardLink;

class Builder implements BuilderInterface
{
    protected bool $start_included = true;
    protected bool $end_included = true;

    protected array $lead_crumbs = [];
    protected array $trail_crumbs = [];

    public function __construct(
        protected int $start_ref_id,
        protected int $end_ref_id,
        protected InternalDomainService $domain_service,
        protected InternalGUIService $gui_service,
        protected DataFactory $data_factory
    ) {
    }
    public function withLeadCrumb(StandardLink $link): BuilderInterface
    {
        $clone = clone $this;
        $clone->lead_crumbs[] = $link;
        return $clone;
    }

    public function withTrailCrumb(StandardLink $link): BuilderInterface
    {
        $clone = clone $this;
        $clone->trail_crumbs[] = $link;
        return $clone;
    }

    public function withStartIncluded(bool $included): BuilderInterface
    {
        $clone = clone $this;
        $clone->start_included = $included;
        return $clone;
    }

    public function isStartIncluded(): bool
    {
        return $this->start_included;
    }

    public function withEndIncluded(bool $included): BuilderInterface
    {
        $clone = clone $this;
        $clone->end_included = $included;
        return $clone;
    }

    public function isEndIncluded(): bool
    {
        return $this->end_included;
    }

    public function get(): Breadcrumbs
    {
        $crumbs = $this->lead_crumbs;
        foreach ($this->yieldPathElements() as $link) {
            $crumbs[] = $link;
        }

        return $this->gui_service->ui()->factory()->breadcrumbs(
            array_merge($crumbs, $this->trail_crumbs)
        );
    }

    public function getAsString(): string
    {
        $titles = [];
        foreach ($this->yieldPathElements() as $link) {
            $titles[] = $link->getLabel();
        }

        return implode(' > ', $titles);
    }

    /**
     * @return \Generator<int, StandardLink>
     */
    protected function yieldPathElements(): \Generator
    {
        $path = $this->getPathIds();
        if (count($path) === 0) {
            return;
        }

        $needs_gap_indicator = false;
        foreach ($path as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);

            $has_visible_permission = $this->domain_service->access()->checkAccess('visible', '', $ref_id);
            $has_read_permission = $this->domain_service->access()->checkAccess('read', '', $ref_id);

            if (!$has_visible_permission) {
                $needs_gap_indicator = true;
                continue;
            }

            if ($needs_gap_indicator) {
                yield $this->gui_service->ui()->factory()->link()->standard('...', '')->withDisabled(true);
                $needs_gap_indicator = false;
            }

            $title = $this->buildTitle($ref_id, $obj_id, $type);
            $action = $has_read_permission ? $this->buildLink($ref_id, $type) : '';

            $link = $this->gui_service->ui()->factory()->link()->standard($title, $action);
            if ($action === '') {
                $link = $link->withDisabled(true);
            }

            yield $link;
        }

        if ($needs_gap_indicator) {
            yield $this->gui_service->ui()->factory()->link()->standard('...', '')->withDisabled(true);
        }
    }

    /**
     * @return int[]
     */
    protected function getPathIds(): array
    {
        if ($this->end_ref_id <= 0) {
            return [];
        }

        $path = $this->domain_service->repositoryTree()->getPathId($this->end_ref_id, $this->start_ref_id);

        if (!$this->isStartIncluded() && count($path) > 0) {
            array_shift($path);
        }

        if (!$this->isEndIncluded() && count($path) > 0) {
            array_pop($path);
        }

        return array_values($path);
    }

    protected function buildTitle(int $ref_id, int $obj_id, string $type): string
    {
        if ($ref_id === (int) ROOT_FOLDER_ID) {
            return $this->domain_service->lng()->txt('repository');
        }

        if ($this->domain_service->objectDefinition()->isAdministrationObject($type)) {
            return $this->domain_service->lng()->txt('obj_' . $type);
        }

        return ilObject::_lookupTitle($obj_id);
    }

    protected function buildLink(int $ref_id, string $type): string
    {
        if ($this->domain_service->objectDefinition()->isAdministrationObject($type)) {
            $this->gui_service->ctrl()->setParameterByClass(ilAdministrationGUI::class, 'ref_id', $ref_id);
            $link = $this->gui_service->ctrl()->getLinkTargetByClass(ilAdministrationGUI::class, 'jump');
            $this->gui_service->ctrl()->clearParametersByClass(ilAdministrationGUI::class);
            return $link;
        }

        return (string) $this->domain_service->staticUrl()->builder()->build($type, $this->data_factory->refId($ref_id));
    }
}
