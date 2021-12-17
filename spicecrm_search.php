<?php
/*
DYNAMIC EVENTS-TABLE DEPENDING ON SEARCH RESULTS
*/

//including wp-load
require_once(dirname(__FILE__, 4).'/wp-load.php');

//concat url for HTTP-request
$args = array(
    'headers' => array(
        'Authorization' => 'Basic ' . base64_encode(get_option('user') . ':' . get_option('password'))
    )
);
$url = get_option('url') . '/api/module/Events?searchterm=*'.$_GET['input_search'].'*&limit=200';
//GET request from API
$request = wp_remote_get($url, $args);
//extracting the body from the requested data
$jsonfile = wp_remote_retrieve_body($request);
//turning request body from string to array
$event_array = json_decode($jsonfile, true);
//addressing subarray list (where the events are listed)
$events = $event_array["list"];
//variable to count event in table
$x_of = sizeof($events,0);
//variable for details-link (without ID yet)
$details_url = rtrim(get_permalink(get_page_by_path('detail')),'/').'?id=';

//declaring searchterm as variable
$searchterm = $_GET['input_search'];

//declaring currentpage as variable
$currentpage = $_GET['input_currentpage'];

//rows of result-set
$numrows = count($events);
//rows per page
$rowsperpage = 5;
//total pages
$totalpages = ceil($numrows / $rowsperpage);


// if current page is greater than total pages...
if ($currentpage > $totalpages) {
    // set current page to last page
    $currentpage = $totalpages;
} // end if
// if current page is less than first page...
if ($currentpage < 1) {
    // set current page to first page
    $currentpage = 1;
} // end if

// the offset of the list, based on current page
$offset = ($currentpage - 1) * $rowsperpage;
//targeted results from array $events
$results = array_slice($events,$offset,$rowsperpage);

global $wp;
$targeturl =  get_permalink( get_the_ID() );

//table header
 ?>
<style>
    table td, table th, .wp-block-table td, .wp-block-table th ,table ,tbody ,thead, table tr, .table>thead>tr>td{
        border: 0px;
        word-break: unset;
        vertical-align: unset; !important

    }
</style>
<table class="table table-striped">
    <thead>
    <tr>
        <th>Kurs</th>
        <th>Plätze</th>
        <th>Kursbeginn</th>
        <th>Kursende</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
<?php
//table body loop
if(!empty($results)){
    foreach($results as $result){
        $id = $result['id'];
?>
    <tr id="<?php echo $result['id']; ?>">
        <td><?php echo $result['name']; ?></td>
        <td><?php echo $result['capacity_participants']; ?></td>
        <td><?php echo date('d.m.Y, G:i',strtotime($result['date_start'])); ?></td>
        <td><?php echo date('d.m.Y, G:i',strtotime($result['date_end'])); ?></td>
        <td><a href="<?php echo $details_url.$id ?>"><input type="button" class="btn btn-link" value="Details"></a></td>
    </tr>
<?php
    }
}
else{
    echo "<tr><td colspan='5'>Keine Ergebnisse gefunden!</td></tr>";
}
?>
    </tbody>
</table>
<div>
<?php echo '<div class="col-md-6" style="float: right;"><p style="text-align: right">'.$x_of.' von '.$_SESSION['y_of'].' Ergebnissen</p></div>';

/******  build the pagination links ******/
// if not on page 1, don't show back links
     echo '<div class="col-md-6" style="float: left;">';
if ($currentpage > 1) {
    // show << link to go back to page 1
    echo " <a href='".$targeturl."?currentpage=1&searchterm=$searchterm'><<</a>";

    // get previous page num
    $prevpage = $currentpage - 1;
    // show < link to go back to 1 page
    echo "<a href='".$targeturl."?currentpage=$prevpage&searchterm=$searchterm'><</a> ";
} // end if

// range of num links to show
$range = 3;

// loop to show links to range of pages around current page
for ($x = ($currentpage - $range); $x < (($currentpage + $range)  + 1); $x++) {
    // if it's a valid page number...
    if (($x > 0) && ($x <= $totalpages)) {
        // if we're on current page...
        if ($x == $currentpage) {
            // 'highlight' it but don't make a link
            echo " [<b>$x</b>] ";
            // if not current page...
        } else {
            // make it a link
            echo "<a href='".$targeturl."?currentpage=$x&searchterm=$searchterm'>$x </a>";
        } // end else
    } // end if
} // end for

// if not on last page, show forward and last page links
if ($currentpage != $totalpages) {
    // get next page
    $nextpage = $currentpage + 1;
    // echo forward link for next page
    echo "<a href='".$targeturl."?currentpage=$nextpage&searchterm=$searchterm'>></a> ";
    // echo forward link for lastpage
    echo "<a href='".$targeturl."?currentpage=$totalpages&searchterm=$searchterm'>>></a> ";
    echo "</div>";
} // end if
/****** end build pagination links ******/


?>
</div><br>


