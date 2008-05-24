#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#

suffix=`basename \`dirname \\\`pwd\\\`\``

install: mysite/_config.php

mysite/_config.php:
	php install.php install SS_testdatabase_${suffix}

test: clean install
	$(MAKE) -C sapphire test

clean:
	if [ -f .htaccess ]; then rm .htaccess; fi
	touch .htaccess
	if [ -f mysite/_config.php ]; then rm mysite/_config.php; fi
