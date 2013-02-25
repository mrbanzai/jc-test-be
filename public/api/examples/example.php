<?php
error_reporting(E_ALL);

// for our example to work in the command line, we're overriding the host
// (this is not necessary from web)
$_SERVER['HTTP_HOST'] = 'local.jobcastle.com';

// include the library
$path = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR);
require_once($path . '/../libs/Client.php');

// you can initialize in one of two ways
$config = array(
    'apiBaseUrl' => 'http://local.jobcastle.com/api/',
    'publicKey' => '50f27ab87ceae988f43d8ca191159bfd673c87cf',
    'privateKey' => '63aace81abe7f536843834919cd7931bd9053149'
);

$client = new Skookum_Api_Client($config);

/*
// Here is a second way to load the PHP client
$publicKey = '50f27ab87ceae988f43d8ca191159bfd673c87cf';
$privateKey = '63aace81abe7f536843834919cd7931bd9053149';

$client = new Skookum_Api_Client($config, $publicKey, $privateKey);
*/

// retrieve the total number of categories
$numCategories = $client->get('/categories/count/');
echo 'The total number of categories:' . PHP_EOL;
var_dump($numCategories);

// retrieve all categories with their names in ascending order
$categories = $client->get('/categories/', array('perPage' => 2));
echo 'The first categories, defaulting to 25 per page ordered by name in ascending order:' . PHP_EOL;
var_dump($categories);

// retrieve all categories with their names in descending order
$categories = $client->get('/categories/', array('sortOrder' => 'DESC'));
echo 'The first categories, defaulting to 25 per page ordered by name in descending order:' . PHP_EOL;
var_dump($categories);

// example of retrieving a small subset of categories for pagination
$categories = $client->get('/categories/', array('perPage' => 5));
echo 'The first 5 categories:' . PHP_EOL;
var_dump($categories);

//==================
// RETRIEVAL BY CITY
//==================

// retrieve the total number of cities
$numCities = $client->get('/cities/count/');
echo 'The total number of cities:' . PHP_EOL;
var_dump($numCities);

// retrieve cities with their names in ascending order
$cities = $client->get('/cities/');
echo 'The first cities, defaulting to 25 per page ordered by name in ascending order:' . PHP_EOL;
var_dump($cities);

// retrieve cities with their names in descending order
$cities = $client->get('/cities/', array('sortOrder' => 'DESC'));
echo 'The first cities, defaulting to 25 per page ordered by name in descending order:' . PHP_EOL;
var_dump($cities);

// retrieve cities with their counts in descending order
$cities = $client->get('/cities/', array('orderBy' => 'count', 'sortOrder' => 'DESC'));
echo 'The first cities, defaulting to 25 per page ordered by count in descending order:' . PHP_EOL;
var_dump($cities);

// example of retrieving a small subset of cities for pagination
$cities = $client->get('/cities/', array('perPage' => 5));
echo 'The first 5 cities:' . PHP_EOL;
var_dump($cities);

//==================
// RETRIEVAL BY STATE
//==================

// retrieve the total number of states
$numStates = $client->get('/states/count/');
echo 'The total number of states:' . PHP_EOL;
var_dump($numStates);

// retrieve states with their names in ascending order
$states = $client->get('/states/');
echo 'The first states, defaulting to 25 per page ordered by name in ascending order:' . PHP_EOL;
var_dump($states);

// retrieve states with their names in descending order
$states = $client->get('/states/', array('sortOrder' => 'DESC'));
echo 'The first states, defaulting to 25 per page ordered by name in descending order:' . PHP_EOL;
var_dump($states);

// retrieve states with their counts in descending order
$states = $client->get('/states/', array('orderBy' => 'count', 'sortOrder' => 'DESC'));
echo 'The first states, defaulting to 25 per page ordered by count in descending order:' . PHP_EOL;
var_dump($states);

// example of retrieving a small subset of states for pagination
$states = $client->get('/states/', array('perPage' => 5));
echo 'The first 5 states:' . PHP_EOL;
var_dump($states);

//======================
// RETRIEVAL BY SCHEDULE
//======================

// retrieve the total number of schedules
$numSchedules = $client->get('/schedules/count/');
echo 'The total number of schedules:' . PHP_EOL;
var_dump($numSchedules);

// retrieve schedules with their names in ascending order
$schedules = $client->get('/schedules/');
echo 'The first schedules, defaulting to 25 per page ordered by name in ascending order:' . PHP_EOL;
var_dump($schedules);

// retrieve schedules with their names in descending order
$schedules = $client->get('/schedules/', array('sortOrder' => 'DESC'));
echo 'The first schedules, defaulting to 25 per page ordered by name in descending order:' . PHP_EOL;
var_dump($schedules);

// retrieve schedules with their counts in descending order
$schedules = $client->get('/schedules/', array('orderBy' => 'count', 'sortOrder' => 'DESC'));
echo 'The first schedules, defaulting to 25 per page ordered by count in descending order:' . PHP_EOL;
var_dump($schedules);

// example of retrieving a small subset of schedules for pagination
$schedules = $client->get('/schedules/', array('perPage' => 5));
echo 'The first 5 schedules:' . PHP_EOL;
var_dump($schedules);

//=================
// RETRIEVAL BY JOB
//=================

// retrieve the total number of jobs
$numJobs = $client->get('/jobs/count/');
echo 'The total number of jobs:' . PHP_EOL;
var_dump($numJobs);

// retrieve total number of jobs in California
$numJobs = $client->get('/jobs/count/', array('state' => 'CA'));
echo 'The total number of jobs in California:' . PHP_EOL;
var_dump($numJobs);

// all jobs
$jobs = $client->get('/jobs/');
echo 'All jobs:' . PHP_EOL;
var_dump($jobs);

// retrieve total number of jobs in California and Texas
$numJobs = $client->get('/jobs/count/', array('state' => array('CA', 'TX')));
echo 'The total number of jobs in California and Texas:' . PHP_EOL;
var_dump($numJobs);

// retrieve total number of jobs in San Antonio, Texas
$numJobs = $client->get('/jobs/count/', array('city' => 'San Antonio', 'state' => 'TX'));
echo 'The total number of jobs in San Antonio, Texas:' . PHP_EOL;
var_dump($numJobs);

// retrieve job titles
$jobs = $client->get('/jobs/titles/', array('perPage' => 2));
echo 'The first jobs, defaulting to 25 per page ordered by title in ascending order:' . PHP_EOL;
var_dump($jobs);

// retrieve jobs by radius from charlotte
$jobs = $client->get('/jobs/radius/', array('zipcode' => '28202', 'radius' => 250));
echo 'The first 25 jobs within a 250 mile radius of zipcode 28202:' . PHP_EOL;
var_dump($jobs);

// search for jobs in California and Texas
$jobs = $client->get('/jobs/search/', array('state' => array('CA', 'TX'), 'perPage' => 2));
echo 'Jobs in California and Texas:' . PHP_EOL;
var_dump($jobs);
die;

// search for jobs in San Antonio, Texas
$jobs = $client->get('/jobs/search/', array('city' => 'San Antonio', 'state' => 'TX'));
echo 'Jobs in San Antonio, Texas:' . PHP_EOL;
var_dump($jobs);

// search for jobs in two locations
$jobs = $client->get('/jobs/search/', array('locations' => array('St. Petersburg, FL', 'San Antonio, TX')));
echo 'Jobs in either St. Petersburg, FL or San Antonio, TX:' . PHP_EOL;
var_dump($jobs);
