<?php

require 'config.php';

function fromAnsi($s) {
    return mb_convert_encoding($s, 'UTF-8', 'CP1251');
}

/**
 * Проверка, не является ли читатель плохим.
 * @param $reader array Данные о читателе.
 * @return string|null Сообщение об ошибке, если читатель плохой.
 */
function badReader ($reader) {
    if ($reader['blocked'])
        return 'Читатель заблокирован';

    if ($reader['debtor'])
        return 'Есть задолженность';

    if ($reader['podpisal'])
        return 'Подписан обходной лист';

    if (!$reader['mail'])
        return 'Нет email';

    if (!$reader['password'])
        return 'Нет пароля';

    return null;
}

/**
 * Получение категории читателя.
 * @param $reader array Данные читателя.
 * @return string|null Категория.
 */
function getCategory ($reader) {
    if (!$reader)
        return null;

    return fromAnsi($reader['category']);
}

class ReaderManager
{
    private $conn;

    public function __construct()
    {
        global $connectionString;
        $this->conn = new PDO($connectionString);
    }

    public function __destruct()
    {
        unset($this->conn);
    }

    /**
     * Поиск читателя по номеру билета.
     * @param $ticket string Номер билета.
     * @return array Найденный читатель.
     */
    public function fetchReader ($ticket) {
        $stmt = $this->conn->prepare("SELECT * FROM [readers] WHERE [ticket] = :ticket");
        $stmt->execute(array('ticket' => $ticket));
        $result = $stmt->fetchAll();
        if ($result) {
            $result = $result[0];
        }
        return $result;
    }

    /**
     * Поиск читателя по паре "логин-пароль".
     * @param $login string Логин (номер читательского, e-mail либо номер телефона).
     * @param $password string Пароль.
     * @return array|null Массив с данными читателя либо `null`.
     */
    public function findReader($login, $password) {
        if (!$login || !$password) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT * FROM [readers] WHERE ([ticket] = :ticket OR [mail] = :mail) AND ([password] = :password)");
        $stmt->execute(array('ticket' => $login, 'mail' => $login, 'password' => $password));
        $result = $stmt->fetchAll();
        if ($result) {
            $result = $result[0];
        }
        return $result;
    }

}
