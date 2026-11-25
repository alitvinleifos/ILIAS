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
use ILIAS\UI\Component\Link\Standard as StandardLink;
use ILIAS\UI\Factory as UIFactory;
use ilTree;

class Builder implements BuilderInterface
{
    protected bool $start_included = true;
    protected bool $end_included = true;

    protected array $lead_crumbs = [];
    protected array $trail_crumbs = [];

    public function __construct(
        protected int $start_ref_id,
        protected int $end_ref_id,
        protected ilTree $tree,
        protected UIFactory $ui_factory,
        protected ObjectPropertiesWrapperInterface $wrapper,
        protected AccessWrapperInterface $access_wrapper
    ) {
    }
    public function addLeadingLink(StandardLink $link): BuilderInterface
    {
        $clone = clone $this;
        $clone->lead_crumbs[] = $link;
        return $clone;
    }

    public function addTrailingLink(StandardLink $link): BuilderInterface
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

        return $this->ui_factory->breadcrumbs(
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
            $permissions = $this->access_wrapper->getPermissionResult($ref_id);

            if (!$permissions['is_visible']) {
                $needs_gap_indicator = true;
                continue;
            }

            if ($needs_gap_indicator) {
                yield $this->ui_factory->link()->standard('...', '')->withDisabled(true);
                $needs_gap_indicator = false;
            }

            $obj_id = $this->wrapper->lookupObjId($ref_id);
            $type = $this->wrapper->lookupType($obj_id);
            $title = $this->wrapper->buildTitle($ref_id, $obj_id, $type);

            $link = $this->ui_factory->link()->standard(
                $title,
                $permissions['is_clickable'] ? $this->wrapper->buildLink($ref_id, $type) : ''
            );

            if (!$permissions['is_clickable']) {
                yield $link->withDisabled(true);
                continue;
            }

            yield $link;
        }

        if ($needs_gap_indicator) {
            yield $this->ui_factory->link()->standard('...', '')->withDisabled(true);
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

        $path = $this->tree->getPathId($this->end_ref_id, $this->start_ref_id);

        if (!$this->isStartIncluded() && count($path) > 0) {
            array_shift($path);
        }

        if (!$this->isEndIncluded() && count($path) > 0) {
            array_pop($path);
        }

        return array_values($path);
    }
}
