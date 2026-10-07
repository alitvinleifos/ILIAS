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

class FieldTableBuilder
{
    public const string TITLE_COLUMN = 'title';
    public const string TYPE_COLUMN = 'type';
    public const string OPTIONS_COLUMN = 'options';
    public const string SEARCHABLE_COLUMN = 'searchable';

    public const string EDIT_FIELD_ACTION = 'editField';
    public const string DELETE_ACTION = 'confirmDeleteFields';
    public const string SAVE_ACTION = 'updateFields';

    public function __construct(
        protected FieldDataRetrieval $data_retrieval,
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
        $columns[self::TYPE_COLUMN] = $this->ui_factory->table()->column()->text(
            $this->lng->txt('md_adv_field_fields')
        );
        $columns[self::OPTIONS_COLUMN] = $this->ui_factory->table()->column()->text(
            $this->lng->txt('options')
        );
        $columns[self::SEARCHABLE_COLUMN] = new PresentationColumn(
            $this->lng,
            $this->lng->txt('md_adv_searchable')
        );

        $actions = [];
        $actions[self::EDIT_FIELD_ACTION] = $this->ui_factory->table()->action()->single(
            $this->lng->txt('edit'),
            $url_builder->withParameter($action_token, self::EDIT_FIELD_ACTION),
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
            $this->lng->txt('md_adv_field_table'),
            $columns
        )->withActions($actions)
            ->withRequest($this->http->request());
    }
}
