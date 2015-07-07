# Revision Strike

[![Build Status](https://travis-ci.org/stevegrunwell/revision-strike.png)](https://travis-ci.org/stevegrunwell/revision-strike)

Unless post revisions are explicitly limited, WordPress will build up a hefty sum of revisions over time. While it's great to have revision history for some recent content, the chances that old revisions will be necessary diminish the longer a post has been published. Revision Strike is designed to automatically remove these unneeded revisions on older, published posts.

**How does it work?**

First, a threshold is set, with a default of 30 days. Once a day, Revision Strike will run and find any post revisions in the database attached to **published** posts with a post date of at least 30 (or your custom threshold) days ago, and "strike" (tear-down and remove) them from the WordPress database.


## Usage

There are a number of ways to interact with Revision Strike:


### WP Cron

Upon plugin activation, a hook is registered to trigger the `revisionstrike_strike_old_revisions` action daily, which kicks off the striking process. This hook is then automatically removed upon plugin deactivation.


### WP-CLI

If you make use of [WP-CLI](http://wp-cli.org/) on your site you may trigger Revision Strike with the following command:

```
$ wp revision-strike clean
```

#### Arguments

<dl>
	<dt>--days=&lt;days&gt;</dt>
	<dd>Remove revisions on posts published at least &lt;days&gt; day(s) ago.</dd>
	<dt>--verbose</dt>
	<dd>Enable verbose logging of deleted revisions.</dd>
</dl>


## Releases

### 0.1

Initial public release.