=== Media Organiser ===
Contributors: ChrisDennis 
Donate link: http://fbcs.co.uk/mocd-donation/
Tags: media,file,manager,explorer,relocate,folder,folders,files,rename,make directory,directories,organize,organise,organizer,organiser,select,selector
Requires at least: 4.3.0
Tested up to: 5.0.1
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows media items (i.e. attachments) to be organised into folders.  

== Description ==

This plugin allows attachments to be organised into folders on the server.

Attachments are physically moved into folders, and all references to them in pages and posts are updated accordingly.

Media Organiser was originally forked from version 1.4.2 of Media File Manager by Atsushi Ueda, and has been largely rewritten since then.  It does not yet have the 'media selector' function of Media File Manager -- in fact, the 'new' interface to choosing media is so poorly documented that I can't work out how to use it.  Any ideas?

== Requirements ==

* MySQL database engine that does transactions, otherwise things will get out of whack of something goes wrong when renaming files.  Information on changing engines can be found on [easyengine.io](https://easyengine.io/tutorials/mysql/myisam-to-innodb/). The tables that are affected are `wp_posts` and `wp_postmeta`.


== Acknowledgements ==

Icons adapted from [github.com/iconic/open-iconic/](https://github.com/iconic/open-iconic).

== Known issues ==

* May not work on sites hosted on a Windows server -- haven't checked the use of directory separators yet.
* Does database transactions -- so requires a database engine that can do commit/rollback, such as InnoDB.
* Does its best to make changes atomic, but things could go wrong if something changes in the middle of renaming files.
* No internationalization (yet).
* Doesn't check if posts are already locked for editing before making changes to them, so it's best to move and rename attachments when you're sure that no-one else is working on any posts or pages that use those attachments.

== Installation ==

Install the plugin in the usual way and activate it.

Administrators should get permission to use the plug-in automatically.  Go to Settings / Media Organiser to give other roles permission to use it if required.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 0.1.4 =

* Tested with WordPress 5.0.1.

= 0.1.3 =

* Tested with WordPress 4.9.1.

= 0.1.2 =

* Added code to cope with duplicated secondary files.
* Documented requirement for transaction-capable database engine.
* Automatically grant capabilities to administrators.
* Better CSS for small screens.

= 0.1.1 =

* Disable checkboxes if moving is not possible (i.e. name clash)
* Improvements to CSS and HTML
* Tweaks to documentation. 

= 0.1.0 =

* Media Organiser admin page largely reworked.  Media Selector section is not yet done.
* Meets current WordPress standards: uploaded to WordPress Plugin Directory.

= 0.0.9 =

* Forked from Media File Manager 1.4.2
