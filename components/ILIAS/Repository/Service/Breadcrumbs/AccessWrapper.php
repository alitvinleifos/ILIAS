<?php

declare(strict_types=1);

namespace ILIAS\Repository\Breadcrumbs;

use ilAccessHandler;

class AccessWrapper implements AccessWrapperInterface
{
    public function __construct(
        protected ilAccessHandler $access
    ) {
    }

    /**
     * @return array{is_visible: bool, is_clickable: bool}
     */
    public function getPermissionResult(int $ref_id): array
    {
        $visible = (bool) $this->access->checkAccess('visible', '', $ref_id);
        $read = (bool) $this->access->checkAccess('read', '', $ref_id);

        return [
            'is_visible' => $visible,
            'is_clickable' => $read
        ];
    }
}
