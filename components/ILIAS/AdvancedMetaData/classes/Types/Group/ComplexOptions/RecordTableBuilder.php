<?php

declare(strict_types=1);

namespace ILIAS\AdvancedMetaData\Types\Group\ComplexOptions\Table;

use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Table\Ordering as OrderingTable;
use ilLanguage;
use ILIAS\HTTP\Services as HTTP;
use ILIAS\UI\URLBuilder;
use ILIAS\UI\URLBuilderToken;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\URI;

class RecordTableBuilder
{
    public const string TITLE_COLUMN = 'title';
    public const string FIELDS_COLUMN = 'fields';
    public const string SCOPE_COLUMN = 'scope';
    public const string ASSIGNED_OBJECTS_COLUMN = 'assigned_objects';
    public const string ACTIVE_COLUMN = 'active';

    public const string EDIT_RECORD_ACTION = 'editRecord';
    public const string EDIT_FIELDS_ACTION = 'editFields';
    public const string DELETE_ACTION = 'confirmDeleteRecords';
    public const string EXPORT_ACTION = 'exportRecords';
    public const string SAVE_ACTION = 'updateRecords';

    public function __construct(
        protected RecordDataRetrieval $data_retrieval,
        protected ilLanguage $lng,
        protected UIFactory $ui_factory,
        protected HTTP $http,
        protected DataFactory $data_factory
    ) {
    }

    public function get(
        URLBuilder $url_builder,
        URLBuilderToken $id_token,
        URLBuilderToken $action_token,
        bool $has_write_access
    ): OrderingTable {
        $columns = [];
        $columns[self::TITLE_COLUMN] = $this->ui_factory->table()->column()->text(
            $this->lng->txt('title')
        )->withIsSortable(true);
        $columns[self::FIELDS_COLUMN] = $this->ui_factory->table()->column()->text(
            $this->lng->txt('md_fields')
        );
        $columns[self::SCOPE_COLUMN] = $this->ui_factory->table()->column()->text(
            $this->lng->txt('md_adv_scope_list_header')
        );
        $columns[self::ASSIGNED_OBJECTS_COLUMN] = new PresentationColumn(
            $this->lng,
            $this->lng->txt('md_obj_types')
        );
        $columns[self::ACTIVE_COLUMN] = new PresentationColumn(
            $this->lng,
            $this->lng->txt('md_adv_active')
        );

        $actions = [];
        $actions[self::EDIT_RECORD_ACTION] = $this->ui_factory->table()->action()->single(
            $this->lng->txt('edit'),
            $url_builder->withParameter($action_token, self::EDIT_RECORD_ACTION),
            $id_token
        );
        $actions[self::EDIT_FIELDS_ACTION] = $this->ui_factory->table()->action()->single(
            $this->lng->txt('md_adv_field_table'),
            $url_builder->withParameter($action_token, self::EDIT_FIELDS_ACTION),
            $id_token
        );

        $actions[self::EXPORT_ACTION] = $this->ui_factory->table()->action()->multi(
            $this->lng->txt('export'),
            $url_builder->withParameter($action_token, self::EXPORT_ACTION),
            $id_token
        );
        if ($has_write_access) {
            $actions[self::DELETE_ACTION] = $this->ui_factory->table()->action()->multi(
                $this->lng->txt('delete'),
                $url_builder->withParameter($action_token, self::DELETE_ACTION),
                $id_token
            );
        }

        $target = $url_builder->withParameter($action_token, self::SAVE_ACTION)->buildURI();
        return $this->ui_factory->table()->ordering(
            $this->data_retrieval,
            $target,
            $this->lng->txt('md_record_list_table'),
            $columns
        )->withActions($actions)
            ->withRequest($this->http->request());
    }
}
