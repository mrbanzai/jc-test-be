class { 'jobcastle':
  db_user     => 'jobcastle',
  db_password => 'j08c45713'
}

jobcastle::apache { 'jobcastle.local':
  port       => 80,
  docroot    => '/vagrant/public',
  vhost_name => '*',
  priority   => 10,
  require    => Class['jobcastle']
}
