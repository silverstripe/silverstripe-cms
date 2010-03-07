#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#

test:
	$(MAKE) -C sapphire test

getallmodules:
	if [ -d subsites ]; then svn update subsites; else svn co http://svn.silverstripe.com/open/modules/subsites/trunk subsites; fi
	if [ -d genericdataadmin ]; then svn update genericdataadmin; else svn co http://svn.silverstripe.com/open/modules/genericdataadmin/trunk genericdataadmin; fi
	if [ -d forum ]; then svn update forum; else svn co http://svn.silverstripe.com/open/modules/forum/trunk forum; fi
	if [ -d cmsworkflow ]; then svn update cmsworkflow; else svn co http://svn.silverstripe.com/open/modules/cmsworkflow/trunk cmsworkflow; fi
	if [ -d multiform ]; then svn update multiform; else svn co http://svn.silverstripe.com/open/modules/multiform/trunk multiform; fi
	if [ -d events ]; then svn update events; else svn co http://svn.silverstripe.com/open/modules/events/trunk events; fi
	if [ -d auth_openid ]; then svn update auth_openid; else svn co http://svn.silverstripe.com/open/modules/auth_openid/trunk auth_openid; fi
	if [ -d blog ]; then svn update blog; else svn co http://svn.silverstripe.com/open/modules/blog/trunk blog; fi
	if [ -d gallery ]; then svn update gallery; else svn co http://svn.silverstripe.com/open/modules/gallery/trunk gallery; fi
	if [ -d rssaggregator ]; then svn update rssaggregator; else svn co http://svn.silverstripe.com/open/modules/rssaggregator/trunk rssaggregator; fi
	if [ -d sharethis ]; then svn update sharethis; else svn co http://svn.silverstripe.com/open/modules/sharethis/trunk sharethis; fi
	if [ -d userforms ]; then svn update userforms; else svn co http://svn.silverstripe.com/open/modules/userforms/trunk userforms; fi
