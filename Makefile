#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#

test:
	$(MAKE) QUERYSTRING="$(QUERYSTRING)" -C framework test