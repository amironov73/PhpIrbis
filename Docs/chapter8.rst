===========
API-адаптер
===========

Файл `Jsoner.php` содержит простой API-адаптер для обращения к ИРБИС64.

Настройка адаптера осуществляется с помощью переменных окружения:

===================== ===================================== =====================================================
Переменная             Назначение                            Значение по умолчанию
===================== ===================================== =====================================================
IRBIS_HOST              IP-адрес сервера                     127.0.0.1
IRBIS_PORT              IP-порт сервера                      6666
IRBIS_USER              Логин для входа                      librarian
IRBIS_PASSWORD          Пароль (чувствителен к регистру!)    secret
IRBIS_DATABASE          База данных                          IBIS
===================== ===================================== =====================================================

Формат вызова:

.. code-block javascript
    Jsoner.php?op=name&arg1=val1&arg2=val2

Здесь `name` - имя вызываемой операции, `arg1` - имя первого аргумента, `val1` - значение первого аргумента. Количество аргументов зависит от вызываемой операции.

Пример использования на языке JavaScript:

.. code-block::javascript
    // путь к бэк-энду
    const baseURL = 'https://server.ru/Jsoner.php'

    function buildUrl (expression) {
        const database = 'ZIMA'
        const format = '@brief'
        const result = baseURL + '?op=search_format&db=' + database + '&expr=' + encodeURIComponent(expression)
            + '&format=' + format
        console.log(result)
        return result
    }

    function performSearch(expression) {
        hideError()
        resultContainer.innerHTML = ''
        const url = buildUrl (expression)
        showBusy()
        axios.get (url)
            .then (function (response) {
                handleSuccess (response.data)
                hideBusy ()
            })
            .catch (function (error) {
                console.log(error)
                hideBusy ()
                showError ('Сервер не ответил либо прислал невалидный ответ')
            })
    }

Предоставляемые сервисы:

=================== =================================================================
Операция             Назначение
=================== =================================================================
db_info              Получение информации о базе данных
list_db              Получение списка доступных баз данных
list_files           Получение списка доступных файлов
list_proc            Получение списка серверных процессов
list_terms           Получение списка поисковых терминов
max_mfn              Получение максимального MFN
read                 Чтение библиографических записей
read_menu            Чтение меню
read_opt             Чтение настроек оптимизации рабочих листов
read_raw             Чтение библиографической записи в "сыром виде"
read_terms           Чтение поисковых терминов
read_text            Чтение текстового файла
restart              Перезапуск сервера ИРБИС64
scenarios            Получение поисковых сценариев
search               Поиск библиографических записей
search_count         Подсчет количества записей, удовлетворяющих поисковому запросу
search_format        Поиск с одновременным расформатированием записей
server_stat          Статистика работы сервера ИРБИС64
user_list            Получение списка пользователей, зарегистрированных в системе
version              Получение версии сервера ИРБИС64
=================== ================================================================

Подробнее об операциях

* **db_info** - получение информации о базе данных. Пример ответа сервера:

.. code-block::javascript

    {
      "name": "",
      "description": "",
      "maxMfn": 2523959,
      "logicallyDeletedRecords": [1218, 1353],
      "physicallyDeletedRecords": [596, 623, 642, 688],
      "nonActualizedRecords": [],
      "lockedRecords": [],
      "databaseLocked": false,
      "readOnly": false
    }

* **list_db** - получение списка доступных баз данных. Пример ответа сервера:

.. code-block::javascript

    [
      {
        "name": "IBIS",
        "description": "Электронный каталог",
        "maxMfn": 0,
        "logicallyDeletedRecords": [],
        "physicallyDeletedRecords": [],
        "nonActualizedRecords": [],
        "lockedRecords": [],
        "databaseLocked": false,
        "readOnly": false
      },
      {
        "name": "RDR",
        "description": "Читатели",
        "maxMfn": 0,
        "logicallyDeletedRecords": [],
        "physicallyDeletedRecords": [],
        "nonActualizedRecords": [],
        "lockedRecords": [],
        "databaseLocked": false,
        "readOnly": false
       }
    ]

* **list_files** - получение списка доступных файлов

.. code-block::javascript

    ["203mars.mnu","2151re.mnu","3005-1mars.mnu","VDR.MNU","znm.mnu"]

* **list_proc** - получение списка серверных процессов

.. code-block::javascript

    [
        {
            "number":"*",
            "ipAddress":"Local IP address",
            "name":"\u0421\u0435\u0440\u0432\u0435\u0440 \u0418\u0420\u0411\u0418\u0421",
            "clientId":"*****",
            "workstation":"*****",
            "started":"24.09.2024 13:20:02",
            "lastCommand":"*****",
            "commandNumber":"*****",
            "processId":"4752",
            "state":"\u0410\u043a\u0442\u0438\u0432\u043d\u044b\u0439"
        },
        {
            "number":"\u0410\u043a\u0442\u0438\u0432\u043d\u044b\u0439",
            "ipAddress":"1",
            "name":"Disconnected",
            "clientId":"",
            "workstation":"362299",
            "started":"",
            "lastCommand":"24.09.2024 15:57:45",
            "commandNumber":"IRBIS_CONTEXT_LIST",
            "processId":"2",
            "state":"1092"
        }
    ]

* **list_terms** - Пример ответа сервера:

.. code-block::javascript

* **max_mfn** - Пример ответа сервера:

.. code-block::javascript

    2523959

* **read** - Пример ответа сервера:

.. code-block::javascript

    {
        "database":"IBIS",
        "mfn":123,
        "version":1,
        "status":0,
        "fields": [
            {"tag":101,"value":"rus","subfields":[]},
            {"tag":331,"value":"\u041e \u0442\u0432\u043e\u0440\u0447\u0435\u0441\u0442\u0432\u0435 \u0440\u0443\u0441\u0441\u043a\u043e\u0433\u043e \u043f\u043e\u044d\u0442\u0430 \u0414. \u041a\u0435\u0434\u0440\u0438\u043d\u0430.","subfields":[]},
            {"tag":463,"value":"","subfields":[{"code":"j","value":"2001"},{"code":"c","value":"\u0420\u043e\u0434\u043d. \u0437\u0435\u043c\u043b\u044f"},{"code":"1","value":"\u0421."},{"code":"s","value":"11-12."},{"code":"0","value":"a-\u0438\u043b"},{"code":"v","value":"25 \u044f\u043d\u0432"}]},
            {"tag":621,"value":"83.3(2\u0420\u043e\u0441=\u0420\u0443\u0441)6","subfields":[]},
            {"tag":700,"value":"","subfields":[{"code":"3","value":"134"},{"code":"a","value":"\u0420\u0443\u043c\u044f\u043d\u0446\u0435\u0432"},{"code":"b","value":"\u0410. \u0413."},{"code":"g","value":"\u0410\u043d\u0434\u0440\u0435\u0439 \u0413\u0440\u0438\u0433\u043e\u0440\u044c\u0435\u0432\u0438\u0447"}]},
            {"tag":900,"value":"","subfields":[{"code":"t","value":"a"},{"code":"b","value":"12"}]},
            {"tag":903,"value":"83.3(2\u0420\u043e\u0441=\u0420\u0443\u0441)6-850365","subfields":[]},
            {"tag":919,"value":"","subfields":[{"code":"a","value":"rus"},{"code":"n","value":"0102"},{"code":"g","value":"ca"},{"code":"z","value":"rus"},{"code":"c","value":"d"}]},
            {"tag":907,"value":"","subfields":[{"code":"c","value":"\u041f\u041a"},{"code":"a","value":"20010223"},{"code":"b","value":"\u0411\u0417\u0410"},{"code":"1","value":"03"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20010330"},{"code":"b","value":"\u041a\u041c\u0412"}]},
            {"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20010914"},{"code":"b","value":"\u041a\u041c\u0412"}]},{"tag":907,"value":"","subfields":[{"code":"a","value":"20011207"},{"code":"b","value":""},{"code":"1","value":"0"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20011208"},{"code":"b","value":"\u041a\u041c\u0412"},{"code":"1","value":"0"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20011214"},{"code":"b","value":"\u041a\u041c\u0412"},{"code":"1","value":"0"}]},{"tag":907,"value":"","subfields":[{"code":"a","value":"20020202"},{"code":"b","value":""}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"GBL"},{"code":"a","value":"20020322"},{"code":"b","value":"\u041a\u041c\u0412"},{"code":"1","value":"0"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":""},{"code":"a","value":"20020527"},{"code":"b","value":""},{"code":"1","value":""}]},{"tag":907,"value":"","subfields":[{"code":"c","value":""},{"code":"a","value":"20020604"},{"code":"b","value":""},{"code":"1","value":""}]},{"tag":907,"value":"","subfields":[{"code":"c","value":""},{"code":"a","value":"20031231"},{"code":"b","value":""},{"code":"1","value":""}]},{"tag":629,"value":"","subfields":[{"code":"b","value":"\u041c\u0435\u0441\u0442\u043d\u043e\u0435 \u0438\u0437\u0434. \u0431\u0435\u0437 \u043a\u0440\u0430\u0435\u0432\u0435\u0434\u0447\u0435\u0441\u043a\u043e\u0433\u043e \u043c\u0430\u0442\u0435\u0440\u0438\u0430\u043b\u0430"},{"code":"c","value":"81"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20080422"},{"code":"b","value":"BikovaGV"},{"code":"1","value":"01"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20080519"},{"code":"b","value":"BikovaGV"},{"code":"1","value":"01"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"obrzv"},{"code":"a","value":"20110120"},{"code":"b","value":"\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412"},{"code":"1","value":"01"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"obrzv"},{"code":"a","value":"20110121"},{"code":"b","value":"\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412"},{"code":"1","value":"01"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20110124"},{"code":"b","value":"\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412"},{"code":"1","value":"01"}]},{"tag":920,"value":"ASP","subfields":[]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20110131"},{"code":"b","value":"\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412"},{"code":"1","value":"01"}]},{"tag":907,"value":"","subfields":[{"code":"c","value":"\u041a\u0420"},{"code":"a","value":"20130819"},{"code":"b","value":"\u041d\u0430\u0448\u0438\u0432\u0430\u043d\u043a\u0438\u043d\u0430\u0410\u0412"}]},{"tag":200,"value":"","subfields":[{"code":"a","value":"\u0412\u043e\u0437\u0434\u0443\u0445 \u0432\u0440\u0435\u043c\u0435\u043d\u0438"},{"code":"f","value":"\u0410. \u0413. \u0420\u0443\u043c\u044f\u043d\u0446\u0435\u0432"}]},{"tag":905,"value":"","subfields":[{"code":"d","value":"1"},{"code":"j","value":"1"},{"code":"2","value":"1"}]},{"tag":690,"value":"101000 ","subfields":[{"code":"l","value":"11.01"}]},{"tag":203,"value":"","subfields":[{"code":"a","value":"\u0422\u0435\u043a\u0441\u0442"},{"code":"c","value":"\u043d\u0435\u043f\u043e\u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0435\u043d\u043d\u044b\u0439"}]}
        ]
    }

* **read_menu** - Пример ответа сервера:

.. code-block::javascript

* **read_opt** - Пример ответа сервера:

.. code-block::javascript

* **read_raw** - Пример ответа сервера:

.. code-block::javascript

    {
        "database":"IBIS",
        "mfn":123,
        "status":0,
        "version":1,
        "fields":[
            "101#rus",
            "331#\u041e \u0442\u0432\u043e\u0440\u0447\u0435\u0441\u0442\u0432\u0435 \u0440\u0443\u0441\u0441\u043a\u043e\u0433\u043e \u043f\u043e\u044d\u0442\u0430 \u0414. \u041a\u0435\u0434\u0440\u0438\u043d\u0430.",
            "463#^j2001^c\u0420\u043e\u0434\u043d. \u0437\u0435\u043c\u043b\u044f^1\u0421.^s11-12.^0a-\u0438\u043b^v25 \u044f\u043d\u0432",
            "621#83.3(2\u0420\u043e\u0441=\u0420\u0443\u0441)6",
            "700#^3134^a\u0420\u0443\u043c\u044f\u043d\u0446\u0435\u0432^b\u0410. \u0413.^g\u0410\u043d\u0434\u0440\u0435\u0439 \u0413\u0440\u0438\u0433\u043e\u0440\u044c\u0435\u0432\u0438\u0447",
            "900#^ta^b12",
            "903#83.3(2\u0420\u043e\u0441=\u0420\u0443\u0441)6-850365",
            "919#^arus^n0102^gca^zrus^cd",
            "907#^c\u041f\u041a^a20010223^b\u0411\u0417\u0410^103",
            "907#^c\u041a\u0420^a20010330^b\u041a\u041c\u0412",
            "907#^c\u041a\u0420^a20010914^b\u041a\u041c\u0412",
            "907#^a20011207^b^10",
            "907#^c\u041a\u0420^a20011208^b\u041a\u041c\u0412^10",
            "907#^c\u041a\u0420^a20011214^b\u041a\u041c\u0412^10",
            "907#^a20020202^b",
            "907#^cGBL^a20020322^b\u041a\u041c\u0412^10",
            "907#^c^a20020527^b^1","907#^c^a20020604^b^1",
            "907#^c^a20031231^b^1",
            "629#^b\u041c\u0435\u0441\u0442\u043d\u043e\u0435 \u0438\u0437\u0434. \u0431\u0435\u0437 \u043a\u0440\u0430\u0435\u0432\u0435\u0434\u0447\u0435\u0441\u043a\u043e\u0433\u043e \u043c\u0430\u0442\u0435\u0440\u0438\u0430\u043b\u0430^c81",
            "907#^c\u041a\u0420^a20080422^bBikovaGV^101","907#^c\u041a\u0420^a20080519^bBikovaGV^101",
            "907#^cobrzv^a20110120^b\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412^101",
            "907#^cobrzv^a20110121^b\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412^101",
            "907#^c\u041a\u0420^a20110124^b\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412^101",
            "920#ASP",
            "907#^c\u041a\u0420^a20110131^b\u0410\u0440\u0435\u0444\u044c\u0435\u0432\u0430\u0415\u0412^101",
            "907#^c\u041a\u0420^a20130819^b\u041d\u0430\u0448\u0438\u0432\u0430\u043d\u043a\u0438\u043d\u0430\u0410\u0412",
            "200#^a\u0412\u043e\u0437\u0434\u0443\u0445 \u0432\u0440\u0435\u043c\u0435\u043d\u0438^f\u0410. \u0413. \u0420\u0443\u043c\u044f\u043d\u0446\u0435\u0432",
            "905#^d1^j1^21","690#101000 ^l11.01",
            "203#^a\u0422\u0435\u043a\u0441\u0442^c\u043d\u0435\u043f\u043e\u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0435\u043d\u043d\u044b\u0439"
        ]
    }

* **read_terms** - Пример ответа сервера:

.. code-block::javascript

* **read_text** - Пример ответа сервера:

.. code-block::javascript

    "mfn,&unifor('+0')\n"

* **restart** - Пример ответа сервера:

.. code-block::javascript

* **scenarios** - Пример ответа сервера:

.. code-block::javascript

    [
        {
            "name":"\u0410\u0432\u0442\u043e\u0440",
            "prefix":"A=",
            "dictionaryType":0,
            "menuName":"",
            "oldFormat":"",
            "correction":"",
            "truncation":"",
            "hint":"",
            "modByDicAuto":"",
            "logic":"",
            "advance":"ATHRA,A=,@sadv",
            "format":""
        },
        {
            "name":"\u0417\u0430\u0433\u043b\u0430\u0432\u0438\u0435\/\u041d\u0430\u0437\u0432\u0430\u043d\u0438\u0435",
            "prefix":"T=",
            "dictionaryType":0,
            "menuName":"",
            "oldFormat":"",
            "correction":"!DMODT",
            "truncation":"",
            "hint":"",
            "modByDicAuto":"",
            "logic":"",
            "advance":"",
            "format":""
        },
        {
            "name":"\u041a\u043b\u044e\u0447\u0435\u0432\u044b\u0435 \u0441\u043b\u043e\u0432\u0430",
            "prefix":"K=",
            "dictionaryType":0,
            "menuName":"",
            "oldFormat":"",
            "correction":"!DMODK",
            "truncation":"",
            "hint":"",
            "modByDicAuto":"",
            "logic":"4",
            "advance":"",
            "format":""
        }
    ]

* **search** - Пример ответа сервера:

.. code-block::javascript

    [12071,37407,46278,151387,184496,184716,233491,281993,466895,660851]

* **search_count** - Пример ответа сервера:

.. code-block::javascript

    177

* **search_format** - Пример ответа сервера:

.. code-block::javascript

    [
        "Ageron, Charles-Robert. Histoire de l'Algerie contemporaine \/ Ch.-Robert Ageron, 1994. - 125 p",
        "Algebre lineaire : (DEUG Sciences A) \/ M. -C. Chatard-Moulin, J. Ezquerra, J. Ezquerra, 1996. - 160 p",
        "Banks, Iain M. The Algebraist : science fiction : abridged \/ I. M. Banks ; read by A. Lesser, 2004. - 6 el. opt. discs (CD-ROM)",
        "Barnett, Raymond. Intermediate Algebra. Structure and Use. \/ R. A. Barnett, 1990. - 579 \u0441.",
        "Beauvoir, Simone. Lettres \u00e0 Nelson Algren : un amour transatlantique, 1947-1964 \/ S. de Beauvoir ; texte \u00e9tabli, trad. de l'anglais et annot. par S. Le Bon de Beauvoir, 1997. - 610 p"
    ]

* **server_stat** - Пример ответа сервера:

.. code-block::javascript

    {
        "runningClients": [
            {
                "number":"*",
                "ipAddress":"127.0.0.1",
                "port":"6666",
                "name":"\u0421\u0435\u0440\u0432\u0435\u0440 \u0418\u0420\u0411\u0418\u0421",
                "id":"*****",
                "workstation":"*****",
                "registered":"24.09.2024 13:20:01",
                "acknowledged":"*****",
                "lastCommand":"*****",
                "commandNumber":"*****"
            },
            {
                "number":"1",
                "ipAddress":"127.0.0.1",
                "port":"6666",
                "name":"librarian",
                "id":"288142",
                "workstation":"\"\u041a\u0430\u0442\u0430\u043b\u043e\u0433\u0438\u0437\u0430\u0442\u043e\u0440\"",
                "registered":"24.09.2024 13:59:10",
                "acknowledged":"24.09.2024 13:59:10",
                "lastCommand":"IRBIS_REG",
                "commandNumber":"1"}],
                "clientCount":2,
                "totalCommandCount":29

                }
        ]
    }

* **user_list** - Пример ответа сервера:

.. code-block::javascript

    [
        {
            "number":"1",
            "name":"librarian",
            "password":"secret",
            "cataloger":"irbisc.ini",
            "reader":"irbisr.ini",
            "circulation":"irbisb.ini",
            "acquisitions":"irbisp.ini",
            "provision":"irbisk.ini",
            "administrator":"irbisa.ini"
        },
        {
            "number":"2",
            "name":"user",
            "password":"password",
            "cataloger":"irbisc.ini",
            "reader":"irbisr.ini",
            "circulation":"irbisb.ini",
            "acquisitions":"IRBISP.INI",
            "provision":"irbisk.ini",
            "administrator":"irbisa.ini"
        }
    ]

* **version** - получение версии сервера ИРБИС64. Пример ответа сервера:

.. code-block::javascript

    {
        "organization":"ИОГУНБ",
        "version":"64.2014",
        "maxClients":100000,"connectedClients":1
    }
