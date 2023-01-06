
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

if [ ! -f /var/www/html/var/install.lock ]; then

	echo "Wait for MySql Container to Start"
	sleep 10

  echo "Install Git"
  apt update && apt install -y git

	echo "Update Database Schemas"
	cd /var/www/html/
	php bin/console doctrine:schema:update --force

  echo "OK" > /var/www/html/var/install.lock
fi

rm -Rf /var/www/html/var/cache

php bin/console
php bin/console --env=test
cp /var/www/html/var/cache/test/appAppKernelTestDebugContainer.xml /project/var/cache/dev/testContainer.xml
chown www-data:www-data -Rf /var/www/html/var

echo "Setup Apache..."
a2enmod rewrite
service apache2 reload

echo "Serving Symfony via Apache..."
exec "apache2-foreground"