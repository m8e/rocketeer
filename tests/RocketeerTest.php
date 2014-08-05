<?php
namespace Rocketeer;

use Rocketeer\TestCases\RocketeerTestCase;

class RocketeerTest extends RocketeerTestCase
{
	////////////////////////////////////////////////////////////////////
	//////////////////////////////// TESTS /////////////////////////////
	////////////////////////////////////////////////////////////////////

	public function testCanGetAvailableConnections()
	{
		$connections = $this->app['rocketeer.connections']->getAvailableConnections();
		$this->assertEquals(array('production', 'staging'), array_keys($connections));

		$this->app['rocketeer.server']->setValue('connections.custom.username', 'foobar');
		$connections = $this->app['rocketeer.connections']->getAvailableConnections();
		$this->assertEquals(array('production', 'staging', 'custom'), array_keys($connections));
	}

	public function testCanGetCurrentConnection()
	{
		$this->swapConfig(array('rocketeer::default' => 'foobar'));
		$this->assertEquals('production', $this->app['rocketeer.connections']->getConnection());

		$this->swapConfig(array('rocketeer::default' => 'production'));
		$this->assertEquals('production', $this->app['rocketeer.connections']->getConnection());

		$this->swapConfig(array('rocketeer::default' => 'staging'));
		$this->assertEquals('staging', $this->app['rocketeer.connections']->getConnection());
	}

	public function testCanChangeConnection()
	{
		$this->assertEquals('production', $this->app['rocketeer.connections']->getConnection());

		$this->app['rocketeer.connections']->setConnection('staging');
		$this->assertEquals('staging', $this->app['rocketeer.connections']->getConnection());

		$this->app['rocketeer.connections']->setConnections('staging,production');
		$this->assertEquals(array('staging', 'production'), $this->app['rocketeer.connections']->getConnections());
	}

	public function testCanUseSshRepository()
	{
		$repository = 'git@github.com:'.$this->repository;
		$this->expectRepositoryConfig($repository, '', '');

		$this->assertEquals($repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanUseHttpsRepository()
	{
		$this->expectRepositoryConfig('https://github.com/'.$this->repository, 'foobar', 'bar');

		$this->assertEquals('https://foobar:bar@github.com/'.$this->repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanUseHttpsRepositoryWithUsernameProvided()
	{
		$this->expectRepositoryConfig('https://foobar@github.com/'.$this->repository, 'foobar', 'bar');

		$this->assertEquals('https://foobar:bar@github.com/'.$this->repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanUseHttpsRepositoryWithOnlyUsernameProvided()
	{
		$this->expectRepositoryConfig('https://foobar@github.com/'.$this->repository, 'foobar', '');

		$this->assertEquals('https://foobar@github.com/'.$this->repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanCleanupProvidedRepositoryFromCredentials()
	{
		$this->expectRepositoryConfig('https://foobar@github.com/'.$this->repository, 'Anahkiasen', '');

		$this->assertEquals('https://Anahkiasen@github.com/'.$this->repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanUseHttpsRepositoryWithoutCredentials()
	{
		$this->expectRepositoryConfig('https://github.com/'.$this->repository, '', '');

		$this->assertEquals('https://github.com/'.$this->repository, $this->app['rocketeer.connections']->getRepository());
	}

	public function testCanCheckIfRepositoryNeedsCredentials()
	{
		$this->expectRepositoryConfig('https://github.com/'.$this->repository, '', '');
		$this->assertTrue($this->app['rocketeer.connections']->needsCredentials());
	}

	public function testCangetRepositoryBranch()
	{
		$this->assertEquals('master', $this->app['rocketeer.connections']->getRepositoryBranch());
	}

	public function testCanGetApplicationName()
	{
		$this->assertEquals('foobar', $this->app['rocketeer.rocketeer']->getApplicationName());
	}

	public function testCanGetHomeFolder()
	{
		$this->assertEquals($this->server.'', $this->app['rocketeer.rocketeer']->getHomeFolder());
	}

	public function testCanGetFolderWithStage()
	{
		$this->app['rocketeer.connections']->setStage('test');

		$this->assertEquals($this->server.'/test/current', $this->app['rocketeer.rocketeer']->getFolder('current'));
	}

	public function testCanGetAnyFolder()
	{
		$this->assertEquals($this->server.'/current', $this->app['rocketeer.rocketeer']->getFolder('current'));
	}

	public function testCanReplacePatternsInFolders()
	{
		$folder = $this->app['rocketeer.rocketeer']->getFolder('{path.storage}');

		$this->assertEquals($this->server.'/app/storage', $folder);
	}

	public function testCannotReplaceUnexistingPatternsInFolders()
	{
		$folder = $this->app['rocketeer.rocketeer']->getFolder('{path.foobar}');

		$this->assertEquals($this->server.'/', $folder);
	}

	public function testCanUseRecursiveStageConfiguration()
	{
		$this->swapConfig(array(
			'rocketeer::scm.branch'                   => 'master',
			'rocketeer::on.stages.staging.scm.branch' => 'staging',
		));

		$this->assertEquals('master', $this->app['rocketeer.rocketeer']->getOption('scm.branch'));
		$this->app['rocketeer.connections']->setStage('staging');
		$this->assertEquals('staging', $this->app['rocketeer.rocketeer']->getOption('scm.branch'));
	}

	public function testCanUseRecursiveConnectionConfiguration()
	{
		$this->swapConfig(array(
			'rocketeer::default'                           => 'production',
			'rocketeer::scm.branch'                        => 'master',
			'rocketeer::on.connections.staging.scm.branch' => 'staging',
		));
		$this->assertEquals('master', $this->app['rocketeer.rocketeer']->getOption('scm.branch'));

		$this->swapConfig(array(
			'rocketeer::default'                           => 'staging',
			'rocketeer::scm.branch'                        => 'master',
			'rocketeer::on.connections.staging.scm.branch' => 'staging',
		));
		$this->assertEquals('staging', $this->app['rocketeer.rocketeer']->getOption('scm.branch'));
	}

	public function testRocketeerCanGuessWhichStageHesIn()
	{
		$path = '/home/www/foobar/production/releases/12345678901234/app';
		$stage = Rocketeer::getDetectedStage('foobar', $path);
		$this->assertEquals('production', $stage);

		$path = '/home/www/foobar/staging/releases/12345678901234/app';
		$stage = Rocketeer::getDetectedStage('foobar', $path);
		$this->assertEquals('staging', $stage);

		$path = '/home/www/foobar/releases/12345678901234/app';
		$stage = Rocketeer::getDetectedStage('foobar', $path);
		$this->assertEquals(false, $stage);
	}

	public function testFillsConnectionCredentialsHoles()
	{
		$connections = $this->app['rocketeer.connections']->getAvailableConnections();
		$this->assertArrayHasKey('production', $connections);

		$this->app['rocketeer.server']->setValue('connections', array(
			'staging' => array(
				'host'      => 'foobar',
				'username'  => 'user',
				'password'  => '',
				'keyphrase' => '',
				'key'       => '/Users/user/.ssh/id_rsa',
				'agent'     => ''
			),
		));
		$connections = $this->app['rocketeer.connections']->getAvailableConnections();
		$this->assertArrayHasKey('production', $connections);
	}

	////////////////////////////////////////////////////////////////////
	//////////////////////////////// HELPERS ///////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Make the config return specific SCM config
	 *
	 * @param  string $repository
	 * @param  string $username
	 * @param  string $password
	 *
	 * @return void
	 */
	protected function expectRepositoryConfig($repository, $username, $password)
	{
		$this->swapConfig(array(
			'rocketeer::scm' => array(
				'repository' => $repository,
				'username'   => $username,
				'password'   => $password,
			),
		));
	}
}
