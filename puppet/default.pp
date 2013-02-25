class { 'jobcastle':
  db_user     => 'jobcastle',
  db_password => 'j08c45713'
}

jobcastle::apache { 'jobcastle.local':
  port       => 80,
  docroot    => '/vagrant/backend/public',
  vhost_name => '*',
  priority   => 10,
  require    => Class['jobcastle']
}

jobcastle::apache { 'jobcastle-client.local':
  port       => 80,
  docroot    => '/vagrant/frontend/public',
  vhost_name => '*',
  priority   => 10,
  require    => Class['jobcastle']
}