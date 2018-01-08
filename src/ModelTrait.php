<?php

namespace HnhDigital\ModelSearch;

trait ModelTrait
{
    /**
     * Apply a search.
     *
     * @return self
     */
    public function scopeSearch(&$query, $request = [])
    {
        return (new ModelSearch())->run($query, $this, $request);
    }

    /**
     * Get the search attributes.
     *
     * @return array
     */
    public function getSearchableAttributes()
    {
        return (new ModelSearch())->getAttributes($this);
    }

    /**
     * Get the search name for this model.
     *
     * @return array
     */
    public function getSearchName()
    {
        if (empty($this->search_name)) {
            return $this->getTable();
        }

        return $this->search_name;
    }

    /**
     * Get the custom attributes.
     *
     * @return array
     */
    public function getSearchAttributes()
    {
        if (empty($this->search_attributes)) {
            return [];
        }

        return $this->search_attributes;
    }

    /**
     * Get the specified (and limited) relationshiips.
     *
     * @return array
     */
    public function getSearchRelationships()
    {
        if (empty($this->search_relationships)) {
            return [];
        }

        return $this->search_relationships;
    }
}
