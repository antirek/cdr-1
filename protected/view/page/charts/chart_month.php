<?php
/**
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  (c) 2013, AC
 */
/* @var $this QueueController */
?>


<div class="filters clear_fix">
    <form method="get" action="" class="of_h">
        <div class="filter fl_l sep">
            <div class="label">Дата</div>
            <div class="labeled">
                <input name="fromdate" type="text" autocomplete="off" value="<?php echo $this->fromdate->format('d.m.Y'); ?>" class="datepicker" >
            </div>
        </div>
        <div class="filter fl_l sep">
            <div class="label">Очередь</div>
            <div class="labeled">
                <?php echo Queue::showMultiple("queue[]", $this->queue); ?>
            </div>
        </div>

        <div class="filter fl_l sep">
            <div class="label">мобильные</div>
            <div class="labeled" style="padding: 3px 0px 4px 0px;">
                <input name="mob" type="checkbox" value="1" <?php if ($this->mob) echo "default=\"1\""; ?> />
            </div>
        </div>


        <div class="filter fl_l">
            <div class="labeled">
                <input type="submit" name="search" id="button-search" class="button button-search" value="Показать" />
            </div>
        </div>
        <input type="hidden" name="section" value="<?php echo $this->getSection(); ?>" />
    </form>
</div>

<script type="text/javascript">
    var chart_title = 'месячный отчет за <b><?php echo $this->fromdate->format('F Y'); ?></b>г.';
    var chart;
    $(document).ready(function() {
        chart = new Highcharts.Chart({
            chart: {renderTo: 'container', type: 'column'},
            title: {text: chart_title },
            legend: {layout: 'vertical', align: 'right', verticalAlign: 'top', backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColorSolid) || 'white', borderColor: '#CCC', borderWidth: 1, x: 0, y: 0, floating: true, shadow: true },
            xAxis: {
                categories: <?php echo json_encode($this->highcharts[0]); ?>,
                lineWidth: 1,
            },
            yAxis: {allowDecimals: false, min: 0, lineWidth: 1, gridLineDashStyle: 'longdash', title: {text: ''}, stackLabels: { enabled: true, style: {fontWeight: 'bold', color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'}  } },
            tooltip: {
                formatter: function() {
                    return '<b>' + this.x + '</b><br/>' +
                            this.series.name + ': ' + this.y + '<br/>';
                }
            },
            plotOptions: { column: {borderColor: '#303030', grouping: false, shadow: true }, series: { animation: { duration: 0  } } },
            series: [
                {
                    name: 'Поступило звонков',
                    data: <?php echo json_encode($this->highcharts[1]); ?>,
                    color: '#B64245',
                    dataLabels: {enabled: true, x: 0, y: 0, style: {fontSize: '13px', fontFamily: 'Verdana, sans-serif', fontWeight: 'bold', color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'} },
                },
                {
                    name: 'Принято звонков',
                    data: <?php echo json_encode($this->highcharts[2]); ?>,
                    color: "#D98962"
                }
            ],
        });
        $("#highcharts-wrap tspan").last().hide();
    });
</script>


<div class="clear clear_fix bigblock">
    <table class="grid" >
        <tr>
            <td class="head" style="width: 400px;">ВСЕГО:</td>
            <td><?php echo $this->totalResult['total']; ?></td>
        </tr>
        <tr>
            <td class="head">Потеряно:</td>
            <td><?php echo $this->totalResult['abandoned']; ?></td>
        </tr>
        <tr>
            <td class="head">Переведено:</td>
            <td><?php echo $this->totalResult['transfered']; ?></td>
        </tr>
        <tr>
            <td class="head">Успешно завершено:</td>
            <td><?php echo $this->totalResult['complete']; ?></td>
        </tr>
        <tr>
            <td class="head">Клиенты, не дождавшиеся ответа, ждали в среднем:</td>
            <td><?php echo @round($this->totalResult['average_time'] / $this->totalResult['abandoned'], 1); ?> сек.</td>
        </tr>
        <tr>
            <td class="head">В среднем клиенты ждут:</td>
            <td><?php echo @round($this->totalResult['average_time_all'] / $this->totalResult['total'], 1); ?> сек.</td>
        </tr>
        <tr>
            <td class="head">В среднем разговор длится:</td>
            <td><?php echo @round($this->totalResult['average_time_talk'] / ($this->totalResult['complete'] + $this->totalResult['transfered']), 1); ?> сек.</td>
        </tr>
    </table>
</div>

<div id="highcharts-wrap" class="clear clear_fix bigblock" style="width: 100%">
    <div id="container" style="width: 1240px; height: 400px;"></div>
</div>