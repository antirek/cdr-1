<?php

/**
 * AutoinformController class  - AutoinformController.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  (c) 2013, AC
 */

/**
 * AutoinformController class
 * Mar 12 2013 11:00:00:000AM
 * @package		AC
 */
class AutoinformController extends Controller {

    /** @var array */
    protected $_filters = array(
        'fromdate' => array('parseDatetime'),
        'todate' => array('parseDatetime'),
        'type' => array('controller', 'parseType'),
        'result' => array('controller', 'parseResult'),
        'phone' => 1,
        'retries' => array('controller', 'parseRetries'),
        'cutnum' => array('parseId')
    );

    /** @var int */
    public $id;

    /** @var string */
    public $section = 'list';

    /** @var ACDbResult */
    private $_result;

    public function __construct() {
        App::Config('autoinform');

        parent::__construct();

        $from = new ACDateTime();
        $from->sub(new DateInterval('P1D'));

        $this->_filters['fromdate'][1] = $from;
    }

    public function init($params = null) {
        parent::init($params);

        if ($_GET['section'] == 'log') {
            $this->section = 'log';
            $this->id = FiltersValue::parseId($_GET['id']);
        }

        $section = 'section' . $this->section;
        $this->$section();

//        if ($this->export && $_GET['export']) {
//            $this->export();
//        } else {
//            if ($this->section == 'list') {
//                $this->viewMain('page/page-autoinform.php');
//            }
//        }
    }

    public function export() {
        $data = array(
            array(
                'Принято',
                $this->getComplete(0, 15),
                $this->getComplete(15, 30),
                $this->getComplete(30, 45),
                $this->getComplete(45, 60),
                $this->getComplete(60, 90),
                $this->getComplete(90, 120),
                $this->getComplete(120, 180),
                $this->getComplete(180, 32768),
                $this->getAvgComplete(),
            ),
            array(
                'Потеряно',
                $this->getAbandoned(0, 15),
                $this->getAbandoned(15, 30),
                $this->getAbandoned(30, 45),
                $this->getAbandoned(45, 60),
                $this->getAbandoned(60, 90),
                $this->getAbandoned(90, 120),
                $this->getAbandoned(120, 180),
                $this->getAbandoned(180, 32768),
                $this->getAvgAbandoned(),
            )
        );

        $export = new Export($data);
        $export->thead = array(
            'Время ожидания',
            '0 - 15',
            '15 - 30',
            '30 - 45',
            '45 - 60',
            '60 - 90',
            '90 - 120',
            '120 - 180',
            '180 - +',
            'Среднее',
        );
        $export->send('timeman');
        exit();
    }

    /**
     * Страница списка логов
     */
    public function sectionList() {
        $this->rows = array();

        if (!is_array(App::Config()->autoinform['type'])) {
            $this->iniType();
        }

        // Подготавливаем запрос
        $query = App::Db()->createCommand()->select()
                ->from('autodialout')
                ->addWhere(App::Config()->autoinform['datetime'], array($this->fromdate->format(), $this->todate->format()), 'BETWEEN')
                ->order('datetotell');
        if ($this->type != null) {
            $query->addWhere('type', $this->type);
        }
        if ($this->result != null) {
            $query->addWhere('result', $this->result);
        }
        if ($this->phone != null) {
            $query->addWhere('dialnum', $this->phone);
        }
        if ($this->retries != null) {
            $query->addWhere('retries', $this->retries);
        }
        $this->_query($query->toString());

        $this->viewMain('page/page-autoinform.php');

    }

    public function iniType() {
        $type = @include CONFIG_DIR . '/autoinform_callback.php';
        Log::dump($type, "<font color=\"#cc0000\">парамет \"<i>Тип вызова</i>\" загружен из файла</font>");
        App::Config()->autoinform['type'] = $type;
    }

    /**
     * Страница лога автоинформатора
     */
    public function sectionLog() {
        $this->viewMain('page/page-autoinform-log.php');
    }

    /**
     * Выполнить результат
     * @param string $query
     * @return void
     */
    private function _query($query) {
        if (App::Config()->autoinform['mssql']['enable']) {
            $cfg = App::Config()->autoinform['mssql'];
            $db = mssql_connect($cfg['host'], $cfg['user'], $cfg['pass']);
            if (!$db) {
                Log::error("mssql_connect> Не удаеться создать соединение с MSSQL!", 'MSSQL');
                return;
            }
            if ($cfg['dbname']) {
                if (!mssql_select_db($cfg['dbname'], $db)) {
                    Log::error("mssql_select_db> Не удаеться подключиться к базе MSSQL! ", 'MSSQL');
                    return;
                }
            }

            $this->_result = mssql_query($query);
            Log::trace($query, 'MSSQL');
            if (!$this->_result) {
                Log::error("mssql_query> Не удаеться выполнить запрос MSSQL!", 'MSSQL');
                return;
            }
        } else {
            $this->_result = App::Db()->query($query);
        }
    }

    /**
     * Получить масив строки запроса
     * @return Autodialout
     */
    public function fetchAutodialout() {
        if ($this->_result instanceof ACDbResult) {
            $result_row = @$this->_result->fetch_array();
        } else {
            $result_row = @mssql_fetch_array($this->_result);
        }
        if ($result_row) {
            return new Autodialout($result_row);
        }
    }

    /**
     * Получить масив строки запроса
     * @return array
     */
    public function fetchArray() {
        if ($this->_result instanceof ACDbResult) {
            return $this->_result->fetch_array();
        } else {
            return @mssql_fetch_array($this->_result);
        }
    }

    /**
     * Количество записей
     * @return int
     */
    public function numRows() {
        if ($this->_result instanceof ACDbResult) {
            $count = $this->_result->count();
        } else {
            $count = @mssql_num_rows($this->_result);
        }
        return (int) $count;
    }

    /**
     * Тип вызова 0, 1, 2
     * @param type $type
     * @return int
     */
    public function parseType($type) {
        $type = ($type != 2 && $type != 1) ? 0 : $type;
        return $type;
    }

    /**
     * Результат
     * @param type $result
     * @return int
     */
    public function parseResult($result) {
        if ($result === null)
            return;
        //if (in_array($result, array(0, 1, 2, 3, 4, 98, 99, 97, 96))) {
        if (@App::Config()->autoinform['result'][$result]) {
            return $result;
        }
    }

    /**
     *
     * @param type $retries
     * @return int
     */
    public function parseRetries($retries) {
        if ($retries >= 0 && $retries <= 3) {
            return $retries;
        }
        return -1;
    }

}