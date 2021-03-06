<?php

/**
 * CdrController class  - CdrController.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  (c) 2013, AC
 */

/* duration */

/**
 * CdrController class
 *
 * @property ACDateTime $fromdate -
 * @property ACDateTime $todate   -
 * @property string     $oper         -
 * @property string     $src          -
 * @property string     $dst          -
 * @property string     $coming       -
 * @property bool       $fileExist    -
 * @property string     $comment      -
 * @property int        $limit        -
 * @property int        $offset       -
 * @property string     $sort
 * @property int        $desc
 */
class CdrController extends Controller {

    protected $_filters = array(
        'fromdate' => array('parseDatetime'), // array('_parseDatetime'),
        'todate' => array('parseDatetime'),
        'oper' => 1,
        'src' => array('parsePhone'),
        'dst' => array('parsePhone'),
        'coming' => 1,
        'fileExist' => 1,
        'comment' => 1,
        'limit' => 1,
        'offset' => 1,
        'queue' => 1,
        'sort' => array('parseSort', array(
                "calldate",
                "src",
                "dst",
                "audio_duration",
                "comment",
                "queue"
            )),
        'mob' => array('parseCheck'),
        'vip' => 1,
        'desc' => 1
    );
    protected $_sections = array(
        'calls' => 'Звонки',
        'answering' => 'Автоинформатор',
    );

    /**
     * @var ACDbConnection
     */
    protected $_db;

    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $countFileExists;

    /**
     * @var array
     */
    public $rows = array();

    // --------------------------------------------------------------

    public function __construct() {
        App::Config('cdr');
        App::Config()->cdr['file_format_low'] = strtolower(App::Config()->cdr['file_format']);
        App::Config()->cdr['file_format_up'] = strtoupper(App::Config()->cdr['file_format']);

        parent::__construct();

        $from = new ACDateTime();
        $from->sub(new DateInterval('P1D'));

        $this->_filters['fromdate'][1] = $from;

        if (App::Config()->cdr['another_base']) {
            $cfg = App::Config()->cdr['database'];
            $this->_db = new ACDbConnection($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['dbname'], App::Config()->database['params']);
        } else {
            $this->_db = App::Db();
        }
    }

    /**
     * Автоматическая инициализация
     * @param array $params
     */
    public function init($params = null) {
        parent::init($params);
        $this->offset = FiltersValue::parseOffset($_GET['offset'], $this->limit);
        if ($this->_actType === self::TYPE_ACTION) {
            $action = "action" . $this->_atcion;
            if (!method_exists($this, $action)) {
                $this->content = $action . "  error action";
                return;
            }
        } else {
            $action = 'index';
        }

        $this->$action();
    }

    /**
     * Формирет страницу
     */
    public function index() {
        while ($this->getCountFileExists()) {
            $this->actionCheckFile();
        }

        $search = 'search' . $this->getSection();
        $this->$search();

        // Подключаем файлы JS и CSS
        $this->dataPage['links'] .= '<script src="' . Utils::linkUrl('lib/player/jquery.jplayer.min.js') . "\"></script>\n"
                . ' <link rel="stylesheet" href="' . Utils::linkUrl('lib/player/jplayer.blue.monday.css') . "\">\n";

        $this->viewMain("page/cdr/{$this->_section}.php");
    }

    /**
     * Редактировать коментарий
     * @param array $params
     * @return bool
     */
    public function actionEditComment($params = null) {
        if (!is_array($params)) {
            $params = $_POST;
        }

        $id = FiltersValue::parseId($params['id']);
        $comment = FiltersValue::parseComment($params['comment']);


        $this->_db->createCommand()->update(Cdr::TABLE)
                ->addSet('comment', $comment, true)
                ->addWhere('id', $id)
                ->query();

        $this->content = 1;

        return $this->_db->success;
    }

    /**
     * Проверяет наличия файлов аудиозаписей и редактирует записи в таблицы.
     * Проверка проходитпорциями
     * @param int $limit_scan количество сканируемых файлов за раз
     * @return array результат работы [count_yes_1, count_file_yes_2, count__no]
     */
    public function actionCheckFile($limit_scan = 100, $fromdate = null, $todate = null) {
        if ($limit_scan <= 0) {
            $limit_scan = 100;
        }
        if (!ACValidation::date($todate)) {
            $todate = $this->todate;
        }
        if (!ACValidation::date($fromdate)) {
            $fromdate = $this->todate;
        }

        $command = $this->_db->createCommand()->select('id, calldate, uniqueid, dcontext')
                ->from(Cdr::TABLE)
                ->where(' `file_exists` IS NULL ')
                ->limit($limit_scan);

        if ($this->fromdate) {
            $command->addWhere('calldate', $this->fromdate->format(), '>=');
        }
        if ($this->todate) {
            $command->addWhere('calldate', $this->todate->format(), '<=');
        }
        $file_no = array();
        $rows = $command->query()->getFetchObjects(Cdr);
        /* @var $cdr Cdr */
        foreach ($rows as $cdr) {
            $cdr->file_exists =  $cdr->getFileExistsInPath();
            if ($cdr->file_exists) {
                $cmd = $this->_db->createCommand()->update(Cdr::TABLE)
                    ->addSet('`file_exists`', $cdr->file_exists)
                    ->addSet('`audio_duration`', $cdr->getTime())
                    ->addWhere('id', $cdr->id);
                $cmd->query();
            } else {
                $file_no[] = $cdr->id;
            }

            if ($this->test_cli) {
                $r = array();
                foreach ($cdr->toArray() as $k=>$v) {
                    $r[]="$k:$v";
                }
                if ($cdr->file_exists) {
                    $r[]="file:".$cdr->getFile();
                    $r[]="time:".$cdr->getTime();
                }
                echo implode(", ", $r)."\n";
            }

        }
        if (count($file_no)) {
            $cmd = $this->_db->createCommand()->update(Cdr::TABLE)
                    ->addSet('`file_exists`', "0")
                    ->addWhere('id', $file_no, 'IN');
            $cmd->query();
        }
        return count($rows);
    }

    /**
     * Выполнить выборгу по звонкам.
     * Поиск записей по заданому фильтру контролера
     * @return bool
     */
    public function searchCalls() {
        $sort = $this->sort;
        if ($this->desc) {
            $sort .= " DESC ";
        }

        log::dump($this->_db);
        $command = $this->_db->createCommand()->select()
                ->from(Cdr::TABLE)
                ->calc()
                ->addWhere('file_exists', '0', '>')
                ->addWhere('dcontext', array('autoinform', 'outgoing', 'dialout'), 'NOT IN')
                ->limit($this->limit)
                ->offset($this->offset)
                ->order($sort);

        // начальная дата
        if ($this->fromdate) {
            $command->addWhere('calldate', $this->fromdate->format(), '>=');
        }
        // конечная дата
        if ($this->todate) {
            $command->addWhere('calldate', $this->todate->format(), '<=');
        }
        // оператор
        if ($this->oper) {
            $oper = $this->_db->escapeString($this->oper);
            $command->where(
                    " AND ("
                    . "(`dcontext` = 'incoming' AND `dstchannel` = '$oper')"
                    . "OR  (`dcontext` <> 'incoming' AND `userfield`= '$oper' )"
                    . ") ");
        }
        // поиск по входящим номерам
        if ($this->src) {
            $command->addWhere('src', "%{$this->src}%", 'LIKE');
        }
        // поиск по исходящим номерам
        if ($this->dst) {
            $command->addWhere('dst', "%{$this->dst}%", 'LIKE');
        }
        // тип звонка входящий, исходящий или все
        if ($this->coming) {
            if ($this->coming == Cdr::INCOMING) {
                $command->addWhere('dcontext', 'incoming');
            }
            if ($this->coming == Cdr::OUTCOMING) {
                $command->where(
                        " AND ("
                        . "(LEFT(`dcontext`, 4)='from' AND CHAR_LENGTH(`dst`)>4)"
                        . "OR (LEFT(`dcontext`, 4)<>'from' AND `dcontext`<>'incoming')"
                        . ")");
            }
        } else {
            $command->where(" AND NOT (  LEFT(`dcontext`, 4)='from' AND CHAR_LENGTH(`dst`)<=4  ) ");
        }
        // коментарий
        if ($this->comment) {
            $command->addWhere('comment', "%{$this->comment}%", 'LIKE');
        }
        // только мобильные
        if ($this->mob) {
            // "9ХХХХХХХХХ" и исходящие вида
            // "[9]89XXXXXXXXX".
            $command->where(" AND ("
                    . "(LEFT(`src`, 1)='9' AND CHAR_LENGTH(`src`)=10)"
                    . "OR (LEFT(`dst`, 3)='989' AND CHAR_LENGTH(`dst`)=12)"
                    . ")");
        }

        if ($this->vip) {
            $command->leftJoinOn('queue_priority', 'number', 'src')
                    ->having('callerid IS NOT NULL');
        }

        // uniqueid: 1353062433.4492
        // callId:   1342707947.325200

        // Очереди
        // $command->leftJoinOn('call_status', 'uniqueid', "callId" );
        // if (is_array($this->queue)) {
        //     $command->addWhere('queue', $this->queue, 'IN');
        // }


        $result = $command->query();
        $this->offset = $result->calc['offset'];
        $this->limit = $result->calc['limit'];
        $this->count = $result->calc['count'];
        $this->rows = $result->getFetchObjects('Cdr');

        return ($result->count()) ? true : false;
    }

    /**
     * Выполнить выборгу по Автоинформаторам.
     * Поиск записей по заданому фильтру контролера
     * @return bool
     */
    public function searchAnswering() {
        $sort = $this->sort;
        if ($this->desc) {
            $sort .= " DESC ";
        }
        $command = $this->_db->createCommand()->select()
                ->from(Cdr::TABLE)
                ->calc()
                ->limit($this->limit)
                ->offset($this->offset)
                ->order($sort);
        if (!@array_key_exists('fignore', $_GET)) {
            $command->addWhere('file_exists', '0', '>');
        }
        // ->addWhere('file_exists', '0', '>')
        $command->addWhere('dcontext', array('autoinform', 'outgoing', 'dialout'), 'IN');



        // начальная дата
        if ($this->fromdate) {
            $command->addWhere('calldate', $this->fromdate->format(), '>=');
        }
        // конечная дата
        if ($this->todate) {
            $command->addWhere('calldate', $this->todate->format(), '<=');
        }
        // поиск по входящим номерам
        if ($this->src) {
            $command->addWhere('src', "%{$this->src}%", 'LIKE');
        }
        // коментарий
        if ($this->comment) {
            $command->addWhere('comment', "%{$this->comment}%", 'LIKE');
        }
        // только мобильные
        if ($this->mob) {
            // "9ХХХХХХХХХ" и исходящие вида
            // "[9]89XXXXXXXXX".
            $command->where(" AND ("
                    . "(LEFT(`src`, 1)='9' AND CHAR_LENGTH(`src`)=10)"
                    . "OR (LEFT(`dst`, 3)='989' AND CHAR_LENGTH(`dst`)=12)"
                    . ")");
        }

        // Очереди
        // $command->leftJoinOn('call_status', 'uniqueid', "callId" );
        // if (is_array($this->queue)) {
        //     $command->addWhere('queue', $this->queue, 'IN');
        // }

        $result = $command->query();
        $this->offset = $result->calc['offset'];
        $this->limit = $result->calc['limit'];
        $this->count = $result->calc['count'];
        $this->rows = $result->getFetchObjects('Cdr');

        return ($result->count()) ? true : false;
    }

    /**
     * Возвращает количество неотсканеных записей
     * @return int
     */
    public function getCountFileExists() {

        $command = $this->_db->createCommand()->select('COUNT(id) AS total')
                ->from(Cdr::TABLE)
                ->where(' `file_exists` IS NULL ');
        if ($this->fromdate) {
            $command->addWhere('calldate', $this->fromdate->format(), '>=');
        }
        if ($this->todate) {
            $command->addWhere('calldate', $this->todate->format(), '<=');
        }

        $arr = $command->query()->fetch();
        return $arr['total'];
    }

}