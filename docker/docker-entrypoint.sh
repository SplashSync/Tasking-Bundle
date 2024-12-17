
################################################################################
#
#  This file is part of SplashSync Project.
# 
#  Copyright (C) Splash Sync <www.splashsync.com>
# 
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# 
#  For the full copyright and license information, please view the LICENSE
#  file that was distributed with this source code.
# 
#  @author Bernard Paquier <contact@splashsync.com>
#
################################################################################
#!/bin/sh

################################################################################
# Compile Project
################################################################################

echo "Compile Project"
composer update
rm -Rf /var/www/html/var/cache
php bin/console
php bin/console --env=test

if [ ! -f /var/www/html/var/install.lock ]; then

	echo "Wait for MySql Container to Start"
	sleep 10

	echo "Update Database Schemas"
	php bin/console doctrine:schema:update --force

  echo "OK" > /var/www/html/var/install.lock
fi

################################################################################
# Start Apache
################################################################################
echo "Setup Apache..."
chown www-data:www-data -Rf /var/www/html/var
a2enmod rewrite
service apache2 reload

echo "Serving Symfony via Apache..."
exec "apache2-foreground"