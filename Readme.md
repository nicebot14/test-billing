# О проекте
Тестовое задане - биллинговая система с очередями и параллельно работающими воркерами.
Подробней смотреть в .docx файле в корне.

# Используемые технологии:
docker/docker-compose, PHP-7.2/Symfony, RabbitMQ 3.7, Postgresql 10.5

# Install
1. cp .env.dist .env
1. docker-compose build
2. docker-compose up -d

# Запуск воркеров
`./docker/docker/worker.sh`

# Комментарии
1. В проекте не используются ORM и миграции т.к. тестовый. Дамп базы прогрузится через докер.
2. Для избежания race conditions используется advisory lock постгреса. В реальном проекте я бы использовал редис.
3. Схема базы возможно не оптимально сделана, но у меня нет каких-либо требований к ней :)
4. Апи сделано достаточно топорно, но мне кажется не в нем смысл.
5. Для сборки докера были использованы мои наработки с других проектов, но я убрал все лишнее.
6. Вместо документации к АПИ прилагаю в корне проекте коллекцию с Postman. Впрочем, оно и так достаточно простое, можно посмотреть что слать в src/DTO/Request. Там же простые правила валидации.

# Архитектура
1. 2 очереди: request и response. В request идут все события из простого АПИ. В response выкидывается событие со статусом success/error и сообщением об ошибке, если есть. response очередь в коде нигде не используется, она нужна только чтобы кидать туда ответы.
2. Для обеспечения отказоустойчивость АПИ предполагает использование ключей идемпонтетности (operation_id). Апи и воркеры работают по приницу "at least once".
3. Все успешно обработанные события сохраняются в таблицу events.
4. В целях экономии времени АПИ изменения баланса умеет как снимать, так и начислять баланс, в реальности я бы сделал 2 различных эндпоинта скорее всего.
5. Для механизма блокировки/разблокировки части средств на счете используется вспомогательная таблица (blocked_balances). Предполагается, что у пользователя может быть несколько одновременно заблокированных операций. После завершения/отклонения перевода записи оттуда удаляются.
6. Блокировка/разблокировка тоже идет на 1 ендпоинт, но у событий в базе будет различный тип.
7. Если какое-то событие не смогло захватить блокировку, то оно отправляется обратно в очередь до тех пор, пока блокировка не освободится.
8. Воркеры сделаны как команда симфони для того, чтобы иметь возможность инжектить сервисы.


