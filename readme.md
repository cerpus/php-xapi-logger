# Cerpus\xAPI-logger package
A Laravel package to help you log xAPI statements from Content Author (H5P) to our LRS.

Add AVT context (from tags) and modify the actor to be GDPR friendly.

Mostly geared towards logging in a format that makes querying from the AVT project easy and fast.

## Installing
`$ composer require cerpus/xapi-logger`

Put the LRS config in `config/auth.php`
```php
...
 'lrs' => [
        'server' => env('LRS_SERVER', 'https://learninglocker.cerpus-course.com/'),
        'key' => env('LRS_KEY', ''),
        'secret' => env('LRS_SECRET', ''),
    ],
 ...
```

In Laravel 5.5 and up the auto discovery should kick in when requiring the package. If you are on Laravel <= 5.4, register the service provider in `config/app.php`. 
```php
'providers' => [
    Cerpus\xAPI\LRSLoggerServiceProvider::class,
]
```

## Usage
Example using an event based approach using queues since processing the raw statement requires calls across the network. 

For a full example take a peek in the [EdStep source code](https://stash.cerpus.com/projects/BRAIN/repos/cerpuscourse/browse).
```
resources/assets/js/h5pevents.js
app/Http/Controllers/H5PServiceController.php
app/Providers/EventServiceProvider.php
app/Events/xApiEvent.php
app/Listeners/LogXApiStatementToLrs.php
```

Gather info in the event
```php
<?php

namespace App\Events;

use Auth;
use App\Events\Event;
use Illuminate\Queue\SerializesModels;

class xApiEvent extends Event
{
    use SerializesModels;

    public $rawXApiStatement = null;
    public $userId = null;
    public $path = '';

    public function __construct($logData)
    {
        // This takes care of the raw xAPI statement from H5P
        $this->rawXApiStatement = new \stdClass();
        if (property_exists($logData, 'event')) {
            if (property_exists($logData->event, 'statement')) {
                $this->rawXApiStatement = $logData->event->statement;
            }
        }

        // Currently the path is not used in this example, but can be used to add more context,
        // for example Course, module, activity in EdStep or Game/Game-node in Gamilab. 
        if (property_exists($logData, 'path')) {
            $this->path = $logData->path;
        }

        // Assign the userId. Remember GDPR and use something that is easy to anonymize
        // DO NOT USE email or name here!
        // AuthId is preferred, but if unavailable use local userId, sessionId or similar. AuthId is used to "track" users across systems (EdStep and Gamilab)
        $user = Auth::user();
        if ($user) {
            $this->userId = Auth::user()->id;
        } else {
            $session = request()->session();
            if ($session->getId()) {
                $this->userId = 'EdStepSession-' . $session->getId();
            } else {
                $this->userId = 'EdStep-Unknown';
            }
        }
    }
}

```

Process the event in a listener.
```php
<?php

namespace App\Listeners;

use Log;
use App\User;
use Cerpus\xAPI\Actor;
use Cerpus\xAPI\Logger;
use App\Events\xApiEvent;
use Cerpus\xAPI\Statement;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogXApiStatementToLrs implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Log xAPI statement to LRS.
     *
     * @param  xApiEvent $event
     * @return void
     */
    public function handle(xApiEvent $event)
    {
        try {
            $rawStatement = $event->rawXApiStatement;
            $user = User::find($event->userId);
            $path = $event->path;

            $statement = new Statement($rawStatement);

            // Replace the Actor in the raw statement with a GDPR friendly actor.
            // homePage defaults to https://auth.cerpus-course.com,
            // but should be set to the local system url if no authId is available.
            $actor = new \stdClass();
            if (!$user) { // Anonymous user
                $actor = new Actor($event->userId);
                $actor->setHomePage(config('app.url')); // Be sure the app.url config is correct
            } else { //
                $actor = new Actor($user->auth_id ?? $user->id);
                if (!$user->auth_id) {
                    $actor->setHomePage(config('app.url')); // Be sure the app.url config is correct
                }
            }
            $statement->setActor($actor); // Replace the actor in the raw statement with the GDPR friendly actor

            /*
             * Add AVT tags as context activities to the statement.
             * This step can take some time depending on the number of tags and makes a call to Content Author (and MetaDataService) to fetch the tags.
             * It is the essensial step for our xAPI statements to be useable for querying in the AVT project, so don't forget it!    
             */
            $statement->addAVTContextActivities(); 

            /** @var Logger $logger */
            $logger = app(Logger::class); 
            // Log the statement to our LRS
            $logger->log($statement);

            if ($logger->hasError()) {
                throw new \Exception($logger->getErrorMessage(), $logger->getErrorCode());
            }
        } catch (\Throwable $exception) {
            Log::error(__METHOD__ . ':[Attempt #:' . $this->attempts() . '] (' . $exception->getCode() . ') ' . $exception->getMessage());
            $this->release($this->attempts() * 30); // Release back into queue, but wait a while before trying again. 
        }
    }
}
```
