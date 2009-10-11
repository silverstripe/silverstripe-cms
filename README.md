SilverStripe CMS on GitHub
==========================

This a git clone of the subversion repository for SilverStripe CMS.  It is here for those of us who love both Git and SilverStripe.

Relevant links
--------------

 * [Main svn repository](http://svn.silverstripe.com/open/)
 * [Trac instance, with bug tracking and source browsing](http://open.silverstripe.org)
 * [Sapphire on GitHub](https://github.com/sminnee/sapphire)

Installation
------------

Because of the use of externals in the subversion repository, the process for setting up a site using these git clones is a litte complicated.  We are planning on improving this in the future, but for now, here are the relevant commands:

	mkdir ~/Sites/yournewsite
	cd ~/Sites/yournewsite
	svn checkout --ignore-externals http://svn.silverstripe.com/open/phpinstaller/trunk .

	git clone git://github.com/sminnee/silverstripe-cms.git cms
	git clone git://github.com/sminnee/sapphire.git sapphire

	svn checkout http://svn.silverstripe.com/open/modules/jsparty/trunk jsparty
	svn checkout http://svn.silverstripe.com/open/thirdparty/jsmin/tags/1.1.0 sapphire/thirdparty/jsmin
	svn checkout http://svn.silverstripe.com/open/thirdparty/simplepie/tags/1.0b3.1 sapphire/thirdparty/simplepie
	svn checkout http://svn.silverstripe.com/open/thirdparty/spyc/tags/0.2 sapphire/thirdparty/spyc
	svn checkout http://svn.silverstripe.com/open/thirdparty/simpletest/tags/1.0.1 sapphire/thirdparty/simpletest
	svn checkout http://svn.silverstripe.com/open/thirdparty/json/tags/1.31 sapphire/thirdparty/json
	svn checkout http://svn.silverstripe.com/open/thirdparty/zend/branches/1.8.1/library/Zend sapphire/thirdparty/Zend

