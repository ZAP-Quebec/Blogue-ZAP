class GetGuestIP < Vagrant::Command::Base
    def execute
      exec("vagrant ssh -c \"ip addr list eth1 |grep 'inet ' |cut -d' ' -f6|cut -d/ -f1\"");
            end
end

class GetHostname < Vagrant::Command::Base
    def execute
      exec("vagrant ssh -c hostname");
    end
end

class SyncSQL < Vagrant::Command::Base
  def execute
    # À faire, il faut faire un script qui reçoit une bd en param avec un type d'import et le path du wp-config.php pour se connecter à la BD. 
    #exec("vagrant ssh -c ''");
    # Dirty hack to show how to do it. Someone else will do it better
    
    with_target_vms() do |machine|
      puts machine.config.vm.shared_folders;
      machine.config.vm.provisioners.each do |prov|
        puts prov.shortcut;
        if prov.shortcut == :chef_client
          puts "We have found a chef provisioner, getting the MySQL access"

        end

      end

    end
  end
end

class Remove < Vagrant::Command::Base
# Deletes the current vagrant VM as a chef client (but not the node)
  def execute
    with_target_vms() do |machine|
      host = machine.config.vm.host_name
      puts "Removing client " +host+ " form Chef"
      exec("vagrant ssh -c 'sudo knife client delete "+ host +" --user "+ host +" --key /etc/chef/client.pem --server http://progtools.libeo.com:4000 --yes'");
    end
  end
end

class OpenWebBrowserHostname < Vagrant::Command::Base

  #def initialize(app, env)
  #  @app = app
  #  @env = env
  #end 

  def execute
    os = RbConfig::CONFIG['host_os'];
    url = "http://google.com/";
    with_target_vms() do |machine|

    url = "http://" +  machine.config.vm.host_name+ "/";  
    case os
      when /darwin/
        exec("open " + url);
      when /linux/
        exec("xdg-open " + url);
      when /mingw/
        exec("start " + url);
    end

    end
  end
end

class LibeoDevConfigs < Vagrant::Config::Base
  attr_accessor :dev_hostname

  def validate(env, errors)
  end

end


Vagrant.config_keys.register(:lbo) { LibeoDevConfigs }
Vagrant.commands.register(:ip) { GetGuestIP }
Vagrant.commands.register(:hostname) { GetHostname }
Vagrant.commands.register(:open) { OpenWebBrowserHostname }
Vagrant.commands.register(:remove) { Remove }
Vagrant.commands.register(:syncSQL) { SyncSQL }
