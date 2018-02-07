<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Services
 * @copyright Budget Dumpster, LLC 2017
 */
namespace BudgetDumpster\Services; 

use Illuminate\Database\Eloquent\Model;
use \RuntimeException;
use \Exception;
use Illuminate\Database\QueryException;
use BudgetDumpster\Exceptions\ModelNotFoundException;

class CRUDService extends AbstractService
{
    /**
     * Retrieve a model by id from eloquent
     *
     * @param Model $model
     * @param string $id
     * @param array $fields
     * @return Model
     * @throws ModelNotFoundException
     */
    public function retrieve(Model $model, $id, array $fields = [])
    {
        try {
            $fields = (!empty($fields)) ? $fields : ['*'];
            $model = $model->find($id, $fields);

            if (is_null($model)) {
            $this->logger->info(
                sprintf(
                    getenv('LOG_NOT_FOUND_MESSAGE'),
                    get_class($model),
                    $id
                ), ['id' => $id]); 
                    
                throw new ModelNotFoundException('We were unable to locate this model', 404);
            }

            return $model;
        } catch (QueryException $e) {
            $this->logger->error(
                sprintf(
                    getenv('LOG_NOT_FOUND_MESSAGE'),
                    get_class($model),
                    $id
                ), $this->getLoggingContext($e, [], true));
                    
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
                $this->logger->error(
                    sprintf(
                        getenv('LOG_CREATE_FAILED_MESSAGE'),
                        get_class($model),
                        get_class($this) . '::create'
                    ), $input); 

                throw new RuntimeException('There was an error saving the model', 500);
            }

            return $model;
        } catch (QueryException $e) {
                $this->logger->error(
                    sprintf(
                        getenv('LOG_CREATE_FAILED_MESSAGE'),
                        get_class($model),
                        get_class($this) . '::create'
                    ), $this->getLoggingContext($e, [$input], true)); 

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
                $this->logger->error(
                    sprintf(
                        getenv('LOG_UPDATE_FAILED_MESSAGE'),
                        get_class($model),
                        $id,
                        get_class($this) . '::update'
                    ), $input); 

                throw new RuntimeException('There was an error saving the model', 500);
            }

            return $model;
        } catch (QueryException $e) {
                $this->logger->error(
                    sprintf(
                        getenv('LOG_UPDATE_FAILED_MESSAGE'),
                        get_class($model),
                        $id,
                        get_class($this) . '::update'
                    ), $this->getLoggingContext($e, [$input], true)); 
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
                $this->logger->error(
                    sprintf(
                        getenv('LOG_DELETE_FAILED_MESSAGE'),
                        get_class($model),
                        $id
                    ), []); 
                throw new RuntimeException('There was an error deleting the model', 500);
            }
            return true;
        } catch (QueryException $e) {
                $this->logger->error(
                    sprintf(
                        getenv('LOG_DELETE_FAILED_MESSAGE'),
                        get_class($model),
                        $id
                    ), $this->getLoggingContext($e, [], true)); 
            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Retrieve a paginated collection of models based on the per_page and page
     * arguments provided
     *
     * @param Model $model
     * @param int $page
     * @param int $per_page
     * @param array $where
     * @return Array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function retrieveAll(
        Model $model,
        $page = 1,
        $per_page = 20,
        array $where = ['field' => 'id', 'operator' => '!=', 'value' => null]
    ) {
    
        if (!is_int($page) || !is_int($per_page)) {
            $this->logger->error(
                 sprintf(
                     getenv('LOG_RETRIEVAL_ERROR_MESSAGE')
                 ), ['page' => $page, 'per_page' => $per_page]
            ); 

            throw new \InvalidArgumentException('The value of the page and per_page values must be integers', 400);
        }

        try {
            $base_search = $model->where($where['field'], $where['operator'], $where['value']);
            $count = $base_search->get()->count();
            $total_pages = ceil($count/$per_page);
            $offset = ($page - 1) * $per_page;
            $models = $base_search->take($per_page)->skip($offset)->get();
            return [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $count,
                'total_pages' => $total_pages,
                'models' => $models
            ];
        } catch (QueryException $e) {
                $this->logger->error(
                    sprintf(
                        getenv('LOG_RETRIEVAL_ERROR_MESSAGE')
                    ), $this->getLoggingContext($e, [], true)
                ); 

            throw new RuntimeException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Add a relationship to an existing model
     *
     * @param Model $model
     * @param Model $relatedModel
     * @param string $relationshipName
     * @return Model
     */
    public function addRelationship(Model $model, Model $relatedModel, $relationshipName)
    {
        $savedModel = $model->$relationshipName()->save($relatedModel);   
        return $savedModel;
    }
}
