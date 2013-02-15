<?php
/**
 * cdr.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  (c) 2013, AC
 */
/* @var $this CdrController */

$this->dataPage['links'] .=''
        . '<link href="js/player/jplayer.blue.monday.css" rel="stylesheet" type="text/css" />'
        . '<script type="text/javascript" src="js/player/jquery.jplayer.min.js"></script>';

$dir    = App::Config()->cdr->monitor_dir . '/';
$format = App::Config()->cdr->file_format;

$this->dataPage['js'] .= <<<HTML
            var rec = {
                directory: '{$dir}',
                format: '{$format}'
            };
            $(document).ready(function() {
                $("#jquery_jplayer").jPlayer({
                    swfPath: "js/player",
                    supplied: rec.format,
                    wmode: "window"
                });
            });
HTML;

//$sort[$this->sort]  = "data-sort=\"{$this->sort}\" data-desc=\"{$this->desc}\"";
?>
<div class="filters clear_fix">
    <form method="get" action="" class="of_h">
        <div class="filter fl_l sep">
            <div class="label">Дата</div>
            <div class="labeled">
                <input name="fromdate" type="text" autocomplete="off" value="<?php echo $this->fromdate->format('Y-m-d H:i'); ?>" class="datetimepicker" >
                —
                <input name="todate" type="text" autocomplete="off" value="<?php echo $this->todate->format('Y-m-d H:i'); ?>" class="datetimepicker" >
            </div>
        </div>
        <div class="filter fl_l sep">
            <div class="label">Оператор</div>
            <div class="labeled">
                <select name="oper" size="1"  default="<?php echo $this->oper; ?>">
                    <?php echo QueueAgents::showOperslist(); ?>
                </select>
            </div>
        </div>

        <div class="filter fl_l sep">
            <div class="label">Телефон</div>
            <div class="labeled">
                <input name="src" placeholder="Источник" autocomplete="off" style="width: 8em;" value="<?php echo html($this->src); ?>">
                —
                <input name="dst" placeholder="Назначение" autocomplete="off" style="width: 8em;" value="<?php echo html($this->dst); ?>">
            </div>
        </div>

        <div class="filter fl_l sep">
            <div class="label">Звонки</div>
            <div class="labeled">
                <select name="coming" size="1"  default="<?php echo $this->coming; ?>">
                    <option value="" selected="selected">все звонки</option>
                    <option value="1">входящий</option>
                    <option value="2">исходящий</option>
                </select>
            </div>
        </div>

        <div class="filter fl_l sep">
            <div class="label">Комментарий</div>
            <div class="labeled">
                <input name="comment" placeholder="Комментарий" autocomplete="off" style="width: 10em;" value="<?php echo html($this->comment); ?>">
            </div>
        </div>
        <div class="filter fl_l sep">
            <div class="label">Показать</div>
            <div class="labeled">
                <select name="limit" size="1"  default="<?php echo $this->limit; ?>">
                    <option value="" selected="selected">30</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>

        <div class="filter fl_l but_search">
            <input type="submit" name="search" id="button-search" value="Показать" />
        </div>

        <input type="hidden" name="sort" value="<?php echo $this->sort; ?>" />
        <input type="hidden" name="desc" value="<?php echo $this->desc; ?>" />
        <input type="hidden" name="offset" value="<?php echo $this->offset; ?>" />
    </form>
</div>

<div class="control_bar clear_fix bigblock of_h">
    <div class="fl_l" style="padding-right: 15px;">
        Найдено: <?php echo $this->count ?>
    </div>
    <div class="pg_pages fl_r">
        <?php
        echo ACPagenator::html($this->count, $this->offset, $this->limit);
        ?>
    </div>
</div>


<div class="clear clear_fix bigblock">
    <table class="grid">
        <thead>
            <tr>
               <!--  data-sort="" data-desc=""  -->
                <th style="width: 60px;">Напр.</th>
                <th <?php  echo Utils::sortable("calldate", $this->sort, $this->desc); ?>  style="width: 150px;">Дата</th>
                <th <?php  echo Utils::sortable("src", $this->sort, $this->desc); ?> style="width: 150px;">Источник</th>
                <th <?php  echo Utils::sortable("dst", $this->sort, $this->desc); ?> style="width: 150px;">Назначение</th>
                <th style="width: 150px;">Оператор</th>
                <th style="width: 135px;">Запись</th>
                <th <?php  echo Utils::sortable("duration", $this->sort, $this->desc); ?> style="width: 70px;">Время</th>
                <th <?php  echo Utils::sortable("comment", $this->sort, $this->desc); ?> style="">Комментарий</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($this->rows as $row) {
                /* @var $row Cdr */
                ?>
                <tr callid="<?php echo $row->id; ?>">
                    <td class="coming_img"><div class="coming_<?php echo $row->getComing(); ?>"></div></td>
                    <td><?php echo $row->calldate ?></td>
                    <td><?php echo html($row->src); ?></td>
                    <td><?php echo html($row->getDst()); ?></td>
                    <td><?php echo html($row->getOperatorCode()); ?></td>
                    <td>
                        <div class="fl_l"><a href="<?php echo $row->getFile(); ?>" target="_blank" ><img src="images/b_save.png" /></a></div>
                        <div class="player_button fl_l b_play" style="margin-left: 5px;">
                            <input type="hidden" value="<?php echo $row->uniqueid; ?>" />
                        </div>
                        <div class="slider fl_l"></div>
                    </td>
                    <td><?php echo $row->getTime(); ?></td>
                    <td class="comment grid_edit"><span><?php echo html($row->comment); ?></span></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>

<div id="comment_edit" class="hidden">
    <input type="text" name="comment" class="edit_box" value="" autocomplete="off" />
</div>

<div id="jquery_jplayer" class="jp-jplayer"></div>
<div id="jp_container_1" class="jp-audio">
    <div class="jp-type-single">
        <div class="jp-gui jp-interface">
            <div class="jp-progress">
                <div class="jp-seek-bar">
                    <div class="jp-play-bar"></div>
                </div>
            </div>

            <div class="jp-time-holder">
                <div class="jp-current-time"></div>
            </div>
        </div>
        <div class="jp-no-solution">
            <span>Update Required</span>
            To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
        </div>
    </div>
</div>
