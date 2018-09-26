# launch
docker-compose up -d

#enter into container
docker exec -it asyncphpexamples bash

#run scripts
CLI (from within container)*: php cli.php
CGI: go to localhost in browser

#stop
docker-compose down

#clear
docker image rm asyncphpexamples
docker image rm asyncphpexamples_webserver

#P.S.
Не парился я с красивым разбросом скриптов по файлам, по-сему, для корректного выполнения примера необходимо закомментировать все остальные (примеры) 