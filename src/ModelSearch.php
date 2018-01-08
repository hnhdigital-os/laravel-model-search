<?php

namespace HnhDigital\ModelSearch;

use Illuminate\Database\Query\Expression;
use Schema;

class ModelSearch
{
    /**
     * Filter types.
     *
     * @var array
     */
    protected static $filter_types = [
        'string',
        'number',
        'date',
        'boolean',
        'list',
        'listLookup',
    ];

    /**
     * String operators.
     *
     * @var array
     */
    protected static $string_operators = [
        '*=*'       => ['value' => '*=*', 'name' => 'Contains'],
        '*!=*'      => ['value' => '*!=*', 'name' => 'Not contain'],
        '='         => ['value' => '=', 'name' => 'Equals'],
        '!='        => ['value' => '!=', 'name' => 'Not equal'],
        '=*'        => ['value' => '=*', 'name' => 'Begins with'],
        '!=*'       => ['value' => '!=*', 'name' => 'Does not begin with'],
        '*='        => ['value' => '*=', 'name' => 'Ends with'],
        '!*='       => ['value' => '*!=', 'name' => 'Does not end with'],
        'IN'        => ['value' => 'IN', 'name' => 'In...', 'helper' => 'Separated by semi-colon'],
        'NOT_IN'    => ['value' => 'NOT_IN', 'name' => 'Not in...', 'helper' => 'Separated by semi-colon'],
        'EMPTY'     => ['value' => 'EMPTY', 'name' => 'Empty'],
        'NOT_EMPTY' => ['value' => 'NOT_EMPTY', 'name' => 'Not empty'],
        'NULL'      => ['value' => 'NULL', 'name' => 'NULL'],
        'NOT_NULL'  => ['value' => 'NOT_NULL', 'name' => 'Not NULL'],
    ];

    /**
     * Number operators.
     *
     * @var array
     */
    protected static $number_operators = [
        '='         => ['value' => '=', 'name' => 'Equals'],
        '!='        => ['value' => '!=', 'name' => 'Not equals'],
        '>'         => ['value' => '>', 'name' => 'Greater than'],
        '>='        => ['value' => '>=', 'name' => 'Greater than and equal to'],
        '<='        => ['value' => '<=', 'name' => 'Less than and equal to'],
        '<'         => ['value' => '<', 'name' => 'Less than'],
        'IN'        => ['value' => 'IN', 'name' => 'In...', 'helper' => 'Separated by semi-colon'],
        'NOT_IN'    => ['value' => 'NOT_IN', 'name' => 'Not in...', 'helper' => 'Separated by semi-colon'],
        'EMPTY'     => ['value' => 'EMPTY', 'name' => 'Empty'],
        'NOT_EMPTY' => ['value' => 'NOT_EMPTY', 'name' => 'Not empty'],
        'NULL'      => ['value' => 'NULL', 'name' => 'NULL'],
        'NOT_NULL'  => ['value' => 'NOT_NULL', 'name' => 'Not NULL'],
    ];

    /**
     * Date operators.
     *
     * @var array
     */
    protected static $date_operators = [
        // @todo
    ];

    /**
     * Boolean operators.
     *
     * @var array
     */
    protected static $boolean_operators = [
        '1'  => ['value' => '1', 'name' => 'True'],
        '0'  => ['value' => '0', 'name' => 'False'],
        '='  => ['value' => '=', 'name' => 'Equals'],
        '!=' => ['value' => '=', 'name' => 'Not equals'],
    ];

    /**
     * List operators.
     *
     * @var array
     */
    protected static $list_operators = [
        'IN'     => ['value' => 'IN', 'name' => 'In selected'],
        'NOT_IN' => ['value' => 'NOT_IN', 'name' => 'Not in selected'],
    ];

    /**
     * List operators.
     *
     * @var array
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    protected static $list_lookup_operators = [
        'IN'     => ['value' => 'IN', 'name' => 'In selected'],
        'NOT_IN' => ['value' => 'NOT_IN', 'name' => 'Not in selected'],
    ];

    /**
     * The active query.
     *
     * @var Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var Model
     */
    protected $model;

    /**
     * The request that was made.
     *
     * @var array
     */
    protected $request;

    /**
     * The attributes.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Search requested grouped by model.
     *
     * @var array
     */
    protected $search_models = [];

    /**
     * Start a model search.
     */
    public function __construct(&$query, $model, $request)
    {
        $this->query = $query;
        $this->checkModel($model);
        $this->checkRequest($request);
    }

    /**
     * Process query.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->search_models as $model_name => $filters) {
            // Apply search against the original model.
            if ($model_name === 'self') {
                self::applySearch($this->query, $filters);
                continue;
            }

            // Apply search against the related model.
            $this->query->whereHas($model_name, function ($query) use ($filters) {
                self::applySearch($query, $filters);
            });
        }

        return $this->query;
    }

    /**
     * Check the given model and build attribute list.
     *
     * @return void
     */
    private function checkModel($model)
    {
        $this->model = $model;
        $this->buildRelationships();
        $this->attributes = self::buildAttributes($this->model);
    }

    /**
     * Build a list of all possible attributes.
     *
     * @return void
     */
    public static function buildAttributes($model)
    {
        $result = [];

        // Build attributes off the specified casts.
        foreach ($model->getCasts() as $name => $cast) {
            $result[$name] = [
                'name'       => $name,
                'title'      => $name,
                'attributes' => [sprintf('%s.%s', $model->getTable(), $name)],
                'filter'     => self::convertCast($cast),
                'model'      => &$model,
                'model_name' => 'self',
            ];
        }

        // Apply any custom attributes that have been specified.
        foreach ($model->getSearchAttributes() as $name => $settings) {
            // Specified name or use key.
            $title = array_get($settings, 'title', $name);

            if ($title === $name) {
                $title = title_case($title);
            }

            // Specified attributes, or attribute.
            $attributes = array_get($settings, 'attributes', array_get('settings', 'attribute', []));

            self::validateAttributes($model, $name, $attributes);

            // Allocate.
            $result[$name] = [
                'name'       => $name,
                'title'      => $title,
                'attributes' => $attributes,
                'filter'     => array_get($settings, 'filter', 'string'),
                'enable'     => array_get($settings, 'enable', []),
                'model'      => &$model,
                'model_name' => 'self',
            ];
        }

        return $result;
    }

    /**
     * Convert cast string to what model search uses.
     *
     * @return string
     */
    private static function convertCast($cast)
    {
        switch ($cast) {
            case 'numeric':
            case 'decimal':
            case 'double':
                return 'number';
        }

        return $cast;
    }

    /**
     * Validate the attributes list.
     *
     * @return void
     */
    private static function validateAttributes($model, $name, &$attributes)
    {
        // Should be an array.
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        // Is empty, so we base it off the key.
        if (empty($attributes)) {
            $attributes = [sprintf('%s.%s', $model->getTable(), $name)];
        }

        // Check each of the attribute values.
        // Convert any prepended with a curly to an expression.
        foreach ($attributes as &$value) {
            if (substr($value, 0) === '{') {
                $value = new Expression(substr($value, 1));
            }
        }
    }

    /**
     * Build a list of all possible relationships.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    private function buildRelationships()
    {
        // Specified relationships.
        $search_relationships = $this->model->getSearchRelationships();

        // Auto discover relationships.
    }

    /**
     * Process the provided request.
     *
     * @return void
     */
    private function checkRequest($request)
    {
        if (empty($request)) {
            return;
        }

        $this->request = $request;

        // Models used in this request.
        $models_used = [];

        // Review each request.
        foreach ($this->request as $name => $filters) {
            // This name is not present in available attributes.
            if (!array_has($this->attributes, $name)) {
                continue;
            }

            // Get the settings for the given attribute.
            $settings = array_get($this->attributes, $name);

            // Check and validate each of the filters.
            $filters = self::validateFilters($filters, $settings);

            // Filter did not pass validation.
            if (empty($filters)) {
                continue;
            }

            // Search against current model.
            if (($model_name = array_get($settings, 'model_name')) === 'self') {
                $this->search_models['self'][$name] = $filters;
                continue;
            }

            // Search against an model via relationship.
            $models_used[$model_name] = true;
            $this->query->search_models[$model_name][$name] = $filters;
        }

        // Join the models to this query.
        if (count($models_used)) {
            $this->query->modelJoin($models_used);
        }
    }

    /**
     * Validate the given filter.
     *
     * @return array
     */
    private static function validateFilters($filters, $settings)
    {
        if (!is_array($filters)) {
            $filters = [$filters];
        }

        // Each fitler.
        foreach ($filters as $index => &$filter) {

            // Check this item.
            $filter = self::validateFilterItem($filter, $settings);

            // Remove if invalid.
            if (empty($filter)) {
                unset($filters[$index]);
            }
        }

        return $filters;
    }

    /**
     * Validate each entry.
     *
     * @param array|string $filter
     * @param array        $settings
     *
     * @return array|null
     */
    private static function validateFilterItem($filter, $settings)
    {
        // Convert string to filter array.
        if (!is_array($filter)) {
            $filter = ['', $filter];
        }

        // Convert string to filter array.
        if (array_get($settings, 'filter') !== 'boolean' && count($filter) == 1) {
            array_unshift($filter, '');
        }

        // Split the filter array into operator, value1, value2
        $operator = array_get($filter, 0, '');
        $value_one = array_get($filter, 1, false);
        $value_two = array_get($filter, 2, false);

        // The wild-all setting was enabled.
        // Update value with all characters wildcarded.
        if (array_has($settings, 'enable.wild-all')) {
            self::applyWildAll($operator, $value_one);
        }

        self::checkInlineOperator($operator, $value_one, $settings);
        self::checkNullOperator($operator, $value_one);
        self::checkEmptyOperator($operator, $value_one);

        // Defaullt operator.
        if (empty($operator)) {
            $operator = self::getDefaultOperator(array_get($settings, 'filter'), $operator);
        }

        // Return filter as an associative array.
        $filter = [
            'operator'  => $operator,
            'method'    => 'where',
            'arguments' => [],
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => true,
        ];

        // Update filter based on the filter being used.
        $validation_method = 'filterBy'.studly_case(array_get($settings, 'filter'));
        $filter = self::{$validation_method}($filter);

        // Update based on operator.
        $filter['positive'] = !(stripos($operator, '!') !== false || stripos($operator, 'NOT') !== false);

        return $filter;
    }

    /**
     * Get the default operator.
     *
     * @param string $filter
     * 
     * @return string
     */
    public static function getDefaultOperator($filter, $operator)
    {
        switch ($filter) {
            case 'string':
                return '*=*';
            case 'number':
                return '=';
            case 'boolean':
                return '=';
            case 'list':
                return 'IN';
        }
    }

    /**
     * Applies a wildcard for between every character.
     *
     * @param string &$value
     *
     * @return void
     */
    public static function applyWildAll(&$operator, &$value)
    {
        $positive = !(stripos($operator, '!') !== false || stripos($operator, 'NOT') !== false);
        $operator = $positive ? '*=*' : '*!=*';
        $value_array = str_split(str_replace(' ', '', $value));
        $value = implode('%', $value_array);
    }

    /**
     * Check the value for inline operator.
     *
     * @param string &$operator
     * @param string &$value
     *
     * @return void
     */
    public static function checkInlineOperator(&$operator, &$value, $settings)
    {
        if (is_array($value)) {
            return;
        }

        // Boolean does not provide inline operations.
        if (array_get($settings, 'filter') === 'boolean') {
            return;
        }

        $value_array = explode(' ', trim($value), 2);

        if (count($value_array) == 1) {
            return;
        }

        $check_operator = array_shift($value_array);

        if (self::checkOperator($settings['filter'], $check_operator)) {
            $operator = $check_operator;
            $value = array_shift($value_array);
        }
    }

    /**
     * Check the value for null or not null.
     *
     * @param string &$operator
     * @param string &$value
     *
     * @return void
     */
    public static function checkNullOperator(&$operator, &$value)
    {
        if ($value === 'NULL' || $value === 'NOT_NULL') {
            $operator = $value;
            $value = '';
        }
    }

    /**
     * Check the value for empty or not empty.
     *
     * @param string &$operator
     * @param string &$value
     *
     * @return void
     */
    public static function checkEmptyOperator(&$operator, &$value)
    {
        if ($value === 'EMPTY' || $value === 'NOT_EMPTY') {
            $operator = $value;
            $value = '';
        }
    }

    /**
     * Filter by string.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByString($filter)
    {
        $operator = array_get($filter, 'operator');
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $value_one = array_get($filter, 'value_one');
        $value_two = array_get($filter, 'value_two');
        $settings = array_get($filter, 'settings');
        $positive = array_get($filter, 'positive');

        switch ($operator) {
            case '=':
            case '!=':
                $arguments = [$operator, $value_one];
                break;
            case '*=*':
            case '*!=*':
                $operator = (stripos($operator, '!') !== false) ? 'not ' : '';
                $operator .= 'like';
                $arguments = [$operator, '%'.$value_one.'%'];
                break;
            case '*=':
            case '*!=':
                $operator = (stripos($operator, '!') !== false) ? 'not ' : '';
                $operator .= 'like';
                $arguments = [$operator, '%'.$value_one];
                break;
            case '=*':
            case '!=*':
                $operator = (stripos($operator, '!') !== false) ? 'not ' : '';
                $operator .= 'like';
                $arguments = [$operator, $value_one.'%'];
                break;
            case 'EMPTY':
                $method = 'whereRaw';
                $arguments = "%s = ''";
                break;
            case 'NOT_EMPTY':
                $method = 'whereRaw';
                $arguments = "%s != ''";
                break;
            case 'IN':
                $method = 'whereIn';
                $arguments = [static::getListFromString($value_one)];
                break;
            case 'NOT_IN':
                $method = 'whereNotIn';
                $arguments = [static::getListFromString($value_one)];
                break;
            case 'NULL':
                $method = 'whereNull';
                break;
            case 'NOT_NULL':
                $method = 'whereNotNull';
                break;
        }

        return [
            'operator'  => $operator,
            'method'    => $method,
            'arguments' => $arguments,
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => $positive,
        ];
    }

    /**
     * Filter by number.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByNumber($filter)
    {
        $operator = array_get($filter, 'operator');
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $value_one = array_get($filter, 'value_one');
        $value_two = array_get($filter, 'value_two');
        $settings = array_get($filter, 'settings');
        $positive = array_get($filter, 'positive');

        switch ($operator) {
            case '=':
            case '!=':
            case '>':
            case '>=':
            case '<=':
            case '<':
                $arguments = [$operator, $value_one];
                break;
            case 'EMPTY':
                $method = 'whereRaw';
                $arguments = "%s = ''";
                break;
            case 'NOT_EMPTY':
                $method = 'whereRaw';
                $arguments = "%s != ''";
                break;
            case 'IN':
                $method = 'whereIn';
                $arguments = [static::getListFromString($value_one)];
                break;
            case 'NOT_IN':
                $method = 'whereNotIn';
                $arguments = [static::getListFromString($value_one)];
                break;
            case 'NULL':
                $method = 'whereNull';
                break;
            case 'NOT_NULL':
                $method = 'whereNotNull';
                break;
        }

        return [
            'operator'  => $operator,
            'method'    => $method,
            'arguments' => $arguments,
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => $positive,
        ];
    }

    /**
     * Filter by boolean.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByBoolean($filter)
    {
        $operator = array_get($filter, 'operator');
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $value_one = array_get($filter, 'value_one');
        $value_two = array_get($filter, 'value_two');
        $settings = array_get($filter, 'settings');
        $positive = array_get($filter, 'positive');

        switch ($value_one) {
            case 1:
            case '1':
                $arguments = [$operator, 1];
                break;
            case 0:
            case '0':
                $arguments = [$operator, 0];
                break;
        }

        return [
            'operator'  => $operator,
            'method'    => $method,
            'arguments' => $arguments,
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => $positive,
        ];
    }

    /**
     * Filter by list lookup.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByListLookup($filter)
    {
        $operator = array_get($filter, 'operator');
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $value_one = array_get($filter, 'value_one');
        $value_two = array_get($filter, 'value_two');
        $settings = array_get($filter, 'settings');
        $positive = array_get($filter, 'positive');

        $value_one = [];

        if (array_has($filter, 'settings.source')) {
            $model = array_get($filter, 'settings.model');
            $method_lookup = 'getFilter'.array_get($filter, 'settings.source').'Result';

            if (!empty($value_one) && method_exists($model, $method_lookup)) {
                $value_one = $model->$method_lookup($value_one);
            }
        }

        return [
            'operator'  => $operator,
            'method'    => $method,
            'arguments' => $arguments,
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => $positive,
        ];
    }

    /**
     * Filter by list lookup.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByList($filter)
    {
        $operator = array_get($filter, 'operator');
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $value_one = array_get($filter, 'value_one');
        $value_two = array_get($filter, 'value_two');
        $settings = array_get($filter, 'settings');
        $positive = array_get($filter, 'positive');

        switch ($operator) {
            case 'IN':
                $method = 'whereIn';
                $arguments = [$value_one];
                break;
            case 'NOT_IN':
                $method = 'whereNotIn';
                $arguments = [$value_one];
                break;
        }

        return [
            'operator'  => $operator,
            'method'    => $method,
            'arguments' => $arguments,
            'value_one' => $value_one,
            'value_two' => $value_two,
            'settings'  => $settings,
            'positive'  => $positive,
        ];
    }

    /**
     * This determines the foreign key relations automatically to prevent the need to figure out the columns.
     *
     * @param string $relation_name
     * @param string $operator
     * @param string $type
     * @param bool   $where
     *
     * @return Builder
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.LongVariable)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function modelJoin($relationships, $operator = '=', $type = 'left', $where = false)
    {
        if (!is_array($relationships)) {
            $relationships = [$relationships];
        }

        if (empty($this->query->columns)) {
            $this->query->selectRaw('DISTINCT '.$this->model->getTable().'.*');
        }

        foreach ($relationships as $relation_name) {
            $relation = $this->model->$relation_name();
            $relation_method = basename(str_replace('\\', '/', get_class($relation)));
            $table = $relation->getTable();

            if ($relation_method === 'HasOne' || $relation_method === 'BelongsTo') {
                $table = $relation->getRelated()->getTable();
            }

            list($parent_key, $foreign_key) = $this->getTableKeys($relation_method);

            foreach (Schema::getColumnListing($table) as $related_column) {
                $this->query->addSelect(new Expression("`$table`.`$related_column` AS `$table.$related_column`"));
            }

            $this->query->join($table, $parent_key, $operator, $foreign_key, $type, $where);

            if ($relation_method === 'BelongsToMany') {
                $related_foreign_key = $relation->getQualifiedRelatedKeyName();
                $related_relation = $relation->getRelated();
                $related_table = $related_relation->getTable();
                $related_qualified_key_name = $related_relation->getQualifiedKeyName();
                $this->query->join($related_table, $related_qualified_key_name, $operator, $related_foreign_key, $type, $where);
            }
        }

        $this->query->groupBy($this->model->getQualifiedKeyName());
    }

    /**
     * Get the table keys based on the relationship.
     *
     * @param string $relation_method
     *
     * @return array
     */
    private function getTableKeys($relation_method)
    {
        switch ($relation_method) {
            case 'BelongsTo':
                $parent_key = $relation->getQualifiedForeignKey();
                $foreign_key = $relation->getQualifiedOwnerKeyName();
            break;
            case 'HasOne':
                $parent_key = $table.'.'.$relation->getParentKey();
                $foreign_key = $table.'.'.$relation->getForeignKey();
            break;
            case 'HasMany':
                $parent_key = $relation->getQualifiedOwnerKeyName();
                $foreign_key = $relation->getQualifiedForeignKey();
            break;
            case 'BelongsToMany':
                $parent_key = $relation->getQualifiedParentKeyName();
                $foreign_key = $relation->getQualifiedForeignKeyName();
            break;
            default:
                return ['', ''];
        }

        return [
            $parent_key,
            $foreign_key,
        ];
    }

    /**
     * Apply search items to the query.
     *
     * @param Builder $query
     * @param array   $search
     *
     * @return void
     */
    private static function applySearch(&$query, $search)
    {
        foreach ($search as $name => $filters) {
            foreach ($filters as $filter) {
                self::applySearchFilter($query, $filter);
            }
        }
    }

    /**
     * Apply the filter item.
     *
     * @return void
     */
    private static function applySearchFilter(&$query, $filter)
    {
        $method = array_get($filter, 'method');
        $arguments = array_get($filter, 'arguments');
        $attributes = array_get($filter, 'settings.attributes');

        if (is_array($arguments)) {
            array_unshift($arguments, '');
        }

        $query->where(function ($query) use ($attributes, $method, $arguments) {
            $count = 0;
            foreach ($attributes as $attribute_name) {
                // Place attribute name into argument.
                if (is_array($arguments)) {
                    $arguments[0] = $attribute_name;

                // Argument is raw and using sprintf.
                } elseif (!is_array($arguments)) {
                    $arguments = [sprintf($arguments, self::quoteIdentifier($attribute_name))];
                }

                $query->$method(...$arguments);

                // Apply an or to the where.
                if ($count === 0) {
                    $method = 'or'.studly_case($method);
                }

                $count++;
            }
        });
    }

    /**
     * Quote a database identifier.
     *
     * @param string $str
     *
     * @return string
     */
    private static function quoteIdentifier($str)
    {
        $str = str_replace(['"', "'"], '', $str);

        return preg_replace("/((\w+)([\.]?))/", '"$2"$3', $str);
    }

    /**
     * Get the filter options.
     *
     * @return array
     */
    public static function getTypes()
    {
        return static::$filter_types;
    }

    /**
     * Check if a given type/operator is available.
     *
     * @param string $type
     * @param string $operator
     *
     * @return bool
     */
    public static function checkOperator($type, $operator)
    {
        return in_array($operator, self::getAllowedOperators($type));
    }

    /**
     * Get operators allowed for the given type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function getAllowedOperators($type)
    {
        if (!in_array($type, self::$filter_types)) {
            return [];
        }

        $data = self::getOperators($type);

        return array_keys($data);
    }

    /**
     * Get an string|number|date operators as array|string.
     *
     * @param string|number|date $type
     * @param bool               $operator
     *
     * @return array|string|null
     */
    public static function getOperators($type)
    {
        if (!in_array($type, self::getTypes())) {
            return [];
        }

        $source = snake_case($type).'_operators';

        return self::$$source;
    }

    /**
     * Get array of values from an input string.
     *
     * @param string $string
     *
     * @return array
     */
    private static function getListFromString($value)
    {
        if (is_string($value_array = $value)) {
            $value = str_replace([',', ' '], ';', $value);
            $value_array = explode(';', $value);
        }

        if (is_array($value_array)) {
            return array_filter(array_map('trim', $value_array));
        }

        return [];
    }
}
