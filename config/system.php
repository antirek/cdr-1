<?php
/**
 * Системные настройки.
 *
 * Директория начинаеться от корня сайта (если не оговорено) со слеша  / и заканчиваеться без него.
 * Если рабочая директория являеться корнем сайта, то устанавливается пустое значение
 *
 * charset="UTF-8"
 */
return array(
    'charset'   => 'utf-8',
    'webpath'   => '/cdr', // корневой путь скриптов
    'enable_ie' => 0, // Выполнять скрипты на Internet Explorer (некорректное отображение)

    // база данных
    'database'  => array(
        'host'   => 'localhost',
        'user'   => 'root',
        'pass'   => '',
        'dbname' => 'asterix',
        'params' => array(// дополнительные параметры базы
            'exception' => 0, // выбрасывать исключения
            'log'       => 1, // вести локальные логи запросов (большие затраты времени)
            'charset'   => 'utf8' // кодировка
        )
    ),

    'secret' => 'secret',

    // LOG
    // вывод логов (дебагир)
    'debug'     => 0,

    // Доп. файл настроек. Переопределяет настройки из файла.
    'config'    => 'localhost'
);