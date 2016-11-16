=== Media Organiser ===
Contributors: ChrisDennis 
Donate link: http://fbcs.co.uk/
Tags: media,file,manager,explorer,relocate,folder,folders,files,rename,make directory,directories,organize,organizer,select,selector,database
Requires at least: 4.3.0
Tested up to: 4.6.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows media items (i.e. attachments) to be organised into folders.

== Description ==

You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.


Media Organiser was forked from version 1.4.2 of Media File Manager by Atsushi Ueda.

Since the fork, various changes have been made including:

* restructuring and simplifying the code
* better error handling
* dialogs instead of alerts
* no logging to custom table
* etc.

== Requirements ==

* jQuery
* Includes jQuery UI -- just the Dialog and ProgressBar modules.
* HTML5 (for data attributes -- used in old media-selector, not by me)

== Known issues ==

* may not work on Windows servers -- haven't checked the use of directory separators yet
* something
* no internationalization (yet)

Icons adapted from https://github.com/iconic/open-iconic

== Installation ==

Install the plugin like usual ones. Then activate it.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 0.1.0

* Media Organiser admin page largely reworked.  Media Selector section is not yet done.

= 0.0.9

* Forked from Media File Manager 1.4.2
