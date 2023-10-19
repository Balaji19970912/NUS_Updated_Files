<?php
include "dbconn.php";
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
// if(empty($_POST['clientcompany'])){
// 	echo 'please '
// }

// echo "<pre>";
// echo "Value = ".$_POST['mwhtrade'];


// echo "POST = ".$_POST['mw'];
// echo "Empty = ".empty($_POST['mw']);

function numberreturnfloat($value){
	$toremovecomma = floatval(preg_replace('/[^\d. ]/', '', $value));
	return $toremovecomma;
}

$checkingforEmpty = empty($_POST['mw']);

$contractId = "SELECT * FROM nus_supply_contract WHERE supplierId=".$_POST['supplierid']."";
$contractId = mysqli_query($conn, $contractId);
$singlerow = array();
while ($row = mysqli_fetch_assoc($contractId)) {
	$singlerow[] = $row;
}


/// writing code for checking total consumption ///

$sqlQueryOne = "SELECT * FROM enter_trade WHERE supplycontractid = '".$singlerow[0]['contract_id']."' 
AND tradevalue = '".$_POST['ddlyear']."' AND clientId = '".$_POST['clientsId']."' AND tradeexecuted ='Executed';";

$resultMW = mysqli_query($conn,$sqlQueryOne);

$rcsMW = mysqli_num_rows($resultMW);

$totalconsumption = numberreturn($singlerow[0]['totalAnualConsumption']);

// echo "<pre>";
// print_r($resultMW);
// echo $rcsMW;

global $final;
if($rcsMW >0) {
	$add = numberreturn($_POST['tradevolume']);
	
	while($rowsMW = mysqli_fetch_assoc($resultMW)) {
		 $final += $rowsMW['tradevolume'];
	}
	$final += $add;
} else {
	$final = numberreturn($_POST['tradevolume']);
	// echo "<pre>";
	// echo "Final = ".$final;
}
// echo "<pre>";
// echo "Total = ".$totalconsumption;

// die();

if($final>$totalconsumption && empty($_POST['mwhtrade'])){
	if(empty($_POST['mw'])) {
		$_SESSION['errorconsumption'] = time();
		header("location:entertrade.php");
		die();
	}
}


$getingclinches = "SELECT * FROM nus_tradeperiods WHERE tradePerId=".$_POST['tranchclick']."";
$getingclinches = mysqli_query($conn, $getingclinches);
$clincherow = array();
while ($rows = mysqli_fetch_assoc($getingclinches)) {
	$clincherow[] = $rows;
}
$quartval = '';
$tradeper = '';
foreach ($_POST['Tradeperiod'] as $key => $value) {
	$tradeper = $value;
}
$availableclick = 0;
$definedclick = 0;
$updateId = 0;
if($tradeper == 'Calendar Yearly'){
	$definedclick = $clincherow[0]['clicktracnches'];
	$getdata = array();

	// echo "I'm here!";
	// echo $tradeper;
	
	$getcountofclick = "SELECT clicks,calenderId FROM nus_calenderyear WHERE tradeId='".$_POST['tranchclick']."' AND supplierid='".$_POST['supplierid']."' AND calenderyear='".$_POST['ddlyear']."';";
	$result = $conn->query($getcountofclick);

	if ($result->num_rows > 0) {
	  while($row = $result->fetch_assoc()) {
	    $getdata[] = $row['clicks'];
	    $updateId = $row['calenderId'];
	  }
	} 
	
	if(count($getdata) == 0){
		// echo 'test';
	}else{
		$availableclick = array_sum($getdata);
	}
}
if($tradeper == 'Calendar Monthly'){
	$definedclick = $clincherow[0]['clicktracnches']*12;
	$getdata = array();
	$getcountofclick = "SELECT clicks,monthId  FROM nus_calendermonth WHERE TradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND year='".$_POST['ddlyear']."' AND month='".$_POST['month']."'";
	$result = $conn->query($getcountofclick);

	if ($result->num_rows > 0) {
	  while($row = $result->fetch_assoc()) {
	    $getdata[] = $row['clicks'];
	    $updateId = $row['monthId'];
	  }
	} 
	
	if(count($getdata) == 0){
		// echo 'test';
	}else{
		$availableclick = array_sum($getdata);
	}
}
if($tradeper == 'Calendar Quarterly'){
	
	$quarters = '';
	foreach ($_POST['Quarter'] as $key => $value) {
		$quarters = $value;
	}
	
	$definedclick = $clincherow[0]['clicktracnches']*4;
	$getdata = array();
	$getcountofclick = "SELECT clicks,querterid FROM nus_calenderquarter WHERE tradeid='".$_POST['tranchclick']."' AND supplierid='".$_POST['supplierid']."' AND quarters='".$quarters."' AND yearoftrade='".$_POST['ddlyear']."'";
	$result = $conn->query($getcountofclick);

	if ($result->num_rows > 0) {
	  while($row = $result->fetch_assoc()) {
	    $getdata[] = $row['clicks'];
	    $updateId = $row['querterid'];
	  }
	} 
	
	if(count($getdata) == 0){
		// echo 'test';
	}else{
		$availableclick = array_sum($getdata);
	}
}
if($tradeper == 'Season'){
	$season = '';
	foreach ($_POST['seasonname'] as $key => $value) {
		$season = $value;
	}
	
	$definedclick = $clincherow[0]['clicktracnches']*2;
	$getdata = array();
	$getcountofclick = "SELECT clicks,seasonId FROM nus_season WHERE tradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND season='".$season."'";
	$result = $conn->query($getcountofclick);

	if ($result->num_rows > 0) {
	  while($row = $result->fetch_assoc()) {
	    $getdata[] = $row['clicks'];
	    $updateId = $row['seasonId'];
	  }
	} 
	
	if(count($getdata) == 0){
		// echo 'test';
	}else{
		$availableclick = array_sum($getdata);
	}
}
$totalconsumption = numberreturn($singlerow[0]['totalAnualConsumption']);
// echo gettype($totalconsumption);
$allmonts = explode(',', $singlerow[0]['allmonts']);
$consumptionmonth = explode('|', $singlerow[0]['hedgeconsumption']);
$estimationconsumption = explode('|', $singlerow[0]['consumptionmonth']);
$headconsumption = array();
$currentconsumption = 0;
$firstdate = array();
$lastdate = array();
$yearcount = count($allmonts)/12;
$yearremaining = count($allmonts)%12;
$yearrmai = array();
$year = 1;
for ($k=0; $k < ($yearcount*12); $k++) { 
    if($k%12 == 0){
    $firstdate[] =$allmonts[$k];
    if($k!=0){
        $year = $year+1;
    }
  	}
    if(((12*$year) - $k) == 1){
        $lastdate[] = $allmonts[$k];
    }
}
$periodtime =  array();
$years = array();
for($j=0;$j<count($lastdate);$j++){
    $periodtime[] = $firstdate[$j].','.$lastdate[$j];
    $val = explode('-', $lastdate[$j]);
    $years[] = $val[0];
}
$yearscnt = $_POST['ddlyear'];
$key = array_search($yearscnt, $years);
$period = implode(',', $periodtime);
// echo "<pre>";
// echo "Ille";
// print_r($period);
$openconsuption = array();
$consumptionallmonths = explode('|', $singlerow[0]['consumptionmonth']);
// print_r($period);
if($tradeper == 'Calendar Yearly'){

	// echo "I'm here!";
	// echo $tradeper;
		
		$periods ='';
		$year = '';
		$getcountofclick = "SELECT timeperiod,calenderyear FROM nus_calenderyear WHERE tradeId='".$_POST['tranchclick']."' AND supplierid='".$_POST['supplierid']."' AND calenderyear='".$_POST['ddlyear']."'";
		$result = $conn->query($getcountofclick);

		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $periods = $row['timeperiod'];
		    $year = $row['calenderyear'];
		  }
		}
		// echo "<pre>";
		// echo "After";
		// echo $periods;

		$startDate = $allmonts[0];
		$endDate = $allmonts[count($allmonts)-1];

		$newDate = array();
		array_push($newDate,$startDate);
		array_push($newDate,$endDate);

		$period11 = implode(",",$newDate);


		// $explode = explode(',',$period); imp
		$explode = explode(',',$period11);

		$count = count($explode);
		$varmonths = getMonthsInRange($explode[0],$explode[$count-1]);
		// echo "Months = ";
		// print_r($varmonths);
		$months = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
		$crrc = array();
		$yeacc = array();
		$mnts = array();
		foreach ($varmonths as $key => $valuem) {
			$yeacc[] = $valuem['year'].'-'.$months[$valuem['month']-1];
		}
		// print_r($yeacc);

		$amt = array();
		$datesranges =explode(',',$periods);
		
		$datesrange =  getMonthsInRange($datesranges[0],$datesranges[1]);
		$yearsrange = array();
		
		foreach ($datesrange as $key => $valuesss) {
			$yearsrange[] = $valuesss['year'].'-'.$months[$valuesss['month']-1];
			
		}
		// echo "<pre>";
		// print_r($datesrange);
		// $cons =  array();

		// foreach ($consumptionallmonth as $key => $value) {
			
		// }
		// print_r($yearsrange);
		
		// print_r($yeacc);
		
		// print_r($consumptionmonth);
		$tradev = numberreturn($_POST['tradevolume']);
		
		$percentageConsu = $_POST['percentagetrade'];
		if(empty($_POST['mw'])) {
			$permonth = (float)$tradev/12;
		} else {
			$permonth = (float)$_POST['mw'];
		}
		
		// echo "<pre>"; 
		// print_r($consumptionmonth);
		// $ii = 0;
		foreach ($consumptionmonth as $key => $valuess) {
			$explodeconsum = explode('-', $valuess);
			$currentest = explode('-',$estimationconsumption[$key]);
			// echo"<pre>";
			// print_r($currentest) ;
			$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
			
			
			if(in_array($keyval, $yeacc)){
				
				$exl = explode('-', $keyval);
				// if(in_array($keyval, $yearsrange) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
				if(in_array($keyval, $yearsrange) && empty($_POST['percentagetrade'])) {
					if(empty($_POST['mw'])) {
						$sum = (float)$explodeconsum[2]+(float)$permonth;
					} else {
						switch($explodeconsum[0]) {
							case 'Jan':
							case 'Mar':
							case 'May':
							case 'July':
							case 'Aug':
							case 'Oct':
							case 'Dec': $days = 31; break;
							case 'Apr':
							case 'Jun':
							case 'Sep':
							case 'Nov': $days = 30; break;
							case 'Feb': if((((int)$explodeconsum[1] % 4 == 0) && ((int)$explodeconsum[1] % 100!= 0)) || ((int)$explodeconsum[1]%400 == 0)) { $days = 29;} else { $days = 28;} break; //$year can be used
						}
						$sum = $days * $permonth * 24;
					}
					$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$sum;
					// echo $explodeconsum[0];
					// echo $explodeconsum[1];
					// die();
				}
				// else if(in_array($keyval, $yearsrange) && $currentest[2]!=$explodeconsum[2]) {
				else if(in_array($keyval, $yearsrange)) {

					// echo $keyval;
					// echo $valuess;
					// $sum = $explodeconsum[2]+$permonth;
					$sum = (float)$explodeconsum[2]+(((float)$currentest[2]*(float)$percentageConsu)/100);
					$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$sum;
					// $openconsuption[] = 
				}else{
					$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
				}
				
				
			}

			// $ii++;
			
		}
		// echo "<pre>";
		// echo "II value = ".$ii;
		$keyArray = array();
		foreach ($headconsumption as $key => $values) {
			$explodeconsum = explode('-', $values);
			$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
			
			if(in_array($keyval, $yeacc)){
				if(in_array($keyval, $yearsrange)){
					// echo $explodeconsum[2];
					$crrc[] = $explodeconsum[2];
				}
				// $exl = explode('-', $keyval);
				// echo $key = array_search($keyval, $keys);
				
				// echo  $explodeconsum[2];
			}
			
		}

		// echo "<pre>";
		// print_r($headconsumption);
		// print_r($crrc);
		$currentconsumption = array_sum($crrc);

		// echo "<pre>";
		// echo $currentconsumption;

		
}
// echo "<pre>";
// print_r($headconsumption);
if($tradeper == 'Calendar Monthly'){
	if(count($consumptionmonth)<12){

		$getcountofclick = "SELECT month, year  FROM nus_calendermonth WHERE TradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND year='".$_POST['ddlyear']."' AND month='".$_POST['month']."'";
		$result = $conn->query($getcountofclick);
		$month ='';
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $month = $row['month'];
		  }
		} 
		$tradev = numberreturn($_POST['tradevolume']);
		if(empty($_POST['mw'])) {
			$permonth = (float)$tradev;
		} else {
			$permonth = (float)$_POST['mw'];
		}

		// echo "<pre>";
		// echo $tradev;
		// echo "Yo";
		$crrc = array();
		foreach ($consumptionmonth as $key => $valuess) {
				$currentest = explode('-',$estimationconsumption[$key]);
				$explodeconsum = explode('-', $valuess);
				$crrc[] = $explodeconsum[2];
				// if($_POST['ddlyear'] == $explodeconsum[1] && $_POST['month'] == $explodeconsum[0] && $currentest[2]!=$explodeconsum[2]){
				if($_POST['ddlyear'] == $explodeconsum[1] && $_POST['month'] == $explodeconsum[0]){
					if(empty($_POST['mw'])) {
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth);
					} else {
						switch($explodeconsum[0]) {
							case 'Jan':
							case 'Mar':
							case 'May':
							case 'July':
							case 'Aug':
							case 'Oct':
							case 'Dec': $days = 31; break;
							case 'Apr':
							case 'Jun':
							case 'Sep':
							case 'Nov': $days = 30; break;
							case 'Feb': if((((int)$explodeconsum[1] % 4 == 0) && ((int)$explodeconsum[1] % 100!= 0)) || ((int)$explodeconsum[1]%400 == 0)) { $days = 29;} else { $days = 28;} break; //$year can be used
						}
						$sum = $days * $permonth * 24;
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
					}
				}else{
					$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
				}
				
				
		}
		$quarters = $_POST['month'];
		$quartval =$_POST['month'];
		$currentconsumption = array_sum($crrc);
	}else{
		$quarters = $_POST['month'];
		$quartval =$_POST['month'];


		$newperiod = explode(",",$singlerow[0]['allmonts']);
		$countnewPeriod = count($newperiod);
		// echo $newperiod[0];
		// echo $newperiod[$countnewPeriod-1];
		// echo "<pre>";
		$period = $newperiod[0].','.$newperiod[$countnewPeriod-1];
		// echo "period = ".$newperiod;
		// print_r($newperiod);



		$explode = explode(',', $period);


		$count = count($explode);
		$varmonths = getMonthsInRange($explode[0],$explode[$count-1]);
		$months = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
		$crrc = array();
		$yeacc = array();
		$mnts = array();

		// $yy = 0;
		// print_r($varmonths);
		foreach ($varmonths as $key => $valuem) {
			$yeacc[] = $valuem['year'].'-'.$months[$valuem['month']-1];
			// $yy++;
		}
		// echo "<pre>";
		// echo "YY = ".$yy;
		
		$getcountofclick = "SELECT month, year  FROM nus_calendermonth WHERE TradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND year='".$_POST['ddlyear']."' AND month='".$_POST['month']."'";
		$result = $conn->query($getcountofclick);
		$month ='';
		$year = '';
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $month =  $row['month'];
		    $year = $row['year'];
		  }
		} 
		
		// print_r($month);
		$tradev = numberreturn($_POST['tradevolume']);
		if(empty($_POST['mw'])) {
			$permonth = (float)$tradev;
		} else {
			$permonth = (float)$_POST['mw'];
		}

		// echo $tradev;
		$ii=0;

		// echo "<pre>Yeacc";
		// print_r($yeacc);
		foreach ($consumptionmonth as $key => $valuess) {
			$ii++;
				$currentest = explode('-',$estimationconsumption[$key]);
				$explodeconsum = explode('-', $valuess);
				$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
				if(in_array($keyval, $yeacc) ){

					// print_r($keyval);
					// print_r($yeacc);
					// echo "Year = ".$year;

					$keyval = explode('-', $keyval);
					// if($year == $explodeconsum[1] && $keyval[1] == $month && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])){
					if($year == $explodeconsum[1] && $keyval[1] == $month && empty($_POST['percentagetrade'])) {
						// $headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth);
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth);
						} else {
							switch($explodeconsum[0]) {
								case 'Jan':
								case 'Mar':
								case 'May':
								case 'July':
								case 'Aug':
								case 'Oct':
								case 'Dec': $days = 31; break;
								case 'Apr':
								case 'Jun':
								case 'Sep':
								case 'Nov': $days = 30; break;
								case 'Feb': if((((int)$explodeconsum[1] % 4 == 0) && ((int)$explodeconsum[1] % 100!= 0)) || ((int)$explodeconsum[1]%400 == 0)) { $days = 29;} else { $days = 28;} break; //$year can be used
							}
							$sum = $days * $permonth * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
						// echo "<pre>";
						// echo "Ok";
					// } else if($year == $explodeconsum[1] && $keyval[1] == $month && $currentest[2]!=$explodeconsum[2]) {
					} else if($year == $explodeconsum[1] && $keyval[1] == $month) {
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth);
						// echo "<pre>";
						// echo "Not Ok";
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
						// echo "<pre>";
						// echo "Devre";
					}
				}
				
				
		}
		// echo "<pre>";
		// echo "II Value = ".$ii;
		// echo "<pre>Headconsumption Month";
		// if(empty($_POST['percentagetrade'])) {
		// 	echo "True";
		// }
		// print_r($headconsumption);
		foreach ($headconsumption as $key => $values) {
			$explodeconsum = explode('-', $values);
			$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
			if(in_array($keyval, $yeacc)){
				$exl = explode('-', $keyval);
				$crrc[] = $explodeconsum[2];
			}
			
		}
		// print_r($crrc);
		$currentconsumption = array_sum($crrc);
	}
	
}

if($tradeper == 'Calendar Quarterly'){
	if(count($consumptionmonth)<12){
		$quarters = '';
		foreach ($_POST['Quarter'] as $key => $value) {
			$quarters = $value;

		}
		$quartval =$quarters;
		$getcountofclick = "SELECT quarters, yearoftrade  FROM nus_calenderquarter WHERE tradeid='".$_POST['tranchclick']."' AND supplierid='".$_POST['supplierid']."' AND yearoftrade='".$_POST['ddlyear']."' AND quarters='".$quarters."'";
		$result = $conn->query($getcountofclick);
		$quart ='';
		$year = 0;
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $quart = $row['quarters'];
		    $year = $row['yearoftrade'];
		  }
		} 
		$q1 = ['Jan','Feb','Mar'];
	    $q2 = ['Apr','May','Jun'];
	    $q3 = ['July','Aug','Sep'];
	    $q4 = ['Oct','Nov','Dec'];

		$tradev = numberreturn($_POST['tradevolume']);

		if(empty($_POST['mw'])) {
			$permonth1 = (float)$tradev/3;
		} else {
			$permonth1 = (float)$_POST['mw'];
		}

		$permonth = $_POST['percentagetrade'];
		$crrc = array();
		// echo $crrc;
		foreach ($consumptionmonth as $key => $valuess) {
				$explodeconsum = explode('-', $valuess);
				$currentest = explode('-',$estimationconsumption[$key]);
				// echo "<pre>";
				// print_r($currentest);
				$crrc[] = $explodeconsum[2];
				if($quart == 'q1'){
					// if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q1) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q1) && empty($_POST['percentagetrade'])) {
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Jan':
								case 'Mar': $days = 31; break;
								case 'Feb': if((((int)$explodeconsum[1] % 4 == 0) && ((int)$explodeconsum[1] % 100!= 0)) || ((int)$explodeconsum[1]%400 == 0)) { $days = 29;} else { $days = 28;} break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q1) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q1)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q2'){
					// if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q2) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q2) && empty($_POST['percentagetrade'])) {
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Apr':
								case 'Jun': $days = 30; break;
								case 'May': $days = 31; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q2) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q2)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q3'){
					// if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q3) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q3) && empty($_POST['percentagetrade'])) {
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'July':
								case 'Aug': $days = 31; break;
								case 'Sep': $days = 30; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q3) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q3)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q4'){
					// if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q4) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q4) && empty($_POST['percentagetrade'])) {
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Oct':
								case 'Dec': $days = 31; break;
								case 'Nov': $days = 30; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q4) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q4)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				
		}
		$currentconsumption = array_sum($crrc);
	}else{

		// echo "illi";
		$startDate = $allmonts[0];
		$endDate = $allmonts[count($allmonts)-1];

		$newDate = array();
		array_push($newDate,$startDate);
		array_push($newDate,$endDate);

		$period11 = implode(",",$newDate);


		// $explode = explode(',',$period); imp
		$explode = explode(',',$period11);

		$count = count($explode);
		$varmonths = getMonthsInRange($explode[0],$explode[$count-1]);
		$months = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
		$crrc = array();
		$yeacc = array();
		$mnts = array();
		foreach ($varmonths as $key => $valuem) {
			$yeacc[] = $valuem['year'].'-'.$months[$valuem['month']-1];
			
		}
	
		$quarters = '';
		foreach ($_POST['Quarter'] as $key => $value) {
			$quarters = $value;
		}
		$quartval =$quarters;
		$getcountofclick = "SELECT quarters, yearoftrade  FROM nus_calenderquarter WHERE tradeid='".$_POST['tranchclick']."' AND supplierid='".$_POST['supplierid']."' AND yearoftrade='".$_POST['ddlyear']."' AND quarters='".$quarters."'";
		$result = $conn->query($getcountofclick);
		$quart ='';
		$year = 0;
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $quart = $row['quarters'];
		    $year = $row['yearoftrade'];
		  }
		} 
		$q1 = ['Jan','Feb','Mar'];
	    $q2 = ['Apr','May','Jun'];
	    $q3 = ['July','Aug','Sep'];
	    $q4 = ['Oct','Nov','Dec'];

		$tradev = numberreturn($_POST['tradevolume']);
		// echo "Trade volume = ".$tradev;
		if(empty($_POST['mw'])) {
			$permonth1 = (float)$tradev/3;
		} else {
			$permonth1 = (float)$_POST['mw'];
		}
		$permonth = $_POST['percentagetrade'];
		foreach ($consumptionmonth as $key => $valuess) {
				$explodeconsum = explode('-', $valuess);
				$currentest = explode('-',$estimationconsumption[$key]);
				// echo "<pre>";
				// print_r($currentest);
				// echo "<pre>";
				// print_r($explodeconsum);
				$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
				// echo $keyval;
				if(in_array($keyval, $yeacc)){
					$keyval = explode('-', $keyval);
				if($quart == 'q1'){
					// if($year == $explodeconsum[1] && in_array($keyval[1], $q1) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($keyval[1], $q1) && empty($_POST['percentagetrade'])) {
						// $headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Jan':
								case 'Mar': $days = 31; break;
								case 'Feb': if((((int)$explodeconsum[1] % 4 == 0) && ((int)$explodeconsum[1] % 100!= 0)) || ((int)$explodeconsum[1]%400 == 0)) { $days = 29;} else { $days = 28;} break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($keyval[1], $q1) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($keyval[1], $q1)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q2'){
					// if($year == $explodeconsum[1] && in_array($keyval[1], $q2) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($keyval[1], $q2) && empty($_POST['percentagetrade'])) {
						// $headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Apr':
								case 'Jun': $days = 30; break;
								case 'May': $days = 31; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($keyval[1], $q2) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($keyval[1], $q2)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q3'){
					// if($year == $explodeconsum[1] && in_array($keyval[1], $q3) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($keyval[1], $q3) && empty($_POST['percentagetrade'])) {
						// $headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'July':
								case 'Aug': $days = 31; break;
								case 'Sep': $days = 30; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($keyval[1], $q3) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($keyval[1], $q3)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'q4'){
					// if($year == $explodeconsum[1] && in_array($keyval[1], $q4) && $currentest[2]!=$explodeconsum[2] && empty($_POST['percentagetrade'])) {
					if($year == $explodeconsum[1] && in_array($keyval[1], $q4) && empty($_POST['percentagetrade'])) {
						// $headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						if(empty($_POST['mw'])) {
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$permonth1);
						} else {
							switch($explodeconsum[0]) {
								case 'Oct':
								case 'Dec': $days = 31; break;
								case 'Nov': $days = 30; break; //$year can be used
							}
							$sum = $days * $permonth1 * 24;
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(float)$sum);
						}
					}
					// else if($year == $explodeconsum[1] && in_array($keyval[1], $q4) && $currentest[2]!=$explodeconsum[2]){
					else if($year == $explodeconsum[1] && in_array($keyval[1], $q4)){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.((float)$explodeconsum[2]+(((float)$currentest[2]*(float)$permonth)/100));
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
			}
		}

		foreach ($headconsumption as $key => $values) {
			$explodeconsum = explode('-', $values);
			$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
			// echo $keyval;
			if(in_array($keyval, $yeacc)){
				$exl = explode('-', $keyval);
				$crrc[] = $explodeconsum[2];
			}
			
		}
		// print_r($crrc);
		$currentconsumption = array_sum($crrc);
	}
	

}

if($tradeper == 'Season'){
	if(count($consumptionmonth)<12){
		$quarters = '';
		foreach ($_POST['seasonname'] as $key => $value) {
			$quarters = $value;
		}
		$quartval =$quarters;
		$getcountofclick = "SELECT seasonId, yeartrade FROM nus_season WHERE tradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND yeartrade='".$_POST['ddlyear']."' AND season='".$quarters."'";
		$result = $conn->query($getcountofclick);
		$quart ='';
		$year = 0;
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $quart = $row['seasonId'];
		    $year = $row['yeartrade'];
		  }
		} 
		$q2= ['Oct','Nov','Dec'];
		$q1 = ['Jan', 'Feb', 'Mar'];
        $q8 = ['Apr','May','Jun','July','Aug','Sep'];

		$tradev = numberreturn($_POST['tradevolume']); 
		$permonth = (float)$tradev/6;

		$crrc = array();
		foreach ($consumptionmonth as $key => $valuess) {
				$explodeconsum = explode('-', $valuess);
				$currentest = explode('-',$estimationconsumption[$key]);
				$crrc[] = $explodeconsum[2];
				if($quart == 'apr-sep'){
					if($year == $explodeconsum[1] && in_array($explodeconsum[0], $q1) && $currentest[2]!=$explodeconsum[2]){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.($explodeconsum[2]+$permonth);

					}else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				if($quart == 'oct-mar'){
					if((($year == $explodeconsum[1] && (in_array($explodeconsum[0], $q2))) && $year-1 ==$explodeconsum[1] ) || (($year == $year && (in_array($explodeconsum[0], $q1))) && $year ==$explodeconsum[1] ) && $currentest[2]!=$explodeconsum[2]){
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.($explodeconsum[2]+$permonth);
					}
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				
		}
		$currentconsumption = array_sum($crrc);
	}else{
		foreach ($_POST['seasonname'] as $key => $value) {
			$quarters = $value;
		}
		$quartval =$quarters;
		$explode = explode(',', $period);
		$count = count($explode);
		$varmonths = getMonthsInRange($explode[0],$explode[$count-1]);
		$months = ['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sep','Oct','Nov','Dec'];
		$crrc = array();
		$yeacc = array();
		$mnts = array();
		foreach ($varmonths as $key => $valuem) {
			$yeacc[] = $valuem['year'].'-'.$months[$valuem['month']-1];
			
		}
		// print_r($yeacc);
		
		$quarters = '';
		foreach ($_POST['seasonname'] as $key => $value) {
			$quarters = $value;
		}
		
		$getcountofclick = "SELECT season, yeartrade  FROM nus_season WHERE tradeId='".$_POST['tranchclick']."' AND supplierId='".$_POST['supplierid']."' AND yeartrade='".$_POST['ddlyear']."' AND season='".$quarters."'";
		$result = $conn->query($getcountofclick);
		$quart ='';
		$year = '';
		if ($result->num_rows > 0) {
		  while($row = $result->fetch_assoc()) {
		    $quart = $row['season'];
		    $year = $row['yeartrade'];
		  }
		} 
		
		$q2= ['Oct','Nov','Dec'];
		$q1 = ['Jan', 'Feb', 'Mar'];
        $q8 = ['Apr','May','Jun','July','Aug','Sep'];

		$tradev = numberreturn($_POST['tradevolume']); 
		$permonth = (float)$tradev/6;
		// print_r($consumptionmonth);
		foreach ($consumptionmonth as $key => $valuess) {
			$currentest = explode('-',$estimationconsumption[$key]);
				$explodeconsum = explode('-', $valuess);
				$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
				
				if(in_array($keyval, $yeacc)){
					
					$keyvals = explode('-', $keyval);
					
					if($quart == 'apr-sep'){
						
						if($year == $explodeconsum[1] && (in_array($explodeconsum[0], $q8)) && $currentest[2]!=$explodeconsum[2]){
							
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.($explodeconsum[2]+$permonth);
						}else{
							$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
						}
					}
					if($quart == 'oct-mar'){

					
						
					if((($keyvals[0] == $explodeconsum[1] && (in_array($keyvals[1], $q2))) && $year-1 ==$explodeconsum[1] ) || (($year == $keyvals[0] && (in_array($keyvals[1], $q1))) && $year ==$explodeconsum[1] ) && $currentest[2]!=$explodeconsum[2]){

						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.($explodeconsum[2]+$permonth);
					
						
					}
				
					else{
						$headconsumption[] = $explodeconsum[0].'-'.$explodeconsum[1].'-'.$explodeconsum[2];
					}
				}
				
			}
		}
		foreach ($headconsumption as $key => $values) {
			$explodeconsum = explode('-', $values);
			$keyval = $explodeconsum[1].'-'.$explodeconsum[0];
			if(in_array($keyval, $yeacc)){
				$exl = explode('-', $keyval);
				$crrc[] = $explodeconsum[2];
			}
		}
		$currentconsumption = array_sum($crrc);
	}
}

// print_r($headconsumption); 
	$getheadge = array();
	$pricedata = array();
	foreach ($headconsumption as $key => $valueheadge) {
		$divar = explode('-', $valueheadge);
		$getheadge[] = $divar[0].'-'.$divar[1];
		$pricedata[] = $divar[2];
	}
	$consumptionallmonths = explode('|', $singlerow[0]['consumptionmonth']);
	$openconsuption = array();
	foreach ($consumptionallmonths as $key => $openvalue) {

		$getarray = explode('-', $openvalue);
		$opnkal =$getarray[0].'-'.$getarray[1];
		$getconval = array_search($opnkal, $getheadge);
		$headval = $headconsumption[$getconval];
		$getprice = $pricedata[$getconval];
	
		$getminusval = numberreturn($getarray[2])-$getprice;
		$openconsuption[] = $getarray[0].'-'.$getarray[1].'-'.$getminusval;
	}
	// print_r($openconsuption);
	// echo "<pre>";
	// echo "Total Consumption = ".$totalconsumption;
	// echo "<pre>";
	// echo "Current Consumption = ".$currentconsumption;
if($availableclick == 0 ){
	$_SESSION['errorclick'] = time();
	header("location:entertrade.php");
	die();
	
}

else{
	
	$sqlfile = '';
	if($tradeper =='Calendar Yearly'){
		
		 $sqlfile = "UPDATE nus_calenderyear SET clicks =clicks-1 WHERE calenderId=".$updateId."";
	}
	if ($tradeper =='Calendar Monthly') {
		$sqlfile = "UPDATE nus_calendermonth SET clicks =clicks-1 WHERE monthId=".$updateId."";
	}
	if ($tradeper =='Calendar Quarterly') {
		$sqlfile = "UPDATE nus_calenderquarter SET clicks =clicks-1 WHERE querterid=".$updateId."";
	}
	if ($tradeper =='Season') {
		$sqlfile = "UPDATE nus_season SET clicks =clicks-1 WHERE seasonId=".$updateId."";
	}
	$conn->query($sqlfile);
	$getheadge = array();
	$pricedata = array();
	foreach ($headconsumption as $key => $valueheadge) {
		$divar = explode('-', $valueheadge);
		$getheadge[] = $divar[0].'-'.$divar[1];
		$pricedata[] = $divar[2];
	}
	$consumptionallmonths = explode('|', $singlerow[0]['consumptionmonth']);
	$openconsuption = array();
	// echo "Consumptionall months";
	// echo "<pre>";
	// print_r($consumptionallmonths);
	// $i=0;
	foreach ($consumptionallmonths as $key => $openvalue) {

		$getarray = explode('-', $openvalue);
		$opnkal =$getarray[0].'-'.$getarray[1];
		$getconval = array_search($opnkal, $getheadge);
		$headval = $headconsumption[$getconval];
		$getprice = $pricedata[$getconval];
	
		$getminusval = numberreturn($getarray[2])-$getprice;
		$openconsuption[] = $getarray[0].'-'.$getarray[1].'-'.$getminusval;
		// $i++;
	}

	// echo "<pre>";
	// echo "I value = ".$i;
	// // echo "<pre>";
	// // print_r($openconsuption);
	// echo "<pre>";
	// print_r($headconsumption);
	// print_r($openconsuption);
	$updateconsumption = "UPDATE nus_supply_contract SET hedgeconsumption ='".implode('|', $headconsumption)."',openconsumption='".implode('|', $openconsuption)."' WHERE supplierId=".$_POST['supplierid']."";
	$conn->query($updateconsumption);

	// echo $updateconsumption;

	// echo "<h1>$quarters</h1>";
	
	$tradedVolume = numberreturn($_POST['tradevolume']);
	if (empty($_POST['mw'])){
		if(empty($quarters)) {
			$quarters = '';
		}
		
		$sqlTrade = "INSERT INTO enter_trade (parentId, clientId, supplycontractid, mw, tradevolume, baseload, effectiveprice, trade, tradevalue, tradingId, nustradeId, mwh, percentage, tradeDate,quartval,tradequarvol) VALUES ( '".$_POST['parentid']."', '".$singlerow[0]['clientId']."', '".$singlerow[0]['contract_id']."', 0, '".$tradedVolume."', '".numberreturnfloat($_POST['baseload'])."', '".numberreturnfloat($_POST['effectiveprice'])."','".$tradeper."', '".$_POST['ddlyear']."', '".$_POST['tranchclick']."', '".$updateId."', '".$_POST['mwhtrade']."', '".$_POST['percentagetrade']."', '".$_POST['creationdate']."', '".$quarters."', '".$_POST['quartanual']."')";

	}
	// else if(empty($_POST['mwhtrade'])) {
	// 	if(empty($quarters)) {
	// 		$quarters = '';
	// 	}
	// 	$sqlTrade = "INSERT INTO enter_trade (parentId, clientId, supplycontractid, mw, tradevolume, baseload, effectiveprice, trade, tradevalue, tradingId, nustradeId, mwh, percentage, tradeDate,quartval,tradequarvol) VALUES ( '".$_POST['parentid']."', '".$singlerow[0]['clientId']."', '".$singlerow[0]['contract_id']."', 0, '".$tradedVolume."', '".$_POST['baseload']."', '".$_POST['effectiveprice']."','".$tradeper."', '".$_POST['ddlyear']."', '".$_POST['tranchclick']."', '".$updateId."', '".$_POST['mwhtrade']."', '".$_POST['percentagetrade']."', '".$_POST['creationdate']."', '".$quarters."', '".$_POST['quartanual']."')";

	// }
	else {
		if(empty($quarters)) {
			$quarters = '';
		}
		$sqlTrade = "INSERT INTO enter_trade (parentId, clientId, supplycontractid, mw, tradevolume, baseload, effectiveprice, trade, tradevalue, tradingId, nustradeId, mwh, percentage, tradeDate,quartval,tradequarvol) VALUES ( '".$_POST['parentid']."', '".$singlerow[0]['clientId']."', '".$singlerow[0]['contract_id']."', '".$_POST['mw']."', '".$tradedVolume."', '".numberreturnfloat($_POST['baseload'])."', '".numberreturnfloat($_POST['effectiveprice'])."','".$tradeper."', '".$_POST['ddlyear']."', '".$_POST['tranchclick']."', '".$updateId."', '', '".$_POST['percentagetrade']."', '".$_POST['creationdate']."', '".$quarters."', '".$_POST['quartanual']."')";
	}
    $conn->query($sqlTrade);
	// echo "sql=".$sqlTrade;
    $_SESSION['updatedtrade'] = time();
    //  header("location:supplycontractpreview.php?id=".$_POST['supplierid']."&type=edit");
    header("location:supplycontractpreview.php?info=".$_POST['clientsId']."&id=".$_POST['supplierid']."&type=preview");
}




function numberreturn($value){
	$toremovecomma = intval(preg_replace('/[^\d. ]/', '', $value));
	return $toremovecomma;
}

function getMonthsInRange($startDate, $endDate)
{
    $months = array();

    while (strtotime($startDate) <= strtotime($endDate)) {
        $months[] = array(
            'year' => date('Y', strtotime($startDate)),
            'month' => date('m', strtotime($startDate)),
        );

        // Set date to 1 so that new month is returned as the month changes.
        $startDate = date('01 M Y', strtotime($startDate . '+ 1 month'));
    }

    return $months;
}
?>
