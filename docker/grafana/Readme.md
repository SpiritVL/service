# Импорт дашборда

Прежде чем импортировать дашборд в Grafana стоит сделать несколько вещей.

## Создать источник `service-db`

* Type: `PostgreSQL`
* Name: `service-db`
* Default: `true`
* Host URL: `postgres:5432`
* Database name: `service`
* Username и Password см. в docker-compose.yaml
* Version: `15`

## Создать источник `prometheus`

* Type: `Prometheus`
* Name: `prometheus`
* Default: `false`
* Prometheus server URL: `http://prometheus:9090`
* Authentication method: `No Authentication`

## Внести правки в `RequestButcher.json`

* Заменить prometheus `"uid": "cf0sxf0dtj7y8b"` на ваш, созданный
* Заменить service-db `"uid": "beyfs2vh2k8w0f"` на ваш, созданный
* Импортировать dashboard - должен сразу заработать из коробки со всеми настройками.

uid-ы источников можно посмотреть в url-е редактирования источника

## TODO: Сделать автоматический импорт источников и дашборда  

Можно автоматически подгружать источники и дашборды, см.
https://grafana.com/docs/grafana/latest/administration/provisioning/
