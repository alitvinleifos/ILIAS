<?php

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

interface AccessWrapperInterface
{
    /**
     * Get the permission result for a specific reference ID
     * @return array{is_visible: bool, is_clickable: bool}
     */
    public function getPermissionResult(int $ref_id): array;
}
