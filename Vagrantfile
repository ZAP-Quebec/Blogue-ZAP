# -*- mode: ruby -*-
# vi: set ft=ruby :
$:.unshift File.dirname(__FILE__)

require "vagrantscripts/tool_set.rb"

Vagrant::Config.run do |config|
  config.vm.box = "precise32"
  config.vm.host_name = File.basename(Dir.getwd) + "-" + ENV['LOGNAME']

  config.vm.box_url = "http://files.vagrantup.com/precise32.box"
  
  config.vm.customize ["modifyvm", :id, "--memory", 1524]

  config.vm.network :bridged



  config.vm.share_folder "wp-content", "/var/www/wordpress/wp-content", "./"

  config.vm.provision :chef_client do |chef|
        chef.chef_server_url = "http://progtools.libeo.com:4000"
        chef.validation_key_path = "vagrantscripts/validation.pem"
        # Set the environment for the chef server
        chef.environment = "dev"

        chef.provisioning_path = "/etc/chef"
        
        chef.json = { 
        
        :mysql => {
            :server_root_password => "1qaz",
            :server_repl_password => "1qazxsw2",
            :server_debian_password => "1qazxsw23edc"
            
        }, 
        
        :php => {
            :conf_dir => "/etc/php5/apache2/",
    		:directives => {
    			:post_max_size => "64M",
    			:upload_max_filesize => "48M",
    			:memory_limit => "128M",
    			:display_errors => "On"
    		}
    	},
        
        :wordpress => {
            :db => {
                :user => "root",
                :password  => "1qaz"
            },
	    
  	    :lang => 'fr_FR',

            :server_aliases => "zap"
        }
        
        }
        
	chef.add_role("Nix")
        chef.add_role("WordPress")
        
  end
  config.vm.provision :shell, :path => "vagrantscripts/change_apache_user.sh"
end

