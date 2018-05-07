<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Services
 * @copyright Budget Dumpster, LLC 2017
 * @version 1.0.0
 */
namespace BudgetDumpster\Services; 

use Illuminate\Database\Eloquent\Model;
use \RuntimeException;
use \Exception;
use \InvalidArgumentException;
use Illuminate\Database\QueryException;
use BudgetDumpster\Exceptions\ModelNotFoundException;

class CRUDService
{
    /**
     * Retrieve a model by id from eloquent
     *
     * @param mixed $model
     * @param string $id
     * @param array $fields
     * @return Model
     * @throws ModelNotFoundException
     */
    public function retrieve($model, $id, array $fields = [])
    {
        try {
            if (
                !$model instanceof \Illuminate\Database\Eloquent\Model &&
                !$model instanceof \Illuminate\Database\Eloquent\Builder
            ) {
                throw new InvalidArgumentException("A model instance or Builder instance is required for the retrieve method", 400);
            } 
            $fields = (!empty($fields)) ? $fields : ['*'];
            $model = $model->find($id, $fields);

            if (is_null($model)) {
                throw new ModelNotFoundException('We were unable to locate this model', 404);
            }

            return $model;
        } catch (QueryException $e) {
            throw new ModelNotFoundException($e->getMessage(), 404, $e);
        }
    }

    /**
     * Attempt to create a model from input data
     *
     * @param Model $model
     * @param Array $input_data
     * @param string $id
     * @return Model
     * @throws \RuntimeException
     */
    public function create(Model $model, array $input, $id)
    {
        try {
            $model->id = $id;

            foreach ($input as $key => $value) {
                $model->$key = $value;
            }

            if (!$model->save()) {
                $input['id'] = $id;

                throw new RuntimeException('There was an error saving the model', 500);
            }

            return $model;
        } catch (QueryException $e) {
            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Attempt to update an existing model
     *
     * @param Model $model
     * @param Array $input
     * @param string $id
     * @return Model
     * @throws \RuntimeException
     * @throws ModelNotFoundException
     */
    public function update(Model $model, array $input, $id)
    {
        try {
            $model = $this->retrieve($model, $id);

            foreach ($input as $key => $value) {
                $model->$key = $value;
            }

            if (!$model->save()) {
                $input['id'] = $id;

                throw new RuntimeException('There was an error saving the model', 500);
            }

            return $model;
        } catch (QueryException $e) {
            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Attempt to delete an existing model
     *
     * @param Model $model
     * @param string $id
     * @return boolean
     * @throws \RuntimeException
     * @throws ModelNotFoundException
     */
    public function delete(Model $model, $id)
    {
        try {
            $model = $this->retrieve($model, $id);
            if (!$model->delete()) {
                throw new RuntimeException('There was an error deleting the model', 500);
            }
            return true;
        } catch (QueryException $e) {
            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Retrieve a paginated collection of models based on the per_page and page
     * arguments provided
     *
     * @param Model|Builder mixed $model
     * @param int $page
     * @param int $per_page
     * @param array $where
     * @return Array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function retrieveAll(
        $model,
        $page = 1,
        $per_page = 20,
        array $where = ['field' => 'id', 'operator' => '!=', 'value' => null],
        array $fields = ['*']
    ) {
    
        if (!is_int($page) || !is_int($per_page)) {
            throw new \InvalidArgumentException('The value of the page and per_page values must be integers', 400);
        }

        try {
            $base_search = $model->where($where['field'], $where['operator'], $where['value']);
            $count = $base_search->get()->count();
            $total_pages = ceil($count/$per_page);
            $offset = ($page - 1) * $per_page;
            $models = $base_search;
            if ($per_page != -1) {
                $models = $models->take($per_page)->skip($offset);
            }
            $models = $models->get($fields);
            return [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $count,
                'total_pages' => $total_pages,
                'models' => $models
            ];
        } catch (QueryException $e) {
            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }
}
