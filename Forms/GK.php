<?php

require 'ReaderManager.php';

header('Content-Type: application/json; charset=utf-8');

function getReaderInfo()
{
    $login = @$_REQUEST['login'];
    $password = @$_REQUEST['password'];
    if (!$login || !$password) {
        $result = array('success' => false, 'message' => 'Неверные логин или пароль');
        echo json_encode($result);
        return;
    }
    $mgr = new ReaderManager();
    $rdr = $mgr->findReader($login, $password);
    if (!$rdr) {
        $result = array('success' => false, 'message' => 'Ничего не найдено!');
        echo json_encode($result);
    } else {
        $isBad = badReader($rdr);
        if ($isBad) {
            $result = array('success' => false, 'message' => $isBad);
            echo json_encode($result);
        } else {
            $result = array(
                'name'       => fromAnsi(@$rdr['name']),
                'category'   => fromAnsi(@$rdr['category']),
                'department' => fromAnsi(@$rdr['department']),
                'ticket'     => fromAnsi(@$rdr['ticket']),
                'debtor'     => @$rdr['debtor'],
                'blocked'    => @$rdr['blocked'],
                'podpisal'   => @$rdr['podpisal'],
                'mail'       => fromAnsi(@$rdr['mail']),
                'job'        => fromAnsi(@$rdr['job'])
            );
            $result = array('success' => true, 'reader' => $result);
            echo fromAnsi(json_encode($result));
        }
    }
    unset($mgr);
}

try {
    $opcode = @$_REQUEST['op'];

    if (!$opcode) {
        $result = array('success' => false, 'message' => 'Не задана операция');
        echo json_encode($result);
        return;
    }

    switch ($opcode) {
        case 'info':
            getReaderInfo();
            break;

        default:
            $result = array('success' => false, 'message' => 'Неизвестная операция');
            echo json_encode($result);
    }

}
catch (Exception $ex) {
    $result = array('success' => false, 'message' => $ex->getMessage());
    echo json_encode($result);
}