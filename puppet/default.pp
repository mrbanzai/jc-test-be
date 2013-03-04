# General server setup
class { 'jobcastle::server': }

# Setup database requirements, application
class { 'jobcastle':
  db_user     => 'jobcastle',
  db_password => 'j08c45713',
  db_name     => 'jobcastle'
}

# Disable SELinux, as the /vagrant files don't have
# the proper context / label
class { 'selinux':
  state => "disabled",
  type => "targeted"
}

# Setup base virtualhost
jobcastle::apache { 'jobcastle.local':
  docroot       => '/vagrant/public',
  docroot_owner => 'vagrant',
  docroot_group => 'apache',
  require    => Class['jobcastle']
}

# Apply any created firewall rules
Firewall <| |>