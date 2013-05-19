#!/bin/bash
sudo sed -i 's/User www-data/User vagrant/g' /etc/apache2/apache2.conf 
sudo sed -i 's/Group www-data/Group vagrant/g' /etc/apache2/apache2.conf 


sudo sed -i 's/export APACHE_RUN_USER=www-data/export APACHE_RUN_USER=vagrant/g' /etc/apache2/envvars 
sudo sed -i 's/export export APACHE_RUN_GROUP=www-data/export export APACHE_RUN_GROUP=vagrant/g' /etc/apache2/envvars 

sudo /etc/init.d/apache2 restart
sudo chown -R vagrant:vagrant /var/www
