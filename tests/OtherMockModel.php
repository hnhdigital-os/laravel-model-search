<?php

namespace HnhDigital\ModelSearch\Tests;

use HnhDigital\ModelSearch\ModelTrait;
use Illuminate\Database\Eloquent\Model;

class OtherMockModel extends Model
{
    use ModelTrait;

    protected $table = 'other_mock_model';

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
     * Search attributes.
     *
     * @var array
     */
    protected $search_attributes = [
        'lookup' => [
            'title'      => 'Name',
            'attributes' => ['other_mock_model.name', 'other_mock_model.title'],
            'filter'     => 'string',
        ],
        'phone' => [
            'attributes' => 'other_mock_model.phone',
            'filter'     => 'string',
            'enable'     => [
                'wild-all' => true,
            ],
        ],
    ];

    /**
     * Search attributes.
     *
     * @var array
     */
    protected $search_relationships = [
        MockModel::class,
    ];

    /**
     * Has one mock model.
     *
     * @return Builder
     */
    public function mockModel()
    {
        return $this->hasOne(MockModel::class);
    }
}
