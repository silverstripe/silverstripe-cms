#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#

test:
	$(MAKE) QUERYSTRING="$(QUERYSTRING)" -C sapphire test

getallmodules:
	if [ -d subsites ]; then svn update subsites; else svn co http://svn.silverstripe.com/open/modules/subsites/trunk subsites; fi
	if [ -d genericdataadmin ]; then svn update genericdataadmin; else svn co http://svn.silverstripe.com/open/modules/genericdataadmin/trunk genericdataadmin; fi
	if [ -d forum ]; then svn update forum; else svn co http://svn.silverstripe.com/open/modules/forum/trunk forum; fi
	if [ -d cmsworkflow ]; then svn update cmsworkflow; else svn co http://svn.silverstripe.com/open/modules/cmsworkflow/trunk cmsworkflow; fi
	if [ -d multiform ]; then svn update multiform; else svn co http://svn.silverstripe.com/open/modules/multiform/trunk multiform; fi
	if [ -d events ]; then svn update events; else svn co http://svn.silverstripe.com/open/modules/events/trunk events; fi