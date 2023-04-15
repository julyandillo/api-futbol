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