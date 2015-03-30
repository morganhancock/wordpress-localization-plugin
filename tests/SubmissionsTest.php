<?php

use Psr\Log\LoggerInterface;
use Smartling\Bootstrap;
use Smartling\Helpers\WordpressContentTypeHelper;
use Smartling\Submissions\SubmissionEntity;
use Smartling\Submissions\SubmissionManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SubmissionsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var ContainerBuilder
	 */
	private $container;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @inheritdoc
	 */
	public function __construct ( $name = null, array $data = array (), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->container = Bootstrap::getContainer();

		$this->logger = $this->container->get( 'logger' );

		$bs = new Bootstrap();
		$bs->load();
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function setupDbAlMock () {
		$dbalMock = $this
			->getMockBuilder( 'Smartling\DbAl\SmartlingToCMSDatabaseAccessWrapper' )
			->setMethods(
				array (
					'query',
					'completeTableName',
					'getLastInsertedId',
					'fetch',
					'escape',
					'__construct'
				)
			)
			->setConstructorArgs(
				array (
					$this->container->get( 'logger' )
				)
			)
			->setMockClassName( 'MockDb' )
			->disableProxyingToOriginalMethods()
			->getMock();

		return $dbalMock;
	}

	/**
	 * @param string $id
	 */
	private function replaceDbAlInContainer ( $id = 'site.db' ) {
		$dbalMock = $this->setupDbAlMock();

		$this->container->set( 'site.db', $dbalMock );
	}

	public function testSubmissionEntityValidations () {
		$entity = new SubmissionEntity( $this->logger );

		$entity->setId( '100' );

		self::assertTrue( 100 === $entity->getId() );

		$entity->setApprovedStringCount( 0 );
		$entity->setCompletedStringCount( 100 );

		self::assertTrue( 0 === $entity->getCompletionPercentage() );

		$entity->setApprovedStringCount( 50 );
		$entity->setCompletedStringCount( 100 );

		self::assertTrue( 100 === $entity->getCompletionPercentage() );

		$entity->setApprovedStringCount( 100 );
		$entity->setCompletedStringCount( 30 );

		self::assertTrue( 30 === $entity->getCompletionPercentage() );

		$entity->setApprovedStringCount( 30 );
		$entity->setCompletedStringCount( 10 );

		self::assertTrue( 33 === $entity->getCompletionPercentage() );

	}

	public function testSubmissionStatusValidationAsValid () {
		$entity = new SubmissionEntity( $this->logger );
		$entity->setStatus( SubmissionEntity::SUBMISSION_STATUS_NEW );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSubmissionStatusValidationAsInvalid () {
		$entity = new SubmissionEntity( $this->logger );
		$entity->setStatus( 'ololo' );
	}

	public function testSubmissionContentTypeValidationAsValid () {
		$entity = new SubmissionEntity( $this->logger );
		$entity->setContentType( WordpressContentTypeHelper::CONTENT_TYPE_POST );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSubmissionContentTypeValidationAsInvalid () {
		$entity = new SubmissionEntity( $this->logger );
		$entity->setContentType( 'ololo' );
	}

	public function testSubmissionEntityCreation () {
		$this->replaceDbAlInContainer();

		/**
		 * @var SubmissionManager $manager
		 */
		$manager = $this->container->get( 'manager.submission' );

		$fields = array (
			'id'                   => null,
			'sourceTitle'          => 'Automatic generated title',
			'sourceBlog'           => 1,
			'sourceContentHash'    => md5( '' ),
			'contentType'          => WordpressContentTypeHelper::CONTENT_TYPE_POST,
			'sourceGUID'           => '/ol"olo',
			'fileUri'              => "/tralala'",
			'targetLocale'         => 'es_US',
			'targetBlog'           => 5,
			'targetGUID'           => '',
			'submitter'            => 'admin',
			'submissionDate'       => time(),
			'approvedStringCount'  => 37,
			'completedStringCount' => 14,
			'status'               => 'New',
		);

		$entity = $manager->createSubmission( $fields );

		$current_id = $entity->id;

		self::assertTrue( $current_id === null );
	}

	public function testEntitySavingToDatabase () {
		$this->replaceDbAlInContainer();

		/**
		 * @var SubmissionManager $manager
		 */
		$manager = $this->container->get( 'manager.submission' );

		$fields = array (
			'id'                   => null,
			'sourceTitle'          => 'Automatic generated title',
			'sourceBlog'           => 1,
			'sourceContentHash'    => md5( '' ),
			'contentType'          => WordpressContentTypeHelper::CONTENT_TYPE_POST,
			'sourceGUID'           => '/ol"olo',
			'fileUri'              => "/tralala'",
			'targetLocale'         => 'es_US',
			'targetBlog'           => 5,
			'targetGUID'           => '',
			'submitter'            => 'admin',
			'submissionDate'       => time(),
			'approvedStringCount'  => 37,
			'completedStringCount' => 14,
			'status'               => 'New',
		);

		$entity = $manager->createSubmission( $fields );

		/**
		 * @var PHPUnit_Framework_MockObject_MockObject $mock
		 */
		$mock = $this->container->get( 'site.db' );

		$mock->expects( self::at( 0 ) )->method( 'completeTableName' )->willReturn( 'wp_mock_table_name' );
		$mock->expects( self::at( 1 ) )->method( 'query' )->willReturn( true );
		$mock->expects( self::at( 2 ) )->method( 'getLastInsertedId' )->willReturn( 88 );

		$newEntity = $manager->storeEntity( $entity );

		$new_id = $newEntity->id;

		self::assertTrue( $new_id === 88 );
	}

	public function testEntityReadFromDatabase () {
		$this->replaceDbAlInContainer();

		/**
		 * @var SubmissionManager $manager
		 */
		$manager = $this->container->get( 'manager.submission' );

		$fields = array (
			array (
				'id'                   => null,
				'sourceTitle'          => 'Automatic generated title',
				'sourceBlog'           => 1,
				'sourceContentHash'    => md5( '' ),
				'contentType'          => WordpressContentTypeHelper::CONTENT_TYPE_POST,
				'sourceGUID'           => '/ol"olo',
				'fileUri'              => "/tralala'",
				'targetLocale'         => 'es_US',
				'targetBlog'           => 5,
				'targetGUID'           => '',
				'submitter'            => 'admin',
				'submissionDate'       => time(),
				'approvedStringCount'  => 37,
				'completedStringCount' => 14,
				'status'               => 'New',
			)
		);

		/**
		 * @var PHPUnit_Framework_MockObject_MockObject $mock
		 */
		$mock = $this->container->get( 'site.db' );

		$countResponse = array (
			( (object) array ( 'cnt' => 1 ) ),
		);

		$mock->expects( $this->at( 0 ) )->method( 'completeTableName' )->willReturn( 'wp_mock_table_name' );
		$mock->expects( $this->at( 3 ) )->method( 'fetch' )->willReturn( $fields ); // select
		$mock->expects( $this->at( 1 ) )->method( 'completeTableName' )->willReturn( 'wp_mock_table_name' );
		$mock->expects( $this->at( 2 ) )->method( 'fetch' )->willReturn( $countResponse );       // count

		$total = 0;

		// read all from database
		$entities = $manager->getEntities( null, null, array (), null, $total );

		$expected = 1 === $total && ( $entities[0] instanceof SubmissionEntity );

		self::assertTrue( $expected );
	}

}