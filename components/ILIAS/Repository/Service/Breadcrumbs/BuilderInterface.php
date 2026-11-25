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

/**
 * Interface BuilderInterface
 * @package ILIAS\Repository\Breadcrumbs
 */
interface BuilderInterface
{
    public function addLeadingLink(StandardLink $link): self;

    public function addTrailingLink(StandardLink $link): self;

    public function withStartIncluded(bool $included): self;

    public function isStartIncluded(): bool;

    public function withEndIncluded(bool $included): self;

    public function isEndIncluded(): bool;

    public function get(): Breadcrumbs;

    public function getAsString(): string;
}
