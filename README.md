Приложение для сбора статистики по логам

Запуск:
1. `docker compose up -d`
2. `sudo docker compose exec app php yii migrate`

Команда для импорта логов: `docker compose exec app php yii log/parse`

Проект по умолчанию должен открываться по адресу `http://localhost:8000`
