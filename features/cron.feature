Feature: WP Cron
  In order to keep the database clean
  as a site owner
  Revision Strike should clean up old revisions daily

  Background:
    Given a WP install
    When I run `wp plugin activate revision-strike`
    Then STDOUT should be:
      """
      Success: Plugin 'revision-strike' activated.
      """

  Scenario: Cron job is registered
    When I run `wp cron event list --fields=hook,recurrence --format=csv`
    Then STDOUT should contain:
      """
      revisionstrike_strike_old_revisions,"1 day"
      """

  Scenario: Daily cron running
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

    When I run `wp cron event run revisionstrike_strike_old_revisions`
    Then STDOUT should contain:
      """
      Success: Executed the cron event 'revisionstrike_strike_old_revisions'
      """

    When I run `wp post list --post_type=revision --format=count`
    Then STDOUT should be:
      """
      0
      """