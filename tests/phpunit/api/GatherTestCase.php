<?php

class GatherTestCase extends ApiTestCase {
	private static $gatherUsers = array();
	private static $listCount = 0;

	public function setUp() {
		parent::setUp();
		static::$users = array_merge( static::$users, self::$gatherUsers );
		$this->addTestUsers( 'gatherUser', 'loggedInUser' );
	}

	/**
	 * @param string $name Username of the test user. Additional usernames can be passed as
	 *   additional arguments.
	 */
	protected function addTestUsers( $name /* ... */ ) {
		// This is a bit overcomplicated but I ran into trouble whenever I tried to implement it
		// in saner ways. $users is reset in setUp() but the user DB does not so simply extending
		// $users would result in a duplicate key error.
		foreach ( func_get_args() as $name ) {
			if ( !array_key_exists( $name, self::$gatherUsers ) ) {
				if ( array_key_exists( $name, static::$users ) ) {
					throw new Exception( 'reserved username: ' . $name );
				}
				static::$users[$name] = self::$gatherUsers[$name] = new TestUser( $name );
			}
		}
	}

	protected function doApiRequestWithWatchToken(
		array $params, array $session = null, $appendModule = false, User $user = null
	) {
		$tokens = $this->getFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'meta' => 'tokens',
			'type' => 'watch',
		), null, false, $user ), 'tokens' );

		$params = array_merge( $params, array( 'token' => $tokens['watchtoken'] ) );
		return $this->doApiRequest( $params, $session, $appendModule, $user );
	}

	/**
	 * Creates a new list.
	 * @param User|string $user User who will own the list (as User object or $users index)
	 * @param array $pages List of pages to include (as title strings)
	 * @param string $label List label
	 * @param array $properties API params for other properties
	 * @return int List ID
	 */
	protected function createList(
		$user, array $pages = array(), $label = null, array $properties = array()
	) {
		if ( is_string( $user ) ) {
			$user = static::$users[$user]->getUser();
		}
		self::$listCount += 1;
		if ( $label === null ) { // labels must be unique
			$label = 'New list ' . self::$listCount;
		}

		$params = array_merge( array(
			'action' => 'editlist',
			'label' => $label,
			'perm' => 'public',
			'titles' => implode( '|', $pages ),
		), $properties );
		$result = $this->getFromResults( $this->doApiRequestWithWatchToken( $params, null, false,
			$user ), 'editlist' );
		$this->assertEquals( 'created', $result['status'] );
		return $result['id'];
	}

	/**
	 * @param User|string $user Flagging user (as User object or $users index)
	 * @param int $listId
	 */
	protected function flagList( $user, $listId ) {
		if ( is_string( $user ) ) {
			$user = static::$users[$user]->getUser();
		}
		$params = array(
			'action' => 'editlist',
			'mode' => 'flag',
			'id' => $listId,
		);
		$result = $this->getFromResults( $this->doApiRequestWithWatchToken( $params, null, false,
			$user ), 'editlist' );
		$this->assertEquals( 'flagged', $result['status'] );
	}

	/**
	 * @param int $listId
	 * @param string $mode editlist API mode parameter
	 */
	protected function setListPermissionOverride( $listId, $mode ) {
		$user = static::$users['sysop']->getUser();
		$params = array(
			'action' => 'editlist',
			'mode' => $mode,
			'id' => $listId,
		);
		$result = $this->getFromResults( $this->doApiRequestWithWatchToken( $params, null, false,
			$user ), 'editlist' );
		$this->assertContains( $result['status'], array( 'updated', 'unchanged' ) );
	}

	/**
	 * Gets the interesting part of an API response.
	 * @param array $ret Return value of doApiRequest()
	 * @param string $module Name of the API module (or query submodule) that was called
	 * @return array
	 */
	protected function getFromResults( $ret, $module ) {
		$fullResults = $ret[0];
		switch ( $module ) {
			case 'tokens':
			case 'lists':
			case 'listpages':
				return $fullResults['query'][$module];
			case 'editlist':
				return $fullResults[$module];
			default:
				var_dump( $fullResults );
				throw new LogicException( 'getResults() is not implemented for module ' . $module );
		}
	}

	protected function getListData( $listId, $user = null ) {
		$lists = $this->getFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'list' => 'lists',
			'lstid' => $listId,
			'lstprop' => 'label|description|public|review|image|count|updated|owner',
		), null, false, $user ), 'lists' );
		$this->assertNotEmpty( $lists, "List $listId not found" );
		return $lists[0];
	}

	protected function debug( $enable = true ) {
		global $wgDebugLogFile;
		$wgDebugLogFile = $enable ? '/vagrant/logs/mediawiki-phpunit-debug.log' : null;
	}

	/**
	 * Asserts that an API request failed (e.g. due to lack of permissions)
	 * @param array $params Request parameters
	 * @param User $user
	 * @param bool $withWatchToken Set to true if the API request requires a watch token.
	 */
	protected function assertRequestFails( $params, $user = null, $withWatchToken = false ) {
		try {
			if ( $withWatchToken ) {
				$this->doApiRequestWithWatchToken( $params, null, false, $user );
			} else {
				$this->doApiRequest( $params, null, false, $user );
			}
		} catch ( UsageException $e ) {
			$this->assertTrue( true );
			return;
		}
		$this->fail( 'Failed to assert that API request fails: '
			. 'user: ' . ( $user ? $user->getName() : 'anon' ) . ', '
			. 'params: ' . json_encode( $params )
		);
	}
}
