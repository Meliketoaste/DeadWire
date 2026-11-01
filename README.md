# DeadWire

PHP for people who let requests die.
> Request in.  
> Stream HTML out.  
> Let Datastar morph it.

```bash
php -S localhost:8000
```

Realtime multiplayer just needs SSE and shared state (pub/sub or a database). A generator with foreach and ob_get_clean() makes fanout convenient.
