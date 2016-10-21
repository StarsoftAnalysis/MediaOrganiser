=== Plugin Name ===
Contributors: aueda
Donate link: http://tempspace.net/plugins/
Tags: media,file,manager,explorer,relocate,folder,folders,files,rename,make directory,directories,organize,organizer,select,selector,database
Requires at least: 4.3.0
Tested up to: 4.5.3
Stable tag: 1.4.2

You can make sub-directories in the upload directory, and move files into them.

== Description ==

You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.

You can view some screenshots, documentation, known issues in the following URL:
http://tempspace.net/plugins/?page_id=111

Before try this plugin, I recommend you to backup your contents(files, database) and test this plugin enough. I have tested this plugin with Apache(Liunx/Windows) and IIS Express 7.5. If you cannot get working this plugin, please let me know.

(usage of file manager)

*To open the file manager, click the "Media File Manager" link in the Media tab in the admin screen.

*To change directory, click an yellow folder icon.

*To move items, check the items you'll move. Then, click an arrow icon which points the destination.

*To make a directory, click an yellow [+] button. Then enter the name of the directory.

*To rename, right-click the icon and select 'Rename'. Then enter the new name.

*To delete an empty folder, right-click the icon and select 'Delete'. You cannot delete files or non-empty folders. To delete them, use the built-in media manager of Wordpress.

*To preview an item, right-click the icon of it, and select 'Preview'.

(usage of file selector)

*To open the media selector, click an yellow folder icon with 'M' letter in the post/page editor screen.

*Then, list of media files opens. At first, all media files appears in the list. You can select directory, media type by selecting the pull-down menu at the top of the dialog.

*Click an icon of media file you'll insert. Then the media insert dialog similar as usual appears. Edit information of the media and click insert button.

== Installation ==

Install the plugin like usual ones. Then activate it.

== Frequently Asked Questions ==

.

== Screenshots ==

1. Media File Manager.

== Changelog ==

= 1.4.2 =
* Fixed security bugs, etc.

= 1.4.1 =
* Fixed security bugs.

= 1.4.0 =
* Added an option of disabling set_time_limit().
* Added [Select All]/[Deselect All] buttons.
* Fixed some errors.
* Fixed security bug.

= 1.3.1 =
* Fixed some errors.
* improved stability.

= 1.3.0 =
* Added a permission setting for file selector feature.
* Corrected background color.
* Fixed some errors.

= 1.2.1 =
* Fixed some errors.

= 1.2.0 =
* Added a feature of deleting empty directories.
* Solve the problem that thumbnails of files other than images, audio or movies are broken in the media selector.
* Improve styling

= 1.1.1 =
* Solved the problem that unnecessary code was running when loading posts or pages.
* Solved the problem that some buttons in the media-selector did not working.
* Solved the problem that this plugin does not work with PHP5.4.

= 1.1.0 =
* Added user access control by role.

= 1.0.2 =
* Solved the problem that file manager shows root directory of your server when the upload directory is not writeable by the server.
* Solved the problem that when moving items, items with same names in the destination directory are overwritten.

= 1.0.1 =
* Solved the problem that the plugin does not work under some kind of environment/setting (Especially windows servers).
* Solved the problem that URLs of medium/large images in posts/pages are not replaced.
* Solved the problem that when a file is renamed to an existing name, the existing file is deleted.
* Solved the problem that media selector does not work in the QuickPress.

= 1.0.0 =
* Solved installation problem.
* several bug fixes.

= 0.8.0 =
* Initial release.
