<!DOCTYPE html>
<html
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>picili auto dashboard</title>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

        <!-- Optional theme -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

        <!-- Latest compiled and minified JavaScript -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

        <!-- d3 charts -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>




    </head>
    <body>


        <div class="container">

            <h1># num of pending tasks: #{{$aStats['iQueued']}}</h1>
            <table class="table">
                <tr>
                    <td>#{{$aStats['tasks']['0_5mins']}}</td><td> 0-5 mins old</td>
                </tr>
                    <tr>
                        <td>#{{$aStats['tasks']['5_10mins']}}</td><td>5-10 mins old</td>
                    </tr>

                <tr>
                    <td>#{{$aStats['tasks']['10_15mins']}}</td><td>10-15 mins old</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks']['15_30mins']}}</td><td>15-30 mins old</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks']['30_45mins']}}</td><td>30-45 mins old</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks']['45_60mins']}}</td><td>45-60 mins old</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks']['60_plusmins']}}</td><td>60+ mins old</td>
                </tr>

            </table>

            <h2>per queue</h2>
            <table class="table">
                @foreach($aStats['sa_queues'] as $sQueue)
                    <tr>
                        <td>{{$sQueue['name']}}</td>
                        <td>{{$sQueue['i_count']}}</td>
                    </tr>
                @endforeach
            </table>

            <h1>other stats</h1>
            <table class="table">
                <tr>
                    <td>#{{$aStats['iFiles']}}</td><td>total picili files</td>
                </tr>
                <tr>
                    <td>#{{$aStats['iDropboxFiles']}}</td><td>dropbox files</td>
                </tr>

                <tr>
                    <td>#{{$aStats['iUsers']}}</td><td>users</td>
                </tr>

                <tr>
                    <td>[#]</td><td>tags</td>
                </tr>
            </table>

            <h1>times seen by processor</h1>
            <table class="table">
                <tr>
                    <td>#{{$aStats['tasks_times_seen']['2']}}</td><td>2</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks_times_seen']['3']}}</td><td>3</td>
                </tr>

                <tr>
                    <td>#{{$aStats['tasks_times_seen']['3_plus']}}</td><td> >3</td>
                </tr>
            </table>

            <h1>processor logs (#{{$aStats['i_processed']}})</h1>

            <table class="table">
                @foreach($aStats['sa_processors'] as $sProcessor)
                    <tr>
                        <td>{{$sProcessor['name']}}</td>
                        <td>{{$sProcessor['i_count']}}</td>
                    </tr>
                @endforeach
            </table>

            <h2>processor timeseries</h2>

    		<p>items processed per minute</p>

            <script type="text/javascript">
                google.charts.load("current", {packages:['corechart']});
                google.charts.setOnLoadCallback(drawChart);
                function drawChart() {

                  var data = new google.visualization.arrayToDataTable(
                    <?php echo json_encode($aStats['timedata_chart']); ?>
                    );

                  var view = new google.visualization.DataView(data);

                  view.setColumns([0, 1,
                                   { calc: "stringify",
                                     sourceColumn: 1,
                                     type: "string",
                                     role: "annotation" },
                                   2]);

                  var options = {
                    title: "Tasks processed per minute",

                    bar: {groupWidth: "95%"},
                    legend: { position: "none" },
                  };
                  var chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
                  chart.draw(view, options);
                }
            </script>

            <div id="columnchart_values" style="width: 100%; height: 300px;"></div>

            <a class="btn btn-primary" role="button" data-toggle="collapse" href="#queue-log-timeseries" aria-expanded="false" aria-controls="collapseExample">
                Show all data
            </a>

            <div class="collapse" id="queue-log-timeseries">
                <div class="well">
                    <table class="ui celled table">
                        <thead>
                            <tr>
                                <th>date</th>
                                <th>done</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aStats['timedata'] as $oStat)
                                <tr>
                                    <td>{{$oStat['date']}}</td>
                                    <td>{{$oStat['count']}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            <p>
            foreach processor, average taskk completion over last periods
            - minute
            - 3 minutes
            - 5 minutes
            - 10 minutes
            - 20 minutes
            - 30 minutes
            - 60 minutes
            - 6 hours
            - 12 hours
            - 24 hours
            </p>
            <h1>todo - error stuff</h1>
            <p># of errors</p>
        </div>
    </body>
</html>
