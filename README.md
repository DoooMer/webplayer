## Simplest way to listen your music from your server in browser.

This application provided as is and not ready to production. Use at your own
risk.

---

Application consist of 3 parts:

- web server (nginx) / not required additional configuration

- web app (php) / need mount directory with your music,
  ex. `-v "/media/music:/app/public/audio"`. Also you can specified redis
  connection from env, ex. `-e REDIS_HOST=redis0 -e REDIS_PORT=63791`

- index storage (redis) / not required additional configuration, tested
  on [redis:6.2.6](https://hub.docker.com/_/redis?tab=tags&name=6.2.6)

Example of `docker-compose.yaml`:

```
version: "3.0"
services:
  nginx:
    image: doomer/webplayer:1.0-nginx
    depends_on:
      - webapp

  webapp:
    image: doomer/webplayer:1.0-php
    environment:
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    volumes:
      - "/media/music:/app/public/audio:ro"
    depends_on:
      - redis

  redis:
    image: redis:6.2.6-alpine
```