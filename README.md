# Tasking-Bundle

Advanced Tasking Manager for Symfony. 100% Php with high concurrency management.

Key features
- Background tasking: create jobs to be run by workers (Symfony commands) 
- 100% PHP, works on any Linux Server with PHP7.2+
- High concurrency management: use jobs token to ensure task is executed by a single process.
- Multi-server compatible: tokens are stored in a central SQL Table.
- Optimized memory footprint: create a dedicated Symfony environnement to reduce memory impacts.

[![Build Status](https://travis-ci.org/SplashSync/Tasking-Bundle.png?branch=master)](https://travis-ci.org/SplashSync/Tasking-Bundle) 
[![Total Downloads](https://poser.pugx.org/splash/tasking-bundle/downloads.png)](https://packagist.org/packages/splash/tasking-bundle) 
[![Latest Stable Version](https://poser.pugx.org/splash/tasking-bundle/v/stable.png)](https://packagist.org/packages/splash/tasking-bundle)

## Installation

#### Step 1 - Requirements and Installing the bundle
The first step is to tell composer that you want to download Tasking-Bundle which can
be achieved by typing the following at the command prompt:

```bash
composer require splash/tasking-bundle
```

#### Step 2 - Enable the bundle in your kernel

The bundle must be added to your `AppKernel`.

**Step usually not necescary in Symfony 4**.

```php
// app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new Splash\Tasking\SplashTaskingBundle(),
        // ...
    );
}
```

## Create Your First Job

Background jobs must extend [Splash\Tasking\Model\AbstractJob](https://github.com/SplashSync/Tasking-Bundle/blob/master/src/Model/AbstractJob.php).

```php
use Splash\Tasking\Model\AbstractJob;

class MyJob extends AbstractJob
{
    /** @return bool */
    public function execute() : bool
    {
        // Execute your background operations
        // ...
        return true;
    }
}
```

Job Token may be defined multiple way: 

```php
use Splash\Tasking\Model\AbstractJob;

class MyJob extends AbstractJob
{
    /** You can set it directly by overriding this constant */
    protected $token = "";

    /**
     * Or by writing an array of parameters to setToken()
     * @param array $parameters 
     * @return self
     */
    public function setup(array $parameters): self
    {
        //====================================================================//
        // Setup Job Token
        $this->setToken($parameters);

        return $this;
    }
}
```
## Available Job Types

There are few predefined abstract job types, for different kinds of tasks: 
- Splash\Tasking\Model\AbstractJob: a single simple task, executed once by job class.
- Splash\Tasking\Model\AbstractServiceJob: execute a Symfony service action with given parameters
- Splash\Tasking\Model\AbstractStaticJob: a simple task, executed & repeated every XX minutes.
- Splash\Tasking\Model\AbstractBatch: step-by-step, execute multiple tasks inside a single job. 

## Symfony Commands

The bundle comes with management commands to pilot workers from command line.
```bash
tasking:check       Tasking Service : Check Supervisor Process is Running on Current Machines
tasking:start       Tasking Service : Start All Supervisors & Workers Process on All Machines
tasking:status      Tasking Service : Check Status of Tasking Services
tasking:stop        Tasking Service : Stop All Supervisors & Workers Process on All Machines
tasking:supervisor  Run a Supervisor Worker Process 
tasking:worker      Run a Tasking Worker Process 
```

**Note: Tasking processes & supervisor are activated & checked each time a new task is added to queue**

## Configuration reference

Bundle configuration are stored under **splash_tasking**:

```yaml
splash_tasking:
    entity_manager: default     // Name of Doctrine Entity Manager to use for Tasks & Token Storage
    environement: prod          // Symfony Environnement to use for workers
    refresh_delay: 3            // Delay for workers status refresh
    watchdog_delay: 30          // Watchdog delay for tasks execution
    multiserver: false          // Enable multiserver mode
    multiserver_path: ''        // Url for remote servers checks 
    server:     
        force_crontab: false    // Use crontab to ensure supervisor is running (Useless if you uses 3+ workers)
        php_version: php        // Bash comamnd for php
    supervisor:
        max_age: 3600           // Time to live of supervisor process, if reached, process will die
        refresh_delay: 500      // Delay between two worker refresh  
        max_workers: 3          // Number of worker to use
        max_memory: 100         // Max. Memory, if reached, process will die
    workers:
        max_tasks: 100          // Max. number of jobs to execute, if reached, process will die
        max_age: 120            // Time to live of a worker process, if reached, process will die
        max_memory: 200         // Max. Memory, if reached, process will die
    tasks:
        max_age: 180            // Time to live of a finished task in database
        try_count: 5            // Number of failed attemps for a task
        try_delay: 120          // Delay before retry of a failed task
        error_delay: 40         // Delay to consider a started task as failed
    static:                     // Key => Class values for Static Jobs
        myStaticJob: AppBundle\Jobs\MyStaticJob          
```

## Docker Dev Environnement

A Docker Compose file is available to run a development server. 
You can start it typing the following at the command prompt:

```bash
docker-compose up -d
```

## Testing & Code Quality

This bundle uses Phpunit for functional testing.
```bash
docker-compose exec app php vendor/bin/phpunit 
```

This bundle uses Grumphp for all code quality checks (PHPMD, PhpCsFixer, PhpStan, and more...).
```bash
docker-compose exec app php vendor/bin/grumphp run 
```

## License

This package is available under the MIT license.

