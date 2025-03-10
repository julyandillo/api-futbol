DOCKER_BE = api_enviroment
UID = $(shell id -u)

up:
	docker-compose up -d

stop:
	docker-compose stop

build:
	docker-compose build --build-arg UID=${UID}

destroy:
	docker-compose down

bash:
	docker exec -it --user ${UID} ${DOCKER_BE} bash

start:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony server:start -d

init:
	$(MAKE) up && $(MAKE) start

log:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony server:log

clear:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony console cache:clear

install:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony composer install

compile:
	docker exec -it --user ${UID} ${DOCKER_BE} symfony console asset-map:compile

prepare:
	$(MAKE) install && $(MAKE) compile