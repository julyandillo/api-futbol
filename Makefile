DOCKER_BE = api_enviroment
UID = $(shell id -u)

up:
	docker compose up -d

stop:
	docker compose stop

build:
	docker compose build --build-arg UID=${UID}

rebuild:
	docker compose build --pull --no-cache --build-arg UID=${UID}

destroy:
	docker compose down --remove-orphans

bash:
	docker exec -it --user ${UID} ${DOCKER_BE} bash

start:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony server:start -d

init:
	$(MAKE) up && $(MAKE) start

log:
	U_ID=${UID} docker exec -it --user ${UID} ${DOCKER_BE} symfony server:log

clear:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony console cache:clear

install:
	$(MAKE) base-install && $(MAKE) install-assets

base-install:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony composer install

install-assets:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony console importmap:install

compile:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony console asset-map:compile

prepare:
	$(MAKE) install && $(MAKE) compile