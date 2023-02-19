DOCKER_BE = api_enviroment

up:
	docker-compose up --build -d

stop:
	docker-compose stop

build:
	docker-compose build

destroy:
	docker-compose down

bash:
	docker exec -it ${DOCKER_BE} bash