<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Tests
 * @copyright BudgetDumpster LLC, 2017
 */
namespace BudgetDumpster\Tests;

use PHPUnit\Framework\TestCase;
use BudgetDumpster\Services\CRUDService;
use BudgetDumpster\Exceptions;

class CRUDServiceTest extends TestCase
{
    /**
     * @var Illuminate\Database\Eloquent\Model
     */
    private $modelMock;

    /**
     * @var Illuminate\Database\Eloquent\Builder
     */
    private $builderMock;

    /**
     * @var Illuminate\Database\Eloquent\Collection
     */
    private $collectionMock;

    /**
     * Test Setup method
     */
    protected function setUp()
    {
        $this->modelMock = $this->getMockBuilder('Illuminate\Database\Eloquent\Model')
            ->setMethods(['save', 'delete', 'find', 'newQuery', 'load'])
            ->getMock();

        $this->builderMock = $this->getMockBuilder('Illuminate\Database\Eloquent\Builder')
            ->setMethods(['take', 'skip', 'get', 'count', 'where'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->getMockBuilder('Illuminate\Database\Eloquent\Collection')
            ->setMethods(['count'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test Tear Down Method
     */
    protected function tearDown()
    {
        unset($this->modelMock);
        unset($this->builderMock);
        unset($this->collectionMock);
    }

    /**
     * Test to ensure if a model is not found in the retrieve method
     * that a ModelNotFoundException is thrown
     *
     * @expectedException \BudgetDumpster\Exceptions\ModelNotFoundException
     * @group services
     * @group crud
     */
    public function testRetrieveModelNotFoundThrowsException()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $crudService = new CRUDService();
        $crudService->retrieve($this->modelMock, $id);
    }

    /**
     * Test to ensure if a model or builder is not passed to the retrieve
     * method, an InvalidArgumentException will be thrown
     *
     * @expectedException \InvalidArgumentException
     */
    public function testNonModelArgumentThrowsException()
    {
        $notModel = new \stdClass;
        $crudService = new CRUDService();
        $crudService->retrieve($notModel, '123456');
    }

    /**
     * Test to ensure if model throws query exception in retrieve, exception
     * will be caught and rethrown
     *
     * @expectedException \BudgetDumpster\Exceptions\ModelNotFoundException
     * @group services
     * @group crud
     */
    public function testRetrieveModelQueryExceptionRethrowsException()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $crudService = new CRUDService();
        $crudService->retrieve($this->modelMock, $id);
    }

    /**
     * Test to ensure a retrieve call with no exceptions will return a full model
     *
     * @group services
     * @group crud
     */
    public function testSuccessfulRetrieveCallReturnsModel()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $response = $crudService->retrieve($this->modelMock, $id);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Model', $response);
    }

    /**
     * Test to ensure a retrieve call with relationships returns successfully
     *
     * @group services
     * @group crud
     */
    public function testSuccessfulRetrievalWithRelationshipsReturnsModel()
    {
        $id = 'ab43ca3434f324acde';
        $foo = new \Stdclass;
        $foo->id = 'bbace5234324feace';
        $foo->name = 'Foo 2';
        $foo_array = [$foo];
        $this->modelMock->relationNames = ['test'];
        $this->modelMock->foo = $foo_array;
        $this->modelMock->foo_id = $foo->id;

        $this->modelMock->expects($this->any())
            ->method('load')
            ->with($this->isType('string'))
            ->will($this->returnValue($this->modelMock));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $response = $crudService->retrieve($this->modelMock, $id);
        $this->assertEquals($this->modelMock->id, $response->id);
    }

    /**
     * Test to ensure if the save method fails on create, throw a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testCreateMethodThrowsExceptionIfSaveFails()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $input = [
            'name' => 'test',
            'description' => 'A test description'
        ];

        $crudService = new CRUDService();
        $crudService->create($this->modelMock, $input, $id);
    }

    /**
     * Test to ensure if model throws Query Exception it is caught and rethrown as a RuntimeException
     *
     * @expectedException \RuntimeException
     */
    public function testModelThrowingQueryExceptionRethrowsRuntimeException()
    {
        $id = 'ab43ca3434f324acde';

        $input = [
            'name' => 'Test',
            'description' => 'Test description'
        ];

        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $crudService = new CRUDService();
        $crudService->create($this->modelMock, $input, $id);
    }

    /**
     * Test to ensure the create method returns the newly created model
     * on a successful save
     */
    public function testCreateMethodReturnsModelOnSuccess()
    {
        $id = 'ab43ca3434f324acde';

        $input = [
            'name' => 'Test',
            'description' => 'Test description'
        ];

        $this->modelMock->id = $id;
        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $crudService = new CRUDService();
        $response = $crudService->create($this->modelMock, $input, $id);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Model', $response);
    }

    /**
     * Test on Update to ensure a model not found exception is thrown if
     * the model cannot be found
     *
     * @expectedException \BudgetDumpster\Exceptions\ModelNotFoundException
     */
    public function testUpdateMethodThrowsModelNotFoundException()
    {
        $id = 'ab43ca3434f324acde';

        $input = [
            'name' => 'Test',
            'description' => 'Test Description'
        ];

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $crudService = new CRUDService();
        $crudService->update($this->modelMock, $input, $id);
    }

    /**
     * Test to ensure a runtime exception is thrown in the update method if
     * the save fails
     *
     * @expectedException \RuntimeException
     */
    public function testUpdateMethodThrowsRuntimeExceptionOnSaveFailure()
    {
        $id = 'ab43ca3434f324acde';

        $input = [
            'name' => 'Test',
            'description' => 'Test Description'
        ];

        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $crudService->update($this->modelMock, $input, $id);
    }

    /**
     * Test to ensure an update request where the model throws a query
     * exception is caught and rethrown as a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testUpdateMethodRethrowsExceptionOnSaveFailure()
    {
        $id = 'ab43ca3434f324acde';
        $input = [
            'name' => 'Test',
            'description' => 'Test Description'
        ];

        $this->modelMock->id = $id;
        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $crudService->update($this->modelMock, $input, $id);
    }

    /**
     * Test to ensure the model is updated and returned on a successful update
     */
    public function testUpdateMethodReturnsModelOnSuccess()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->id = $id;

        $input = [
            'name' => 'Test',
            'description' => 'Test Description'
        ];

        $this->modelMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $response = $crudService->update($this->modelMock, $input, $id);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Model', $response);
    }

    /**
     * Test to ensure delete method returns ModelNotFoundException if model
     * cannot be retrieved
     *
     * @expectedException \BudgetDumpster\Exceptions\ModelNotFoundException
     */
    public function testDeleteReturnsExceptionIfModelCannotBeFound()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $crudService = new CRUDService();
        $crudService->delete($this->modelMock, $id);
    }

    /**
     * Test ensure a RuntimeException is thrown if the delete method fails
     *
     * @expectedException \RuntimeException
     */
    public function testDeleteFailureThrowsRuntimeException()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->id = $id;

        $this->modelMock->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $crudService->delete($this->modelMock, $id);
    }

    /**
     * Test to ensure if delete throws a query exception, it will be 
     * caught and rethrown as a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testDeleteFailureCatchsQueryExceptionThrowsAsRuntime()
    {
        $id = 'ab43ca3434f324acde';
        $this->modelMock->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $crudService->delete($this->modelMock, $id);
    }

    /**
     * Test to ensure true is returned if the delete succeeds
     */
    public function testDeleteReturnsTrueOnSuccess()
    {
        $id = 'ab43ca3434f324acde';

        $this->modelMock->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->modelMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->modelMock));

        $crudService = new CRUDService();
        $response = $crudService->delete($this->modelMock, $id);

        $this->assertTrue($response);
    }

    /**
     * Test to ensure a get retrieveAll search will catch a query exception
     * and rethrow it as a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testRetrieveAllCatchesQueryExceptionAndRethrowsRuntime()
    {
        $this->builderMock->expects($this->any())
            ->method('where')
            ->with('id', '!=', null)
            ->will($this->returnValue($this->builderMock));

        $this->builderMock->expects($this->any())
            ->method('get')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->modelMock->expects($this->once())
            ->method('newQuery')
            ->will($this->returnValue($this->builderMock));

        $crudService = new CRUDService();
        $crudService->retrieveAll($this->modelMock);
    }

    /**
     * Test to ensure a collection can be returned with default argument values
     *
     * @dataProvider retrieveAllProvider
     */
    public function testRetrieveAllIsSuccessfullWithDefaultArgs($page, $per_page, $total)
    {
        $expected_total_pages = ceil($total/$per_page);
        $offset = ($page - 1) * $per_page;

        $this->collectionMock->expects($this->once())
            ->method('count')
            ->will($this->returnValue($total));

        $this->builderMock->expects($this->any())
            ->method('where')
            ->with('id', '!=', null)
            ->will($this->returnValue($this->builderMock));

        $this->builderMock->expects($this->any())
            ->method('take')
            ->with($per_page)
            ->will($this->returnValue($this->builderMock));

        $this->builderMock->expects($this->any())
            ->method('skip')
            ->with($offset)
            ->will($this->returnValue($this->builderMock));

        $this->builderMock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->collectionMock));

        $this->modelMock->expects($this->once())
            ->method('newQuery')
            ->will($this->returnValue($this->builderMock));

        $crudService = new CRUDService();
        $response = $crudService->retrieveAll($this->modelMock, $page, $per_page);

        $this->assertEquals($page, $response['page']);
        $this->assertEquals($per_page, $response['per_page']);
        $this->assertEquals($expected_total_pages, $response['total_pages']);
        $this->assertEquals($total, $response['total']);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $response['models']);
    }

    /**
     * Test to ensure that invalid page and/or per page parameters will throw an InvalidArgumentException
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidPaginationProvider
     */
    public function testRetrieveAllBadPageOrPageTypeArgumentValuesThrowAnException($page, $per_page)
    {
        $crudService = new CRUDService();
        $crudService->retrieveAll($this->modelMock, $page, $per_page);
    }

    /**
     * Data Provider for testing the pagination of retrieveAll
     */
    public function retrieveAllProvider()
    {
        return [
            [1, 20, 30],
            [2, 2, 20],
            [3, 4, 12]
        ];
    }

    /**
     * Invalid pagination provider
     */
    public function invalidPaginationProvider()
    {
        return [
            [1, "20"],
            ["2", 10],
            [3, []],
            [new \Stdclass, 20]
        ];
    }
}
