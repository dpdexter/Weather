<?php
if ( ! defined('EXT') ) exit('Invalid file request');
/*
=====================================================
 B2quote 
 Author: David Dexter
 http://www.brilliant2.com 
 Copyright (c) David Dexter - Brilliant.com 
=============================================================
	File:			pi.b2quote.php 
-------------------------------------------------------------
	Compatibility:	ExpressionEngine 1.6.8+ 
					Requires Curl
-------------------------------------------------------------
	Purpose:		Grab Stock Quotes
=============================================================
*/

$plugin_info = array(
						'pi_name'			=> 'B2weather',
						'pi_version'		=> '1.0.0',
						'pi_author'			=> 'David Dexter',
						'pi_author_url'		=> 'http://www.brilliant2.com/',
						'pi_description'	=> 'Weather Information For Weather.gov',
						'pi_usage'			=> B2weather::usage()
					);
					
class B2weather {

	var $return_data 	= '';

	function details(){
	
		global $TMPL;
		
		// Get the tag attribute
			$zip = ( ! $TMPL->fetch_param('zipcode')) ? '91764' : strtoupper($TMPL->fetch_param('zipcode'));
			$days = ( ! $TMPL->fetch_param('days')) ? 1 : strtoupper($TMPL->fetch_param('days'));
			if(!is_numeric($days) || $days > 7){
				$days = 7;
			}
		// Build our yahoo download url 
			$url = 'http://www.weather.gov/forecasts/xml/SOAP_server/ndfdSOAPclientByDay.php?whichClient=NDFDgenByDayMultiZipCode&lat=&lon=&listLatLon=&lat1=&lon1=&lat2=&lon2=&resolutionSub=&endPoint1Lat=&endPoint1Lon=&endPoint2Lat=&endPoint2Lon=&zipCodeList='.$zip.'&centerPointLat=&centerPointLon=&distanceLat=&distanceLon=&resolutionSquare=&citiesLevel=&format=24+hourly&startDate='.date("Y-n-d").'&numDays=7&Submit=Submit';
			
		// se curl to get the data
			$curl = curl_init();
		    curl_setopt ($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    $result = curl_exec ($curl);
		    curl_close ($curl); 
			
			$xml = new SimpleXMLElement($result);
			
			// Get the Max Temp Array
				for($i=0;$i < count($xml->data->parameters->temperature[0]->value) ; $i++){
					$val = $xml->data->parameters->temperature[0]->value[$i];
					$max[$i] = ($val == '') ? 'N/A' : $val;
				}

			// Get the Min temp Array
				for($i=0;$i < count($xml->data->parameters->temperature[1]->value) ; $i++){
					$val = $xml->data->parameters->temperature[1]->value[$i];
					$min[$i] = ($val == '') ? 'N/A' : $val;
				}
			
			// Get the text outlook
				$condition = 'weather-conditions';
				$i = 0;
				foreach($xml->data->parameters->weather->$condition as $v){
					$forecast[$i] = $v["weather-summary"];
					$i++;
				}
			
			// Get the icon information	
				$tag = 'conditions-icon';
				$link = 'icon-link';
				$i = 0;
				foreach($xml->data->parameters->$tag->$link as $v){
					$icon[$i] = $v;
					$i++;
				}

		$swap = array (
							"{zipcode}",
							"{date}",
							"{forecast}",  
							"{count}",
							"{high}",
							"{low}",
							"{icon}",
						);
	
		$tagdata = $TMPL->tagdata;


		if (preg_match("/format\s*=\s*[\'|\"](.*?)[\'|\"]/s", $tagdata, $match)){
             $format = str_replace("%","",$match['1']);
		}
		$tagdata = preg_replace("/\sformat\s*=\s*[\'|\"](.*?)[\'|\"]/s","",$tagdata);
		
		for($i=0; $i < $days;$i++){
			$vals = array (
								$zip,
								date($format,strtotime("+ ".$i." days")),
								$forecast[$i],
								$i + 1,
								$max[$i],
								$min[$i],
								$icon[$i]
							);
			
			$this->return_date .= str_replace($swap,$vals,$tagdata);
		}	
		return $this->return_date;
	}	
	
	function usage(){
		ob_start(); 
		?>
		#Requires Curl
		
		B2Weather will grab weather information from the National Weather Service. 
		
		Tag Usage
		=====================================================
		
			{exp:b2weather:details zipcode="95219" days="7"}
				{count}
				{date} // also accepts format attribute 
				{high}
				{low}
				{forecast}
				{icon}
			{/exp:b2weather:details}
		
		
		Parameters:
		-------------------------------------
			Zip Code - The 5 digit zip code to lookup 
			Days - The number of days to display
		<?php
		$buffer = ob_get_contents();
			
		ob_end_clean(); 
		
		return $buffer;
	}

}
?>