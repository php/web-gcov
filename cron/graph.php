<?php

// File to generate graphs for a weekly or monthly basis

/*
Inportant Notes:

* For the sake of simplicity, the SQL selects the highest number of each of the four criteria with the limitation of one data point allowed per day.  This also keeps the graph from becoming too messy with too many data points.

* Graphs can only be generated for the period of time if at least more than one data point exist during that time frame.  Otherwise an error would occur when starting the graph creation process.

*/

if(!defined('CRON_PHP'))
{
  echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	  exit;
}

include_once 'config.php';

include_once 'lib/jpgraph/jpgraph.php';

include_once 'lib/jpgraph/jpgraph_line.php';

/*
support for:
weekly ( 7 days )
monthly ( 30 days )
*/

$days = 0;

if($graph_mode == 'monthly')
{
	$graph_mode_text = 'Month';
	$graph_days = 30;
}
else if($graph_mode == 'weekly')
{
	$graph_mode_text = 'Week';
	$graph_days = 7;
}

// Make sure an acceptable graph mode is selected before continuing
if($graph_days > 0)
{

	try
	{
		$sql = 'SELECT build_date, max( build_percent_code_coverage ) , max( build_numwarnings ) , max( build_numfailures ) , max( build_numleaks ) FROM local_builds WHERE DATE_SUB( CURDATE( ) , INTERVAL ? DAY ) <= build_date AND version_id=? GROUP BY build_date';
		$stmt = $mysqlconn->prepare($sql);
		$stmt->execute(array($graph_days, $version_id));
	}
	catch(PDOException $e)
	{
	// if error occurs log, outptu and ensure the version_id is not legimate
	//$version_id = 0;
	}

	$data_numrows = 0;

	$data_x = array();

	$data_y = array();
	$data_y['warnings'] = array();	// stores number of warnings
	$data_y['codecoverage'] = array();	// stores number of errors
	$data_y['failures'] = array();	// stores number of test failures
	$data_y['memleaks'] = array();	// stores nunber if memory leaks

	$current_date = date('M d');

	$graph_array = array(
        array('name' => 'codecoverage',
							'title' => 'Code Coverage',
							'yformat' => 'percent'),
				array('name' => 'failures',
							'title' => 'Test Failures',
							'yformat' => 'integer'),
        array('name' => 'memleaks',
							'title' => 'Memory Leaks',
							'yformat' => 'integer'),
				array('name' => 'warnings',
							'title' => 'Compile Warnings',
							'yformat' => 'integer')
			);

	while($row = $stmt->fetch())
	{
		list($build_date, $build_codecoverage, $build_numwarnings, $build_numfailures, $build_numleaks) = $row;

		list($year, $month, $day) = explode('-', $build_date);

		$data_x[] = $day;

		// Code Coverage less then 0 means could not be located
		if($build_codecoverage >= 0)
			$data_y['codecoverage'][] = $build_codecoverage;

		$data_y['failures'][] = $build_numfailures;
		$data_y['memleaks'][] = $build_numleaks;
		$data_y['warnings'][] = $build_numwarnings;

		$data_numrows++;

	} // Cycle through the results

	// Ensure more than one data row exist for the period of time
	if($data_numrows > 1)
	{
		// Create the graphs
		foreach($graph_array as $curgraph)
		{
			// Ensure individual graph has enough data to be drawn
			if(count($data_y[$curgraph['name']]) > 1)
			{
				$graph_filename = $outdir.DIRECTORY_SEPARATOR.'graphs'.DIRECTORY_SEPARATOR.$curgraph['name'].'_'.$graph_mode.'.png';

				// Create the graph. These two calls are always required
				$graph = new Graph(400, 300, 'auto');

				$graph->img->SetMargin(40 ,40 ,40, 40);
	
				$graph->SetScale('textlin');
				$graph->title->Set($curgraph['title'].' for the '
					.$graph_mode_text. ' Ending '.$current_date);

				$graph->SetShadow();
				//$graph->yscale->SetGrace(10,10); 

				// Create the linear plot on the Y axis
				$lineplot= new LinePlot($data_y[$curgraph['name']]); 
			
				$lineplot->SetColor('blue');

				// Add the plot to the graph
				$graph->Add($lineplot);
				$graph->xaxis->SetTitle('Day');
				$graph->xaxis->SetTickLabels($data_x);
				$graph->xaxis->SetTextTickInterval(1);

				if($curgraph['yformat'] == 'percent')
					$graph->yaxis->SetTitle('Percentage of '.$curgraph['title']);
				else // For now else assume integer
					$graph->yaxis->SetTitle('Number of '.$curgraph['title']);

				// Output the graph to a file location
				$graph->Stroke($graph_filename);

				} // End check for number of data points on the graph
				else
					echo "graph.php: graph of mode $curgraph[name] had an insufficient number of valid data points.\n";
		} // End looping each graph type

		echo "graph.php: completed this graph mode\n";
	} // End check that number of rows > 1
	else
	{
		//echo 'insufficient number of data rows'."\n";
		echo "graph.php: has insufficent data rows to make these graphs for $version_id \n";
	}
}
else
{
	echo "graph.php was not called with an acceptable mode\n";
}
