<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package TextMessaging
 * @subpackage Tests
 * @subpackage Unit
 * @subpackage Services
 * @copyright BudgetDumpster LLC, 2017
 */
namespace TextMessaging\Tests\Unit\Services;
use PHPUnit\Framework\TestCase;
use TextMessaging\Controllers;
use TextMessaging\Services\CRUDService;
use TextMessaging\Models;
use TextMessaging\Tests\Unit\Helpers;

class CRUDServiceTest extends TestCase
{
    /**
     * @var TextMessaging\Services\CRUDService
     */
    private $crudService;

    /**
     * @var TextMessaging\Tests\Unit\CustomerHelper
     */
    private $customerHelper;
    
    /**
     * @var TextMessaging\Tests\Unit\MessageHelper
     */
    private $messageHelper;

    /**
     * @var TextMessaging\Models\Customer
     */
    private $customerMock;

    /**
     * @var TextMessaging\Models\Message
     */
    private $messageMock;

    /**
     * @var TextMessaging\Models\TextMessage
     */
    private $textMock;

    /**
     * @var Illuminate\Database\Eloquent\Builder
     */
    private $builderMock;

    /**
     * @var Illuminate\Database\Eloquent\Collection
     */
    private $collectionMock;

    /**
     * @var Monolog\Logger
     */
    private $logger;

    /**
     * Test Setup method
     */
    protected function setUp()
    {
        $this->customerHelper = new Helpers\CustomerHelper();
        $this->messageHelper = new Helpers\MessageHelper();

        $this->customerMock = $this->getMockBuilder('TextMessaging\Models\Customer')
            ->setMethods(['save', 'delete', 'find', 'newQuery', 'load'])
            ->getMock();

        $this->logger = $this->getMockBuilder('Monolog\Logger')
            ->setMethods(['info', 'warning', 'error', 'debug', 'alert', 'emergency'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageMock = $this->getMockBuilder('TextMessaging\Models\Message')
            ->setMethods(['save', 'delete', 'find', 'newQuery'])
            ->getMock();

        $this->textMock = $this->getMockBuilder('TextMessaging\Models\TextMessage')
            ->setMethods(['save', 'delete', 'find', 'newQuery'])
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
        unset($this->customerHelper);
        unset($this->messageHelper);
        unset($this->customerMock);
        unset($this->messageMock);
        unset($this->textMock);
        unset($this->builderMock);
        unset($this->collectionMock);
        unset($this->logger);
    }

    /**
     * Test to ensure if a model is not found in the retrieve method
     * that a ModelNotFoundException is thrown
     *
     * @expectedException \TextMessaging\Exceptions\ModelNotFoundException
     * @group services
     * @group crud
     */
    public function testRetrieveModelNotFoundThrowsException()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->retrieve($this->customerMock, $id);
    }

    /**
     * Test to ensure if model throws query exception in retrieve, exception
     * will be caught and rethrown
     *
     * @expectedException \TextMessaging\Exceptions\ModelNotFoundException
     * @group services
     * @group crud
     */
    public function testRetrieveModelQueryExceptionRethrowsException()
    {
        $id = $this->messageHelper->getHash();
        $this->messageMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->retrieve($this->messageMock, $id);
    }

    /**
     * Test to ensure a retrieve call with no exceptions will return a full model
     *
     * @group services
     * @group crud
     */
    public function testSuccessfulRetrieveCallReturnsModel()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->id = $id;
        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $crudService = new CRUDService($this->logger);
        $response = $crudService->retrieve($this->customerMock, $id);

        $this->assertEquals($this->customerMock->id, $response->id);
        $this->assertEquals($this->customerMock->first_name, $response->first_name);
    }

    /**
     * Test to ensure a retrieve call with relationships returns successfully
     *
     * @group services
     * @group crud
     */
    public function testSuccessfulRetrievalWithRelationshipsReturnsModel()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->id = $id;
        $foo = new \Stdclass;
        $foo->id = $this->customerHelper->getHash();
        $foo->name = 'Foo 2';
        $foo_array = [$foo];
        $this->customerMock->foo = $foo_array;
        $this->customerMock->foo_id = $foo->id;

        $this->customerMock->expects($this->any())
            ->method('load')
            ->with($this->isType('string'))
            ->will($this->returnValue($this->customerMock));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $relationship = ['foo'];

        $crudService = new CRUDService($this->logger);
        $response = $crudService->retrieve($this->customerMock, $id, $relationship);
        $this->assertEquals($this->customerMock->id, $response->id);
    }

    /**
     * Test to ensure if the save method fails on create, throw a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testCreateMethodThrowsExceptionIfSaveFails()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $input = $this->customerHelper->modelAsArray($this->customerMock);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->create($this->customerMock, $input, $id);
    }

    /**
     * Test to ensure if model throws Query Exception it is caught and rethrown as a RuntimeException
     *
     * @expectedException \RuntimeException
     */
    public function testModelThrowingQueryExceptionRethrowsRuntimeException()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $input = $this->customerHelper->modelAsArray($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));


        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->create($this->customerMock, $input, $id);
    }

    /**
     * Test to ensure the create method returns the newly created model
     * on a successful save
     */
    public function testCreateMethodReturnsModelOnSuccess()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $input = $this->customerHelper->modelAsArray($this->customerMock);
        $this->customerMock->id = $id;
        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $crudService = new CRUDService($this->logger);
        $response = $crudService->create($this->customerMock, $input, $id);
        $this->assertEquals($response->id, $this->customerMock->id);
        $this->assertEquals($response->first_name, $this->customerMock->first_name);
    }

    /**
     * Test on Update to ensure a model not found exception is thrown if
     * the model cannot be found
     *
     * @expectedException \TextMessaging\Exceptions\ModelNotFoundException
     */
    public function testUpdateMethodThrowsModelNotFoundException()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $input = $this->customerHelper->modelAsArray($this->customerMock);
        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->update($this->customerMock, $input, $id);
    }

    /**
     * Test to ensure a runtime exception is thrown in the update method if
     * the save fails
     *
     * @expectedException \RuntimeException
     */
    public function testUpdateMethodThrowsRuntimeExceptionOnSaveFailure()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $input = $this->customerHelper->modelAsArray($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->update($this->customerMock, $input, $id);
    }

    /**
     * Test to ensure an update request where the model throws a query
     * exception is caught and rethrown as a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testUpdateMethodRethrowsExceptionOnSaveFailure()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $input = $this->customerHelper->modelAsArray($this->customerMock);
        $this->customerMock->id = $id;
        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->update($this->customerMock, $input, $id);
    }

    /**
     * Test to ensure the model is updated and returned on a successful update
     */
    public function testUpdateMethodReturnsModelOnSuccess()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->id = $id;
        $input = $this->customerHelper->modelAsArray($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $crudService = new CRUDService($this->logger);
        $response = $crudService->update($this->customerMock, $input, $id);
        $this->assertEquals($response->id, $this->customerMock->id);
        $this->assertEquals($response->first_name, $this->customerMock->first_name);
    }

    /**
     * Test to ensure delete method returns ModelNotFoundException if model
     * cannot be retrieved
     *
     * @expectedException \TextMessaging\Exceptions\ModelNotFoundException
     */
    public function testDeleteReturnsExceptionIfModelCannotBeFound()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue(null));

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->delete($this->customerMock, $id);
    }

    /**
     * Test ensure a RuntimeException is thrown if the delete method fails
     *
     * @expectedException \RuntimeException
     */
    public function testDeleteFailureThrowsRuntimeException()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->id = $id;

        $this->customerMock->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->delete($this->customerMock, $id);
    }

    /**
     * Test to ensure if delete throws a query exception, it will be 
     * caught and rethrown as a runtime exception
     *
     * @expectedException \RuntimeException
     */
    public function testDeleteFailureCatchsQueryExceptionThrowsAsRuntime()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);
        $this->customerMock->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new \Illuminate\Database\QueryException('test', [], new \Exception)));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->delete($this->customerMock, $id);
    }

    /**
     * Test to ensure true is returned if the delete succeeds
     */
    public function testDeleteReturnsTrueOnSuccess()
    {
        $id = $this->customerHelper->getHash();
        $this->customerMock = $this->customerHelper->getModel($this->customerMock);

        $this->customerMock->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->customerMock->expects($this->once())
            ->method('find')
            ->with($id)
            ->will($this->returnValue($this->customerMock));

        $crudService = new CRUDService($this->logger);
        $response = $crudService->delete($this->customerMock, $id);

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

        $this->customerMock->expects($this->once())
            ->method('newQuery')
            ->will($this->returnValue($this->builderMock));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->retrieveAll($this->customerMock);
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

        $this->customerMock->expects($this->once())
            ->method('newQuery')
            ->will($this->returnValue($this->builderMock));

        $crudService = new CRUDService($this->logger);
        $response = $crudService->retrieveAll($this->customerMock, $page, $per_page);

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
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->isType('array'))
            ->will($this->returnValue(null));

        $crudService = new CRUDService($this->logger);
        $crudService->retrieveAll($this->customerMock, $page, $per_page);
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
