#!/bin/bash

echo "Removing local wp-content"
sudo rm -rf /var/www/wordpress/wp-content
echo "Linking to remote wp-content"
ln -s /var/www/wp-content /var/www/wordpress/