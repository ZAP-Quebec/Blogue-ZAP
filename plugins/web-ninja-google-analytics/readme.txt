=== Web Ninja Google Analytics ===
Contributors: Josh Fowler
Donate link: http://josh-fowler.com/
Tags: google analytics, google, statistics, stats, tracking, dashboard, chart, graph
Requires at least: 2.8.0
Tested up to: 3.0.3
Stable tag: 1.0.3

Enable Google Analytics on all of your pages instantly and add Google Analytics Stats to your Admin Dashboard and Posts.

== Description ==

The Web Ninja Google Analytics Plugin is the one stop shop for all your Google Analytic needs. It not only allows you to add Google Analytics JavaScript to each page on your site without making any changes to your template, but it also adds an Admin Dashboard Widget with Analytic Stats. Plus, not only do you see the over all stats on the Admin Dashboard but you can see individual post and page stats in the Post and Pages Admin sections.

This plugin is highly configurable. With it you can add tracking to outbound links, download, and mailto links as well as see your stats from the past 7 days, 30 days, 60 days, and even 90 days. 

Read through the list of all the features below to get a feeling of what this plugin can do. You can enable and disable all features individually, although the default configuration will suffice for most of the users

*Features*

* When adding the JavaScript tracker code to a page, put it at the end of the body. There are quite a few WordPress plugins for Google Analytics out there. Most of them include the JavaScript in the head section. This can delay the loading of your page and is not advised by Google.
* When using a WordPress theme that does not invoke the wp_footer hook as it is supposed to do, the JavaScript tracker code will be added to the head section. This can delay the loading of your page. The only way to prevent this, is to have the theme author implement the correct plugin calls, fix the theme yourself or start using another theme.
* Add the JavaScript tracker code to the admin pages if you want to track those as well. (switched off with the default settings)
* Automatic check-for-updates to warn you (on the UGA Options page) if your version of Web Ninja Google Analytics is out of date.
* Does not add the tracker code to the pages when a logged on user of a configurable userlevel requests a page. This can be used to ignore your own page views and not skew your statistics. (Default configuration ignores page hits from users level 8 and up)
* Add tracking to out links. You can also specify hostnames which should be considered internal (e.g. www.example.com, example.com and example.org). Links to these hostnames will be considered internal and the tracking event will not be added to those links. You can also specify the prefix to append to the link when sending it to Google Analytics so your outbound links will be logged to a logical directory structure. This way, you will be able to easily identify what pages visitors clicked on to leave your site. (The default configuration is to check outgoing links in the /out/ directory at Google Analytics)
* Add tracking to download links. You can specify which file extensions should be considered downloads. Only internal links to these filetypes will be tracked. Internal links are either relative links (without a hostname) or links to the hostnames you defined as internal. You can also specify the prefix to append to the link when sending it to Google Analytics so your download links will be logged to a logical directory structure. This way, you will be able to easily identify what files your visitors downloaded. (The default configuration contains a list of common file extensions to be marked as downloads. These are tracked in the /download/ directory at Google Analytics by default)
* Add tracking to mailto links. You can also specify the prefix to append to the link when sending it to Google Analytics so your mailto links will be logged to a logical directory structure. This way, you will be able to easily identify what mailto links your visitors clicked. (The default configuration is to track mailto links in the /mailto/ directory at Google Anaytics)
* Specify if the outgoing, download and mailto links should be tracked in the postings only, the comments, the comment author URL or any combination of these three.
* Adds a Dashboard Widget to your Admin Dashboard for easy access to your Google Analytic stats.
* Configure which users can view the Dashboard Widget but their user level.
* Shows basic stats such as Pageviews, Visits, Pages/Visit, Bounce Rate, Avg. Time on Site, and New Visits % as well as detailed stats such as Top Posts (with Pageviews), Top Searches, and Top Referers.
* Added support for Goal stat tracking.
* View individual basic stats on each post quickly in the Post admin section.
* Embed Pageviews over the past 30 days in the post with a shortcode. (Either by graph or by text)

Check out http://josh-fowler.com for more plugins.

== Installation ==

Installing is as simple as downloading the file from this site, placing it in your wp-content/plugins directory and activating the plugin. For the more detailed instructions read on.

1. Get a Google Analytics account at http://analytics.google.com.
2. Download the Web Ninja Google Analytics ZIP file
3. Extract the zipfile and place the PHP file in the wp-content/plugins directory of your WordPress installation
4. Go to the administration page of your WordPress installation (normally at http://www.yourblog.com/wp-admin)
5. Click on the Plugins tab and search for Web Ninja Google Analytics in the list
6. Activate the Web Ninja Google Analytics plugin
7. You can now find an Web Ninja GA page under Options to set the options of the plug-in
8. Be sure to enter in the UA-XXXXXXXX-X number provided by Google Analytics into the plugin or it will not work.
9. Wait until Google Analytics updates your reports. Currently it seems like this can take up to 24 hours.
10. To activate your Dashboard Widget, in the options screen enter your log in and password that you would normally use for your Google Analytics account.
11. Once logged in you may select the Account that the stats will be taken from. (The Dashboard Widget will not appear till you have save the Account option at least once)

Please note that SimpleXML (http://us3.php.net/manual/en/book.simplexml.php) is needed for the stats part of this plugin. It is enabled by default in PHP version 5 but some hosting environments may have it turned off. The plugin will alert you if SimpleXML is not available. You may be able to contact your hosting company to enable this for you. Also, if it is not available the Javascipt insertion of the Google Analytics code will still work. Only your stats will be unavailable through the Dashboard and Post Section.

== Screenshots ==

1. Screenshot of the Admin Dashboard Widget.
2. Screenshot of the Post Admin Section stats.
3. Screenshot of the Plugin Options Screen.


== Frequently Asked Questions ==

= Where can I get support for this plugin? =

Support can be found here: http://josh-fowler.com/forum/

I try to watch this often and can answer any problems you have when I have time.

== Change Log ==

= 1.0.3 =

* Fixed a problem where some people couldn't click the "Show" link on the Detailed Stats on the Admin Dashboard Widget
* Added a few more debugging lines in the code for easier debugging on anyone that has problems
* Added a Facebook "Like" button in the options. By clicking it you will get all of the latest updates by me to your Facebook News Feed

= 1.0.2 =

* Fixed a few grammar errors
* Added an option to change how many days are shown in the Post Admin Section

= 1.0.1 =

* Frist Release

== Usage ==

= Shortcode =

To embed Google Analytics data into a post use the following syntax: [wnga: option]. 

Examples:

The following will be replaced by the number of pageviews for the current page or post over the past 30 days when embedded in that page or post:

[wnga] 

The following will be replaced by a sparkline that represents the number of pageviews for the current page or post over the past 30 days when embedded in that page or post:

[wnga: sparkline]