# General server setup
class { 'jobcastle::server': }

# Setup database requirements, application
class { 'jobcastle':
  db_user     => 'jobcastle',
  db_password => 'j08c45713',
  db_name     => 'jobcastle'
}

$domainName = 'www.jobcastle.com'
$vhostPath = "/var/www/vhosts/${domainName}"

exec { 'setup_initial_vhost':
  path => "/bin:/usr/bin:/sbin:/usr/sbin:/usr/local/bin:/usr/local/sbin",
  command => "mkdir -p ${vhostPath}/releases/initial/public && \
              ln -s ${vhostPath}/current ${vhostPath}/releases/initial",
  creates => "/var/www/vhosts/${domainName}/current/public"
}

# Setup base virtualhost
jobcastle::apache { $domainName:
  docroot    => "${vhostPath}/current/public",
  require    => [ Exec['setup_initial_vhost'], Class['jobcastle'] ]
}

# Apply any created firewall rules
Firewall <| |>