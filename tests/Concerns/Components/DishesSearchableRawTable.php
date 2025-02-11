<?php

namespace PowerComponents\LivewirePowerGrid\Tests\Concerns\Components;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Tests\Concerns\Models\Dish;
use PowerComponents\LivewirePowerGrid\{Column, Facades\PowerGrid, PowerGridComponent, PowerGridFields};

class DishesSearchableRawTable extends PowerGridComponent
{
    public string $tableName = 'testing-dishes-searchable-raw-table';

    public string $database = '';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput(),

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $searchableRaw = match ($this->database) {
            'sqlite' => 'STRFTIME("%d/%m/%Y", dishes.produced_at) as produced_at_formatted',
            'mysql'  => 'DATE_FORMAT(dishes.produced_at, "%d/%m/%Y") as produced_at_formatted',
            'pgsql'  => 'to_char(dishes.produced_at, \'DD/MM/YYYY\')::text as produced_at_formatted',
            default  => ''
        };

        return Dish::query()
            ->join('categories', function ($categories) {
                $categories->on('dishes.category_id', '=', 'categories.id');
            })
            ->select('dishes.*', 'categories.name as category_name', DB::raw($searchableRaw));
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('produced_at_formatted');
    }

    public function columns(): array
    {
        $searchableRaw = match ($this->database) {
            'sqlite' => 'STRFTIME("%d/%m/%Y", dishes.produced_at) like ?',
            'mysql'  => 'DATE_FORMAT(dishes.produced_at, "%d/%m/%Y") like ?',
            'pgsql'  => 'to_char(dishes.produced_at, \'DD/MM/YYYY\')::text ilike ?',
            default  => ''
        };

        return [
            Column::make('ID', 'id')
                ->searchable()
                ->sortable(),

            Column::make('Prato', 'name')
                ->searchable()
                ->placeholder('Prato placeholder')
                ->sortable(),

            Column::make('Produced At Formatted', 'produced_at_formatted', 'produced_at')
                ->searchableRaw($searchableRaw)
                ->sortable(),

            Column::action('Action'),
        ];
    }

    public function setTestThemeClass(string $themeClass): void
    {
        config(['livewire-powergrid.theme' => $themeClass]);
    }
}
