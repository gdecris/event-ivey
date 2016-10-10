# event-ivey
PHP Event System with queue driver support 

## Setup using illuminate/container

```
  // Register the appropriate queue
  $container->singleton(QueueContract::class, MemoryQueue::class);
  // Bind the dispatcher as a singleton
  $container->singleton(EventDispatcher::class);
```

## Basic Usage
### Listen to events
```
  $container->make(EventDispatcher::class)->listen('event.name', function($payload) {
    // Listener receives the payload
  });
```
### Fire events
```
  $container->make(EventDispatcher::class)->fire('event.name', ['some' => 'payload data']);
```


## Queued Listener
### Registering the Listener
```
  $container->make(EventDispatcher::class)->listen('event.name', MyListener::class);
```
### Listener class
```
  use Ivey\Events\EventListener;

  class MyListener extends EventListener 
  {
      proteceted static $should_queue = true;

      public function fire($payload)
      {
          // TODO: Implement fire() method.
      }
  }
```
