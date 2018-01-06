<?php

namespace HnhDigital\ModelSearch;

trait ModelTrait
{
    /**
     * Search name.
     *
     * @var array
     */
    protected $search_name = '';

    /**
     * Specified custom search attributes.
     *
     * @var array
     */
    protected $custom_search_attributes = [

    ];

    /**
     * Specified custom search relationships.
     *
     * @var array
     */
    protected $custom_search_relationships = [

    ];

    /**
     * Apply a search.
     *
     * @return self
     */
    public function scopeSearch($query, $request)
    {
        new ModelSearch($query, $this, $request);
    }

    /**
     * Get the custom attributes.
     *
     * @return array
     */
    public function getCustomSearchAttributesAttribute($value)
    {
        return $value;
    }

    /**
     * Get the specified (and limited) relationshiips.
     *
     * @return array
     */
    public function getCustomSearchRelationshipsAttribute($value)
    {
        return $value;
    }

    /**
     * Get the search name for this model.
     *
     * @return array
     */
    public function getSearchNameAttribute($value)
    {
        if (empty($value)) {
            return $this->getTable();
        }

        return $value;
    }
}
