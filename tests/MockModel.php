<?php

namespace HnhDigital\ModelSearch\Tests;

use HnhDigital\ModelSearch\ModelTrait;
use Illuminate\Database\Eloquent\Model;

class MockModel extends Model
{
    use ModelTrait;

    protected $table = 'mock_model';

    /**
     * The attributes that require casting.
     *
     * @var array
     */
    protected $casts = [
        'id'         => 'integer',
        'is_enabled' => 'boolean',
        'title'      => 'string',
        'name'       => 'string',
        'phone'      => 'string',
        'total'      => 'numeric',
    ];

    /**
     * Custom search attributes.
     *
     * @var array
     */
    protected $search_attributes = [
        'lookup' => [
            'title'      => 'Name',
            'attributes' => ['mock_model.name', 'mock_model.title'],
            'filter'     => 'string',
        ],
        'phone' => [
            'attributes' => 'mock_model.phone',
            'filter'     => 'string',
            'enable'     => [
                'wild-all' => true,
            ],
        ],
    ];
}
