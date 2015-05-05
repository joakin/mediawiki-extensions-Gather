<?php

class GatherTestCase extends ApiTestCase {
	private static $gatherUsers = array();

	public function setUp() {
		parent::setUp();
		if ( !array_key_exists( 'gatherUser', self::$gatherUsers ) ) {
			self::$gatherUsers['gatherUser'] = new TestUser( 'gatherUser' );
		}
		static::$users = array_merge( static::$users, self::$gatherUsers );
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
		$user, array $pages = array(), $label = 'New list', array $properties = array()
	) {
		if ( is_string( $user ) ) {
			$user = static::$users[$user]->getUser();
		}

		$tokens = $this->getFromResults( $this->doApiRequest( array(
			'action' => 'query',
			'meta' => 'tokens',
			'type' => 'watch',
		), null, false, $user ), 'tokens' );

		$params = array_merge( array(
			'action' => 'editlist',
			'token' => $tokens['watchtoken'],
			'label' => $label,
			'titles' => implode( '|', $pages ),
		), $properties );
		$result = $this->getFromResults( $this->doApiRequest( $params, null, false, $user ), 'editlist' );
		$this->assertEquals( 'created', $result['status'] );
		return $result['id'];
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
			case 'listpages':
				return $fullResults['query'][$module];
			case 'editlist':
				return $fullResults[$module];
			default:
				var_dump( $fullResults );
				throw new LogicException( 'getResults() is not implemented for module ' . $module );
		}
	}

	protected function debug( $enable = true ) {
		global $wgDebugLogFile;
		$wgDebugLogFile = $enable ? '/vagrant/logs/mediawiki-phpunit-debug.log' : null;
	}
}
