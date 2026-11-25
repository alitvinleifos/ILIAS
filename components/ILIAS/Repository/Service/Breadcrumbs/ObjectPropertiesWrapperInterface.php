<?php

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

interface ObjectPropertiesWrapperInterface
{
    public function lookupObjId(int $ref_id): int;

    public function lookupType(int $obj_id): string;

    public function lookupTitle(int $obj_id): string;

    public function buildTitle(int $ref_id, int $obj_id, string $type): string;

    public function buildLink(int $ref_id, string $type): string;
}
