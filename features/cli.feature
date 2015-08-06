Feature: WP-CLI
	In order to remove old revisions
	as a site owner
	I should be able to use WP-CLI

	Background:
		Given a WP install
		When I run `wp plugin activate revision-strike`
    Then STDOUT should be:
    	"""
			Success: Plugin 'revision-strike' activated.
    	"""

	Scenario: Removing old revisions with defaults
		When I run `wp post create --post_title='Test post' --post_status=publish --post_date='2015-01-01 00:00:00' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content=Updated`
    And I run `wp post update {POST_ID} --post_content='Update a second time'`
    Then STDOUT should be:
    	"""
			Success: Updated post {POST_ID}.
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			2
    	"""

    When I run `wp revision-strike clean`
    Then STDOUT should contain:
    	"""
			Success: 2 post revisions were deleted successfully
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			0
    	"""

	Scenario: Removing old revisions with limits
		When I run `wp post create --post_title='Test post' --post_status=publish --post_date='2015-01-01 00:00:00' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content=Updated`
    And I run `wp post update {POST_ID} --post_content='Update a second time'`
    Then STDOUT should be:
    	"""
			Success: Updated post {POST_ID}.
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			2
    	"""

    When I run `wp revision-strike clean --limit=1`
    Then STDOUT should contain:
    	"""
			Success: One post revision was deleted successfully
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			1
    	"""

	Scenario: Revisions exist, but post dates are too recent
		When I run `wp post create --post_title='Test post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post update {POST_ID} --post_content=Updated`
    Then STDOUT should be:
    	"""
			Success: Updated post {POST_ID}.
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			1
    	"""

    When I run `wp revision-strike clean`
    Then STDOUT should contain:
    	"""
			Success: No errors occurred, but no post revisions were removed.
    	"""

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
    	"""
			1
    	"""