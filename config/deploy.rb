set :application, "JobCastle"
set :repository,  "git@github.com:Skookum/Parallon-Jobs.git"

set :scm, :git
# Or: `accurev`, `bzr`, `cvs`, `darcs`, `git`, `mercurial`, `perforce`, `subversion` or `none`

set :branch, 'master'

set :deploy_to, "/var/www/vhosts/#{application}"
set :document_root, "#{deploy_to}/current"

set :default_stage, 'production'
require 'capistrano/ext/multistage'

namespace :deploy do
  task :finalize_update, :except => {no_release: true} do
    run "chmod -R g+w #{releases_path}/#{release_name}"
  end

  task :migrate do
  end

  task :restart, :except => {no_release: true} do
    %w(.git config).each do |dir|
      run "rm -rf #{document_root}/#{dir}"
    end
  end
end

# if you want to clean up old releases on each deploy uncomment this:
# after "deploy:restart", "deploy:cleanup"

# if you're still using the script/reaper helper you will need
# these http://github.com/rails/irs_process_scripts

# If you are using Passenger mod_rails uncomment this:
# namespace :deploy do
#   task :start do ; end
#   task :stop do ; end
#   task :restart, :roles => :app, :except => { :no_release => true } do
#     run "#{try_sudo} touch #{File.join(current_path,'tmp','restart.txt')}"
#   end
# end