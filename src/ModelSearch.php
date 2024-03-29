<?php

namespace HnhDigital\ModelSearch;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        'scope',
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
        '*!='       => ['value' => '*!=', 'name' => 'Does not end with', 'does not end with'],
        'IN '        => ['value' => 'IN', 'name' => 'In...', 'inline' => 'in', 'helper' => 'Separated by semi-colon'],
        'NOT_IN '    => ['value' => 'NOT_IN', 'name' => 'Not in...', 'inline' => 'not in', 'helper' => 'Separated by semi-colon'],
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
        'IN '       => ['value' => 'IN', 'name' => 'In...', 'helper' => 'Separated by semi-colon'],
        'NOT_IN '   => ['value' => 'NOT_IN', 'name' => 'Not in...', 'helper' => 'Separated by semi-colon'],
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
     * Scope operators.
     *
     * @var array
     */
    protected static $scope_operators = [
        'IN '     => ['value' => 'IN', 'name' => 'In selected'],
        'NOT_IN ' => ['value' => 'NOT_IN', 'name' => 'Not in selected'],
    ];

    /**
     * List operators.
     *
     * @var array
     */
    protected static $list_operators = [
        'IN '     => ['value' => 'IN', 'name' => 'In selected'],
        'NOT_IN ' => ['value' => 'NOT_IN', 'name' => 'Not in selected'],
    ];

    /**
     * List operators.
     *
     * @var array
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    protected static $list_lookup_operators = [
        'IN '     => ['value' => 'IN', 'name' => 'In selected'],
        'NOT_IN ' => ['value' => 'NOT_IN', 'name' => 'Not in selected'],
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
        $this->processRequest($request);

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
            $this->query->where(function ($query) use ($filters) {
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
            if (! method_exists($model, $method)) {
                continue;
            }

            $relation = self::getRelation($model->$method());
            $this->relationships[$method] = $relation;
            $this->relationships[$relation['table']] = &$relation;

            self::buildCastedAttributes($relation['model'], $result, $method);
            self::buildSearchAttributes($relation['model'], $result, $method);
            unset($relation);
        }

        return $result;
    }

    /**
     * Get the table keys based on the relation.
     *
     * @param  Relation  $relation
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
                $parent_key = $relation->getQualifiedForeignKeyName();
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
     * @param  Model  $model
     * @param  array  &$result
     * @param  nullable|string  $method
     * @return void
     */
    private static function buildCastedAttributes($model, &$result, $method = null)
    {
        $model_name = 'self';
        $name_append = '';

        if (! is_null($method)) {
            $model_name = $method;
            $name_append = $method.'.';
        }

        // ModelSchema implementation gives us better data.
        if (class_exists('HnhDigital\ModelSchema\Model')
            && $model instanceof \HnhDigital\ModelSchema\Model) {
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
            case 'integer':
            case 'int':
            case 'numeric':
            case 'decimal':
            case 'float':
            case 'double':
                return 'number';
        }

        return $cast;
    }

    /**
     * Build attributes based on the the $search_attributes array on the model.
     *
     * @param  Model  $model
     * @param  array  &$result
     * @param  nullable|string  $method
     * @return void
     */
    private static function buildSearchAttributes($model, &$result, $method = null)
    {
        $model_name = 'self';
        $name_append = '';

        if (! is_null($method)) {
            $model_name = $method;
            $name_append = $method.'.';
        }

        // Apply any custom attributes that have been specified.
        foreach ($model->getSearchAttributes() as $name => $settings) {
            // Specified name or use key.
            $title = Arr::get($settings, 'title', $name);

            if ($title === $name) {
                $title = Str::title($title);
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
        if (! is_array($attributes)) {
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
     * Parse the given request.
     *
     * Values can be provided as a native array, json encoded or a string.
     *
     * @param  mixed  $request
     * @return array
     */
    protected static function parseRequest($request)
    {
        if (empty($request)) {
            return [];
        }

        // Array provided.
        if (is_array($request)) {
            return $request;
        }

        // Check if JSON, return array.
        $json_request = json_decode($request, true);

        if (! is_null($json_request)) {
            return $json_request;
        }

        // Convert url query string into array.
        parse_str($request, $query);

        return $query;
    }

    /**
     * Process the provided request.
     *
     * @return void
     */
    private function processRequest($request)
    {
        $this->request = self::parseRequest($request);

        if (empty($this->request)) {
            return;
        }

        // Models used in this request.
        $models_used = [];

        // Review each request.
        foreach ($this->request as $name => $filters) {
            $name = str_replace('-', '.', $name);

            // This name is not present in available attributes.
            if (! Arr::has($this->attributes, $name)) {
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

            $attributes = Arr::wrap(Arr::get($settings, 'attributes', []));

            // Scan the attributes array for attributes not against this model.
            foreach ($attributes as &$attribute_name) {
                // Check if this attribute name is an expression.
                $is_expression = $attribute_name instanceof Expression || is_object($attribute_name);
                $attribute_name_expression = $attribute_name;

                if ($attribute_name instanceof Expression) {
                    $attribute_name = $attribute_name->getValue(new \Illuminate\Database\Schema\Grammars\MySqlGrammar());
                } elseif (is_object($attribute_name)) {
                    $attribute_name = (string) $attribute_name;
                }

                preg_match_all("/([a-zA-Z_]*)\.(?:[a-zA-Z_]*)/", $attribute_name, $matches);

                // Add models being used in these attributes.
                foreach ($matches[1] as $model_name) {
                    if ($this->model->getTable() === $model_name) {
                        continue;
                    }

                    if (! isset($models_used[$model_name])) {
                        $models_used[$model_name] = Str::random(6);
                    }

                    $attribute_name = str_replace($model_name, "{$model_name}_{$models_used[$model_name]}", $attribute_name);
                }

                // Recast expression.
                if ($is_expression) {
                    $attribute_name = new Expression($attribute_name);
                }

                unset($attribute_name);
            }

            foreach ($filters as &$filter) {
                Arr::set($filter, 'settings.attributes', $attributes);
            }

            // Search against current model.
            if (($model_name = Arr::get($settings, 'model_name')) === 'self') {
                $this->search_models['self'][$name] = $filters;
                continue;
            }

            // Search against an model via relationship.
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
        if (! is_array($filters)) {
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
     * @param  array|string  $filter
     * @param  array  $settings
     * @return array|null
     */
    private static function validateFilterItem($filter, $settings)
    {
        // Convert string to filter array.
        if (! is_array($filter)) {
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

        self::checkNumberBetweenOperator($operator, $value_one, $settings);
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
        $validation_method = 'filterBy'.Str::studly(Arr::get($settings, 'filter'));

        if (method_exists(__CLASS__, $validation_method)) {
            $filter = self::{$validation_method}($filter);
        }

        if ($filter === false) {
            return $filter;
        }

        // Update based on operator.
        $filter['positive'] = ! (stripos($operator, '!') !== false || stripos($operator, 'NOT') !== false);

        return $filter;
    }

    /**
     * Get the default operator.
     *
     * @param  string  $filter
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
     * @param  string  &$value
     * @return void
     */
    private static function applyWildAll(&$operator, &$value)
    {
        $positive = ! (stripos($operator, '!') !== false || stripos($operator, 'NOT') !== false);
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

        if (! empty($operator)) {
            $operator_name = Arr::get(self::getOperator('string', $operator), 'inline', 'contains');
        }

        return [
            $operator_name,
            $operator,
            $value,
        ];
    }

    /**
     * Check the value for value between two numbers.
     *
     * @param  string  &$operator
     * @param  string  &$value
     * @return void
     */
    private static function checkNumberBetweenOperator(&$operator, &$value, $settings = [])
    {
        if (is_array($value)) {
            return;
        }

        // Boolean does not provide inline operations.
        if (($filter = Arr::get($settings, 'filter')) !== 'number') {
            return;
        }

        preg_match('/^(?:-){0,}([0-9]){1,}(?: ){0,}><(?: ){0,}(?:-){0,}([0-9]){1,}$/', trim($value), $matches);

        if (count($matches) <= 1) {
            return;
        }

        $operator = 'BETWEEN';
        $value = [trim($matches[1]), trim($matches[2])];
    }

    /**
     * Check the value for inline operator.
     *
     * @param  string  &$operator
     * @param  string  &$value
     * @return void
     */
    private static function checkInlineOperator(&$operator, &$value, $settings = [])
    {
        if (is_array($value)) {
            return;
        }

        // Boolean does not provide inline operations.
        if (($filter = Arr::get($settings, 'filter')) === 'boolean') {
            return;
        }

        if ($operator !== '' && in_array($operator, self::getAllowedOperators($filter))) {
            return;
        }

        // Preg quote all the operators.
        $operators = array_map('preg_quote', self::getAllowedOperators($filter));

        // Match any one of these operators.
        $operator_regex = implode('|', $operators);

        preg_match("/((?:{$operator_regex})*)(.*?)$/", trim($value), $matches);

        if (count($matches) <= 1) {
            return;
        }

        $operator = $matches[1];
        $value = trim($matches[2]);
    }

    /**
     * Check the value for null or not null.
     *
     * @param  string  &$operator
     * @param  string  &$value
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
     * @param  string  &$operator
     * @param  string  &$value
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
            case 'BETWEEN':
                $method = 'whereBetween';
                $arguments = [$value_one];
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
     * Filter by scope.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function filterByScope($filter)
    {
        $operator = Arr::get($filter, 'operator');
        $method = Arr::get($filter, 'method');
        $source = Arr::get($filter, 'settings.source');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

        if (Arr::has($filter, 'settings.source')) {
            $model = Arr::get($filter, 'settings.model');

            $method_transform = 'transform'.Str::studly($source).'Value';

            if (method_exists($model, $method_transform)) {
                $value_one = $model->$method_transform($value_one);
            }

            $method_lookup = 'scope'.Str::studly($source);

            if (! method_exists($model, $method_lookup)) {
                return false;
            }

            $method = Str::camel($source);
            $arguments = [$value_one];
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
        $source = Arr::get($filter, 'settings.source');
        $arguments = Arr::get($filter, 'arguments');
        $value_one = Arr::get($filter, 'value_one');
        $value_two = Arr::get($filter, 'value_two');
        $settings = Arr::get($filter, 'settings');
        $positive = Arr::get($filter, 'positive');

        if (Arr::has($filter, 'settings.source')) {
            $model = Arr::get($filter, 'settings.model');

            $method_transform = 'transform'.Str::studly($source).'Value';

            if (method_exists($model, $method_transform)) {
                $value_one = $model->$method_transform($value_one);
            }

            $method_lookup = 'getFilter'.Str::studly($source).'Result';

            if (empty($value_one)) {
                return false;
            }

            if (method_exists($model, $method_lookup)) {
                $value_one = $model->$method_lookup($value_one);
            } else {
                throw new \Exception(sprintf('%s is missing method %s', $model->getTable(), $method_lookup));
            }

            if ($value_one === false) {
                return false;
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
     * Search the related table.
     *
     * @param  string  $relation_name
     * @param  string  $operator
     * @param  string  $type
     * @param  bool  $where
     * @return Builder
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.LongVariable)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function modelJoin($relationships, $operator = '=', $type = 'left', $where = false)
    {
        $relationships = Arr::wrap($relationships);

        // Check relationships array and remove any that we don't have conenction for.
        foreach ($relationships as $relation_name => $unique_table_id) {
            if (! Arr::has($this->relationships, $relation_name)) {
                unset($relationships[$relation_name]);
            }
        }

        // No model joining required, skip.
        if (count($relationships) === 0) {
            return;
        }

        // Distinct rows based on key name.
        if (empty($this->query->columns)) {
            $this->query->selectRaw('DISTINCT `'.$this->model->getTable().'`.`'.$this->model->getKeyName().'`,`'.$this->model->getTable().'`.*');
        }

        // Process each relatioinship and add to the query.
        foreach ($relationships as $relation_name => $unique_table_id) {
            $table_name = "{$relation_name}_{$unique_table_id}";

            // Required variables.
            $model = Arr::get($this->relationships, $relation_name.'.model');
            $method = Arr::get($this->relationships, $relation_name.'.method');
            $table = Arr::get($this->relationships, $relation_name.'.table');
            $parent_key = Arr::get($this->relationships, $relation_name.'.parent_key');
            $foreign_key = Arr::get($this->relationships, $relation_name.'.foreign_key');

            $this->query->join(
                "{$table} as {$table_name}",
                $parent_key,
                $operator,
                preg_replace_callback(
                    '/^('.preg_quote($relation_name).")\.((?:`)?.*?(?:`)?)$/",
                    function ($matches) use ($table_name) {
                        return $table_name.'.'.str_replace('`', '', $matches[2]);
                    },
                    $foreign_key
                ),
                $type,
                $where
            );

            // The join above is to the intimidatory table. This joins the query to the actual model.
            if ($method === 'BelongsToMany') {
                $related_foreign_key = $model->getQualifiedRelatedKeyName();
                $related_relation = $model->getRelated();
                $related_table = $related_relation->getTable();
                $related_qualified_key_name = $related_relation->getQualifiedKeyName();
                $this->query->join($related_table, $related_qualified_key_name, $operator, $related_foreign_key, $type, $where);
            }
        }
    }

    /**
     * Apply search items to the query.
     *
     * @param  Builder  $query
     * @param  array  $search
     * @return void
     */
    private static function applySearch(&$query, $search)
    {
        foreach ($search as $name => $filters) {
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    self::applySearchFilter($query, $filter);
                }
            });
        }
    }

    /**
     * Apply the filter item.
     *
     * @return void
     */
    private static function applySearchFilter(&$query, $filter)
    {
        $filter_type = Arr::get($filter, 'settings.filter');
        $method = Arr::get($filter, 'method');
        $arguments = Arr::get($filter, 'arguments');
        $attributes = Arr::get($filter, 'settings.attributes');
        $positive = Arr::get($filter, 'positive');

        if ($filter_type !== 'scope' && is_array($arguments)) {
            array_unshift($arguments, '');
        }

        $query->orWhere(function ($query) use ($filter_type, $attributes, $method, $arguments, $positive) {
            $count = 0;
            foreach ($attributes as $attribute_name) {
                // Place attribute name into argument.
                if ($filter_type !== 'scope' && is_array($arguments)) {
                    $arguments[0] = $attribute_name;

                // Argument is raw and using sprintf.
                } elseif (! is_array($arguments)) {
                    $arguments = [sprintf($arguments, self::quoteIdentifier($attribute_name))];
                }

                if ($filter_type === 'scope') {
                    $arguments[] = $positive;
                }

                $query->$method(...$arguments);

                // Apply an or to the where.
                if ($filter_type !== 'scope' && $count === 0 && $positive) {
                    $method = 'or'.Str::studly($method);
                }

                $count++;
            }
        });
    }

    /**
     * Quote a database identifier.
     *
     * @param  string  $str
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
     * @param  string  $type
     * @param  string  $operator
     * @return bool
     */
    public static function checkOperator($type, $operator)
    {
        return in_array($operator, self::getAllowedOperators($type));
    }

    /**
     * Get an operators details.
     *
     * @param  string  $type
     * @param  string  $operator
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
     * @param  string  $type
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
     * @param  string|number|date  $type
     * @param  bool  $operator
     * @return array|string|null
     */
    public static function getOperators($type)
    {
        if (! in_array($type, self::getTypes())) {
            return [];
        }

        $source = Str::snake($type).'_operators';

        return self::$$source;
    }

    /**
     * Get array of values from an input string.
     *
     * @param  string  $string
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
