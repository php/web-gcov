<?php

include_once 'config.php';

include_once 'lib/jpgraph/jpgraph.php';

include_once 'lib/jpgraph/jpgraph_line.php';

// todo: graph is only created if build occurs during multiple week days

// todo: a new week is determined if the current day of the week  is Sunday

try
{
	$sql = 'SELECT build_date, max( build_numerrors ) , max( build_numwarnings ) , max( build_numfailures ) , max( build_numleaks ) FROM builds WHERE DATE_SUB( CURDATE( ) , INTERVAL 7 DAY ) <= build_date AND version_id=? GROUP BY build_date';
        $stmt = $mysqlconn->prepare($sql);
        $stmt->execute(array($version_id));
}
catch(PDOException $e)
{
	// if error occurs log, outptu and ensure the version_id is not legimate
	//$version_id = 0;
}

// todo:

$data_numrows = 0;

$data_x = array();

//$data_y = array();

$data_y = array();
$data_y['warnings'] = array();	// stores number of warnings
$data_y['errors'] = array();	// stores number of errors
$data_y['failures'] = array();	// stores number of test failures
$data_y['memleaks'] = array();	// stores nunber if memory leaks

//$data_x[] = '2006-07-26';
//$data_y[] = 10;

$current_date = date('Y_m_d');

//$graph_array = array('errors','failures','memleaks','warnings');

$graph_array = array(
        array('name' => 'errors',
		'title' => 'Compile Errors'),
	array('name' => 'failures',
		'title' => 'Test Failures'),
        array('name' => 'memleaks',
                'title' => 'Memory Leaks'),
	array('name' => 'warnings',
                'title' => 'Compile Warnings')
);

while($row = $stmt->fetch())
{
	list($build_date, $build_numerrors, $build_numwarnings, $build_numfailures, $build_numleaks) = $row;

	$data_x[] = substr($build_date,0,10);
	//$data_x[] = '2006-07-27';
	$data_y['errors'][] = $build_numerrors;
	$data_y['failures'][] = $build_numfailures;
	$data_y['memleaks'][] = $build_numleaks;
	$data_y['warnings'][] = $build_numwarnings;

	$data_numrows++;
}

if($data_numrows > 1)
{
// Create the graphs
	foreach($graph_array as $curgraph)
	{
	//echo $graph_single."\n";

		$graph_filename = $outdir.DIRECTORY_SEPARATOR.'graphs'.DIRECTORY_SEPARATOR.$curgraph['name'].'_'.$current_date.'.png';

		// Create the graph. These two calls are always required
		$graph = new Graph(400,300,"auto");

		$graph->img->SetMargin(40,40,40,40);
	
		$graph->SetScale("textlin");
		$graph->title->Set($curgraph['title'].' for Week Ending '.$current_date);

		$graph->SetShadow();

		//$graph->yscale->SetGrace(10,10); 

		// Create the linear plot
		$lineplot=new LinePlot($data_y[$curgraph['name']]); // resolves to the y data for the current element
		$lineplot->SetColor('blue');

		// Add the plot to the graph
		$graph->Add($lineplot);
		$graph->xaxis->SetTitle('Date');
		$graph->xaxis->SetTickLabels($data_x);
		$graph->xaxis->SetTextTickInterval(1);

		$graph->yaxis->SetTitle('Number of '.$curgraph['title']);
		//$graph->yaxis->SetTextTickInterval(10);


		//$graph->SetLegends($xdata);

		// Output the graph to a file location
		$graph->Stroke($graph_filename);

	}
/*
	$outfilename = $outdir.DIRECTORY_SEPARATOR.'graph.inc';

	// todo: possibly check that all 4 graphs were actually created
	if(file_exists($outfilename))
	{
		$contents = file_get_contents($outfilename);
	}
	else
	{
		$contents = '';
	}
	
	// Output the new contents at the top and add a new line
	$contents = $current_date."\n".$contents;
	file_put_contents($outfilename, $contents);
	echo 'out file: '.$outfilename."\n";
*/

} // End check that number of rows > 1
/*
else
{
	echo 'insufficient number of data rows'."\n";
}*/
