<?php

namespace HnhDigital\ModelSearch;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;

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
     * UUID operators.
     *
     * @var array
     */
    protected static $uuid_operators = [
        '='         => ['value' => '=', 'name' => 'Equals', 'inline' => 'is'],
        '!='        => ['value' => '!=', 'name' => 'Not equal', 'inline' => 'is not'],
        'IN'        => ['value' => 'IN', 'name' => 'In...', 'inline' => 'in', 'helper' => 'Separated by semi-colon'],
        'NOT_IN'    => ['value' => 'NOT_IN', 'name' => 'Not in...', 'inline' => 'not in', 'helper' => 'Separated by semi-colon'],
        'NULL'      => ['value' => 'NULL', 'name' => 'NULL', 'inline' => 'is null'],
        'NOT_NULL'  => ['value' => 'NOT_NULL', 'name' => 'Not NULL', 'inline' => 'is not null'],
    ];

    /**
     * String operators.
     *
     * @var array
     */
    protected static $string_operators = [
        '*=*'       => ['value' => '*=*', 'name' => 'Contains', 'inline' => 'contains'],
        '*!=*'      => ['value' => '*!=*', 'name' => 'Not contain', 'inline' => 'does not contain'],
        '='         => ['value' => '=', 'name' => 'Equals', 'inline' => 'is'],
        '!='        => ['value' => '!=', 'name' => 'Not equal', 'inline' => 'is not'],
        '=*'        => ['value' => '=*', 'name' => 'Begins with', 'inline' => 'begins with'],
        '!=*'       => ['value' => '!=*', 'name' => 'Does not begin with', 'inline' => 'does not begin with'],
        '*='        => ['value' => '*=', 'name' => 'Ends with', 'inline' => 'ends with'],
        '!*='       => ['value' => '*!=', 'name' => 'Does not end with', 'does not end with'],
        'IN'        => ['value' => 'IN', 'name' => 'In...', 'inline' => 'in', 'helper' => 'Separated by semi-colon'],
        'NOT_IN'    => ['value' => 'NOT_IN', 'name' => 'Not in...', 'inline' => 'not in', 'helper' => 'Separated by semi-colon'],
        'EMPTY'     => ['value' => 'EMPTY', 'name' => 'Empty', 'inline' => 'is empty'],
        'NOT_EMPTY' => ['value' => 'NOT_EMPTY', 'name' => 'Not empty', 'inline' => 'is not empty'],
        'NULL'      => ['value' => 'NULL', 'name' => 'NULL', 'inline' => 'is null'],
        'NOT_NULL'  => ['value' => 'NOT_NULL', 'name' => 'Not NULL', 'inline' => 'is not null'],
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
     * The relationships.
     *
     * @var array
     */
    protected $relationships;

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
    public function __construct()
    {
    }

    /**
     * Process the search.
     *
     * @return void
     */
    public function run(&$query, $model, $request)
    {
        $this->query = $query;
        $this->getAttributes($model);
        $this->parseRequest($request);

        return $this->query();
    }

    /**
     * Run the query.
     *
     * @return builder
     */
    private function query()
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
    public function getAttributes($model)
    {
        $this->model = $model;
        $this->attributes = self::buildRelationshipAttributes($this->model);
        $this->attributes = $this->attributes + self::buildAttributes($this->model);

        return $this->attributes;
    }

    /**
     * Build a list of attributes from the relationships.
     *
     * @return void
     */
    private function buildRelationshipAttributes($model)
    {
        $result = [];

        foreach ($model->getSearchRelationships() as $method) {
            if (!method_exists($model, $method)) {
                continue;
            }

            $relation = self::getRelation($model->$method());
            $this->relationships[$method] = $relation;

            self::buildCastedAttributes($relation['model'], $result, $method);
            self::buildSearchAttributes($relation['model'], $result, $method);
        }

        return $result;
    }

    /**
     * Get the table keys based on the relation.
     *
     * @param Relation $relation
     *
     * @return array
     */
    private static function getRelation($relation)
    {
        $method = basename(str_replace('\\', '/', get_class($relation)));

        switch ($method) {
            case 'BelongsTo':
            case 'HasMany':
            case 'HasOne':
                $model = $relation->getRelated();
                break;
            default:
                $model = $relation;
        }

        $table = $model->getTable();

        switch ($method) {
            case 'BelongsTo':
            case 'BelongsToMany':
                $parent_key = $relation->getQualifiedForeignKey();
                $foreign_key = $relation->getQualifiedOwnerKeyName();
            break;
            case 'HasMany':
                $parent_key = $relation->getQualifiedParentKeyName();
                $foreign_key = $relation->getQualifiedForeignKeyName();
            break;
            case 'HasOne':
                $parent_key = $table.'.'.$relation->getParentKey();
                $foreign_key = $table.'.'.$relation->getForeignKeyName();
            break;
        }

        return [
            'model'       => $model,
            'method'      => $method,
            'table'       => $table,
            'parent_key'  => $parent_key,
            'foreign_key' => $foreign_key,
        ];
    }

    /**
     * Build a list of all possible attributes.
     *
     * @return void
     */
    public static function buildAttributes($model)
    {
        $result = [];

        self::buildCastedAttributes($model, $result);
        self::buildSearchAttributes($model, $result);

        return $result;
    }

    /**
     * Build attributes based on the casts array on the model.
     *
     * @param Model           $model
     * @param array           &$result
     * @param nullable|string $method
     *
     * @return void
     */
    private static function buildCastedAttributes($model, &$result, $method = null)
    {
        $model_name = 'self';
        $name_append = '';

        if (!is_null($method)) {
            $model_name = $method;
            $name_append = $method.'.';
        }

        // ModelSchema implementation gives us better data.
        if (class_exists('HnhDigital\ModelSchema\Model')
            && $model instanceOf \HnhDigital\ModelSchema\Model) {

            // Build attributes off the schema.
            foreach ($model->getSchema() as $name => $config) {
                $result[$name_append.$name] = [
                    'name'              => $name,
                    'title'             => Arr::get($config, 'title', $name),
                    'attributes'        => [sprintf('%s.%s', $model->getTable(), $name)],
                    'filter'            => self::convertCast(Arr::get($config, 'cast')),
                    'model'             => &$model,
                    'model_name'        => $model_name,
                    'source_model'      => Arr::get($config, 'model'),
                    'source_model_key'  => Arr::get($config, 'model_key', null),
                    'source_model_name' => Arr::get($config, 'model_name', 'display_name'),
                ];
            }

            return;
        }

        // Build attributes off the specified casts.
        foreach ($model->getCasts() as $name => $cast) {
            $result[$name_append.$name] = [
                'name'       => $name,
                'title'      => $name,
                'attributes' => [sprintf('%s.%s', $model->getTable(), $name)],
                'filter'     => self::convertCast($cast),
                'model'      => &$model,
                'model_name' => $model_name,
            ];
        }
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
     * Build attributes based on the the $search_attributes array on the model.
     *
     * @param Model           $model
     * @param array           &$result
     * @param nullable|string $method
     *
     * @return void
     */
    private static function buildSearchAttributes($model, &$result, $method = null)
    {
        $model_name = 'self';
        $name_append = '';

        if (!is_null($method)) {
            $model_name = $method;
            $name_append = $method.'.';
        }

        // Apply any custom attributes that have been specified.
        foreach ($model->getSearchAttributes() as $name => $settings) {
            // Specified name or use key.
            $title = Arr::get($settings, 'title', $name);

            if ($title === $name) {
                $title = title_case($title);
            }

            // Specified attributes, or attribute.
            $attributes = Arr::get($settings, 'attributes', Arr::get($settings, 'attribute', []));

            self::validateAttributes($model, $name, $attributes);

            // Allocate.
            $result[$name_append.$name] = [
                'name'              => $name,
                'title'             => $title,
                'attributes'        => $attributes,
                'filter'            => Arr::get($settings, 'filter', 'string'),
                'enable'            => Arr::get($settings, 'enable', []),
                'source'            => Arr::get($settings, 'source', $name),
                'model'             => &$model,
                'model_name'        => $model_name,
                'source_model'      => Arr::get($settings, 'model'),
                'source_model_key'  => Arr::get($settings, 'model_key', null),
                'source_model_name' => Arr::get($settings, 'model_name', 'display_name'),
            ];
        }
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

        // Is empty, use the name of the table + name.
        if (empty($attributes)) {
            $attributes = [sprintf('%s.%s', $model->getTable(), $name)];
        }

        // Check each of the attribute values.
        // Convert any prepended with a curly to an expression.
        foreach ($attributes as &$value) {
            if (substr($value, 0, 1) === '#' || substr($value, 0, 1) === '{') {
                $value = new Expression(substr($value, 1));
            }
        }
    }

    /**
     * Process the provided request.
     *
     * @return void
     */
    private function parseRequest($request)
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
            if (!Arr::has($this->attributes, $name)) {
                continue;
            }

            // Get the settings for the given attribute.
            $settings = Arr::get($this->attributes, $name);

            // Settings is empty.
            if (empty($settings)) {
                continue;
            }

            // Check and validate each of the filters.
            $filters = self::validateFilters($filters, $settings);

            // Search against current model.
            if (($model_name = Arr::get($settings, 'model_name')) === 'self') {
                $this->search_models['self'][$name] = $filters;
                continue;
            }

            // Search against an model via relationship.
            $models_used[$model_name] = true;
            $this->search_models[$model_name][$name] = $filters;
        }

        // Join the models to this query.
        if (count($models_used)) {
            $this->modelJoin($models_used);
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
        if (Arr::get($settings, 'filter') !== 'boolean' && count($filter) == 1) {
            array_unshift($filter, '');
        }

        // Split the filter array into operator, value1, value2
        $operator = Arr::get($filter, 0, '');
        $value_one = Arr::get($filter, 1, false);
        $value_two = Arr::get($filter, 2, false);

        // The wild-all setting was enabled.
        // Update value with all characters wildcarded.
        if (Arr::has($settings, 'enable.wild-all')) {
            self::applyWildAll($operator, $value_one);
        }

        self::checkInlineOperator($operator, $value_one, $settings);
        self::checkNullOperator($operator, $value_one);
        self::checkEmptyOperator($operator, $value_one);

        // Defaullt operator.
        if (empty($operator)) {
            $operator = self::getDefaultOperator(Arr::get($settings, 'filter'), $operator);
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
        $validation_method = 'filterBy'.studly_case(Arr::get($settings, 'filter'));
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
            case 'uuid':
                return 'IN';
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
    private static function applyWildAll(&$operator, &$value)
    {
        $positive = !(stripos($operator, '!') !== false || stripos($operator, 'NOT') !== false);
        $operator = $positive ? '*=*' : '*!=*';
        $value_array = str_split(str_replace(' ', '', $value));
        $value = implode('%', $value_array);
    }

    /**
     * Parse any inline operator.
     *
     * @return array
     */
    public static function parseInlineOperator($text)
    {
        $operator_name = 'contains';
        $operator = Arr::get($text, 0, '');
        $value = Arr::get($text, 1, false);

        self::checkInlineOperator($operator, $value);

        if (!empty($operator)) {
            $operator_name = Arr::get(self::getOperator('string', $operator), 'inline', 'contains');
        }

        return [
            $operator_name,
            $operator,
            $value,
        ];
    }

    /**
     * Check the value for inline operator.
     *
     * @param string &$operator
     * @param string &$value
     *
     * @return void
     */
    private static function checkInlineOperator(&$operator, &$value, $settings = [])
    {
        if (is_array($value)) {
            return;
        }

        // Boolean does not provide inline operations.
        if (Arr::get($settings, 'filter') === 'boolean') {
            return;
        }

        $value_array = explode(' ', trim($value), 2);

        if (count($value_array) == 1) {
            return;
        }

        $check_operator = array_shift($value_array);

        if (self::checkOperator(Arr::get($settings, 'filter', 'string'), $check_operator)) {
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
     * Filter by UUID.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByUuid($filter)
    {
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

        switch ($operator) {
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
     * Filter by string.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByString($filter)
    {
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

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
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

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
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

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
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

        if (Arr::has($filter, 'settings.source')) {
            $model = Arr::get($filter, 'settings.model');
            $method_lookup = 'getFilter'.studly_case(Arr::get($filter, 'settings.source')).'Result';

            if (!empty($value_one) && method_exists($model, $method_lookup)) {
                $value_one = $model->$method_lookup($value_one);
            }

            $operator = 'IN';
            $method = 'whereIn';
            $arguments = [$value_one];
        } else {
            $value_one = [];
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
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

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

        foreach ($relationships as $relation_name => $load_relationship) {

            // Required variables.
            $model = Arr::get($this->relationships, $relation_name.'.model');
            $method = Arr::get($this->relationships, $relation_name.'.method');
            $table = Arr::get($this->relationships, $relation_name.'.table');
            $parent_key = Arr::get($this->relationships, $relation_name.'.parent_key');
            $foreign_key = Arr::get($this->relationships, $relation_name.'.foreign_key');

            // Add the columns from the other table.
            // @todo do we need this?
            //$this->query->addSelect(new Expression("`$table`.*"));
            $this->query->join($table, $parent_key, $operator, $foreign_key, $type, $where);

            // The join above is to the intimidatory table. This joins the query to the actual model.
            if ($method === 'BelongsToMany') {
                $related_foreign_key = $model->getQualifiedRelatedKeyName();
                $related_relation = $model->getRelated();
                $related_table = $related_relation->getTable();
                $related_qualified_key_name = $related_relation->getQualifiedKeyName();
                $this->query->join($related_table, $related_qualified_key_name, $operator, $related_foreign_key, $type, $where);
            }
        }

        // Group by the original model.
        $this->query->groupBy($this->model->getQualifiedKeyName());
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
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $attributes = Arr::get($filter, 'settings.attributes');
        $positive = Arr::get($filter, 'positive');

        if (is_array($arguments)) {
            array_unshift($arguments, '');
        }

        $query->where(function ($query) use ($attributes, $method, $arguments, $positive) {
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
                if ($count === 0 && $positive) {
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
     * Get an operators details.
     *
     * @param string $type
     * @param string $operator
     *
     * @return bool
     */
    public static function getOperator($type, $operator)
    {
        $operators = self::getOperators($type);

        return Arr::get($operators, $operator, []);
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
