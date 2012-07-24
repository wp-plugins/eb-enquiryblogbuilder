=== Enquiry Blog Builder ===
Contributors: EnquiryBlogger
Tags: education, school, open university, widget, plugin, wpmu, elli, enquiryblogger
Requires at least: 3.0.0
Tested up to: 3.4.1
Stable tag: 1.1

A suit of plugins to help students enquire into a subject and teachers to manage their progress. Intended for use with multi-site setups.

== Description ==

There are several plugins as part of this suite, they can be used independently, but are intended to work together. More details of the EnquiryBlogger project can be found on the [Learning Emergence](http://learningemergence.net/tools/enquiryblogger "Learning Emergence") site.

The development of EnquiryBlogger was funded as part of [Learning Futures](http://www.learningfutures.org/ "Learning Futures"), a project launched in 2008 by the [Paul Hamlyn Foundation](http://www.phf.org.uk/ "Paul Hamlyn Foundation") (a charity) and the [Innovation Unit](http://www.innovationunit.org/ "Innovation Unit") (a social enterprise) in order to find ways to improve educational outcomes in secondary school by increasing young peoples' engagement in learning.

**MoodView** - This displays a line graph plotting the mood of the student as their enquiry progresses. The widget displays the past few moods and allows a new one to be selected at any time. Changing moods (a hard coded drop-down list from 'going great' to 'it's a disaster') creates a new blog entry with an optional reason for the mood change. The graph is created using the included [Flot](http://code.google.com/p/flot/ "Flot - Attractive Javascript plotting for jQuery") JavaScript library.

**EnquirySpiral** - This widget provides a graphical display of the number of posts made in the first nine categories. A spiral of blobs appears over an image with each blob representing a category. The blobs are small and red when no posts have been made. They change to yellow for one or two posts, and green for three or more. In this way it is easy for the student to see how they are progressing, assuming the nine categories are well chosen.

**EnquirySpider** - This widget works in the same way as the EnquirySpiral, except that the blobs are arranged in a star shape. They are associated with seven categories. (from nine to sixteen so they don't conflict with the EnquirySpiral). The categories are intended to match with the [Effective Lifelong Learning Inventory diagram](http://www.ellionline.co.uk/ "ELLI").

Along with the three plugins above, there are corresponding ones that provide widgets on the dashboard for the teacher. These widgets will show the widgets for all of their students.

**BlogBuilder** - This plugin allows batch creation of blogs. Teacher names and student names are provided and all the blogs are built in one go. The teacher-student relationship is stored in a table in the database. Teachers who login will then see the dashboard showing the progress of the students assigned to them.

In order to make the blog builder more effective, we recommend [New Blog Defaults](http://wordpress.org/extend/plugins/wpmu-new-blog-defaults/ "New Blog Defaults plugin") plugin which allows each new blog to inherit the same set of defaults. In particular, setting the categories for the spiral and the ELLI spider and choosing the blog theme.

We wanted the blog builder to create all blogs and not require any layout changes or widget fiddling for individual blogs afterwards. To acheive this, we picked a theme to use and some code in the eb-blogbuilder.php is hard coded to this theme,

We used the [Suffusion](http://wordpress.org/extend/themes/suffusion) theme to place the widgets and also applied a number of [Suffusion](http://wordpress.org/extend/themes/suffusion) presets to every blog so they would be laid out as we required. We also created a child-theme of [Suffusion](http://wordpress.org/extend/themes/suffusion) which adds an extra page that will dynamically display links to the other students in a group that the blog belongs to. As this is all dependent on the Suffusion theme, we have commented that code out, but it can easily be re-added. 


Part of the Open University EnquiryBlogger suite.

== Installation ==

1. Upload the 'eb-enquiryblogbuilder' folder to the `/wp-content/plugins/` directory
1. Activate the plugins through the 'Plugins' menu in WordPress
1. If you intend to use the [Suffusion](http://wordpress.org/extend/themes/suffusion) theme, install it and uncomment the code in eb-blogbuilder and add the suffusion-child folder into /wp-content/themes
1. Install the [New Blog Defaults](http://wordpress.org/extend/plugins/wpmu-new-blog-defaults/ "New Blog Defaults plugin") plugin to set the default theme and categories for every new blog.

== Frequently Asked Questions ==

= Where do I find the blog builder page? =

You need to be super-admin. Then go to *My Sites*, *Network Admin*, *Desktop*. Click on *Settings* and then *Enquiry Blog Builder*.

= What if I want several groups? = 

Each set of blogs is associated with a school name and a group name within the school.
A blog is made by concatenating the school, group and student names.
The username is made from the school and student name. If the same student (or teacher) appears in several groups, they will have multiple blogs (each with a different group name) but only a single username to manage them.

= What if I want to add a new teacher to an existing group? =

In the 'New Member section, use an existing school and group and add the new member name and email. They will be added to that group - a new blog and user will be created if they don't already exist. All new members are students by default. On the main blog list for that group, click on the Status field to toggle to a teacher and back.

= What are the Categories I should use as a default for every new blog? =

The Categories appear as blobs on the Spiral and the ELLI Spider. Posting under these categories makes the blobs change colour and grow.
You can use any categories you like, but we use:

* Choosing
* Observing
* Questioning
* Narrating
* Mapping
* Connecting
* Formalising
* Validating
* Applying
* Changing & Learning
* Learning Relationships
* Strategic Awareness
* Resilience
* Creativity
* Meaning Making
* Critical Curiosity

== Screenshots ==

1. The three widgets on a student's blog.
1. A view on the dashboard for a teacher of two students.

== Changelog ==

= 1.1 =
* Set different images for each leg of the ELLI Spider for each group
* Set a different header for each group of blogs (requires the Suffusion Theme)
* Import a CSV to bulk create blogs
* Export a CSV of the blog urls and usernames for the current group
* Easily toggle a blog as belonging to a student or a teacher
* Easily add new members to existing groups
* Easily move members from one group to another
* Allow or disallow a blog from being customised (widgets and theme)
* Add several new members to a new group in one step
* Spider widget is larger
* Dashboard fixes for large numbers of blogs and students

= 1.0 =
* First release of the plugins

== Upgrade Notice ==

= 1.1 =

Many new features and bugs fixed.
Highlights include custom blog headers, easier blog management, CSV import and export