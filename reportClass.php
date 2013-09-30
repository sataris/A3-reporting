<?
error_reporting(E_ERROR | E_PARSE);
ignore_user_abort(true);
set_time_limit(0);
//Get the necessary files
set_include_path(get_include_path().PATH_SEPARATOR.'/home/rtwpfx/public_html/analytics-v3/google-api-php-client/src');
require_once 'analytics-v3/google-api-php-client/src/apiClient.php';
require_once 'analytics-v3/google-api-php-client/src/contrib/apiAnalyticsService.php';
include ("inc/fpdf.php");
include ("inc/googlegraph.php");
$included_files = get_included_files();
if (!in_array("/home/rtwpfx/public_html/inc/mime/sendmail_mail_attachment.php", $included_files)) {
    include ("inc/mime/sendmail_mail_attachment.php");
}
date_default_timezone_set("Australia/Sydney");
class Report {
    //DB Variables
    protected $db_hostname = "localhost";
    protected $db_username = "rtwpfx_dbu";
    protected $db_password = "paypay1";
    protected $db_database = "rtwpfx_tracker_pay";
    protected $conn;
    // Report Variables
    private $reports;
    protected $clientname;
    protected $clientemail;
    protected $rrstart;
    protected $rrto;
	protected $now;
    //Goal Variables
    protected $goals = "N";
    protected $goals3;
    protected $goals3due;
    protected $goals6;
    protected $goals6due;
    protected $goals12;
    protected $goals12due;
    protected $ecom = "N";
    protected $ecom3;
    protected $ecom3due;
    protected $ecom6;
    protected $ecom6due;
    protected $ecom12;
    protected $ecom12due;
    protected $traf3;
    protected $traf3due;
    protected $traf6;
    protected $traf6due;
    protected $traf12;
    protected $traf12due;
    // Company Variables
    protected $pdf;
    protected $cid;
    protected $profileId;
    protected $homepage;
    protected $prevstartdate;
    protected $prevenddate;
    protected $chart;
    protected $companyname;
    protected $gmostatus;
    protected $startdate;
    protected $enddate;
    // PPC Variables
    protected $campaignservice;
    protected $reportDefinition;
    protected $token;
    protected $adWordheaders;
    protected $ppcobj;
    protected $ppcId;
    protected $ppcdatestart;
    //The Team Variables
    protected $cmname;
    protected $cmemail;
    protected $seoname;
    protected $seoemail;
    protected $ppcname;
    protected $ppcemail;
    protected $smmname;
    protected $smmemail;
    // API Variables
    protected $client;
    protected $majesticapi;
    protected $analytics;

    
    protected function checkTokenValid() {
        //Check if the time remaining is less than 2 minutes
        //Get the current token from the database
        
        $timer = json_decode($this->client->getAccessToken());
        $expires = $timer->created + $timer->expires_in;
        if ($expires - time() < 120) {
            $this->client->refreshToken('1/NBOsp-vNvt7TXgf7eGY1pulz28tMCsLLjzS4D0D6BPg');
        }
        usleep(250000);
    }
    
    protected function getResults($from, $to, $metrics) {
        return $this->analytics->data_ga->get('ga:'.$this->profileId, $from, $to, 'ga:'.$metrics);
    }
    
    protected function markup($bid, $percentage) {
        $markup = $percentage * 100;
        $markedup = $bid * 100 / $markup;
        return $markedup;
    }
    
    protected function ceiling($num) {
        if ($num > 100) {
            $nearest = 100;
        } else if ($num > 10) {
            $nearest = 10;
        } else {
            $nearest = ceil($num);
        }
        if ($nearest == 0) {
            $result = 0;
        } else {
            $result = ceil($num / $nearest) * $nearest;
        }
        return $result;
    }
    
    protected function clickarray($num) {
        $divider = $num / 2;
        for ($i = 0; $i <= 2; $i++) {
            $return[] = $divider * $i;
        }
        return $return;
    }
    
    protected function clickdata($clickdata, $base) {
        if ($base == 0) {
            foreach ($clickdata as $click) {
                $returnarray[] = $click * 100;
            }
        } else {
            foreach ($clickdata as $click) {
                $returnarray[] = $click / $base * 100;
            }
        }
        return $returnarray;
    }
    
    protected function insertChart() {
        $this->pdf->Image($this->chart."&chm=B,76A4FB,0,1,0", '10', '60', '190', '', 'PNG');
    }
    
    protected function insertChartAris() {
    
    }
    
    protected function setBarColour() {
        $this->pdf->SetFillColor(49, 145, 214);
    }
    
    protected function setColourlight() {
        $this->pdf->SetFillColor(234, 244, 251);
        $this->pdf->SetTextColor(0, 0, 0);
        
    }
    
    protected function setColourdark() {
        $this->pdf->SetFillColor(193, 222, 243);
        $this->pdf->SetTextColor(0, 0, 0);
        
    }
    
    protected function calc_percGoal($projected, $target) {
        $difference = ($projected - $target) / $target * 100;
        return $difference;
    }
    
    protected function calc_total_months($a) {
        $year = date("Y", strtotime($a));
        $ymonths = $year * 12;
        $month = date("n", strtotime($a));
        $total = $ymonths + $month;
        return $total;
    }
    
    protected function calcvisitdiff($cvisits, $pvisits) {
        if ($pvisits != 0) {
            $visitdiff = $cvisits - $pvisits;
            $visitdiff = $visitdiff / $pvisits;
            $visitperc = $visitdiff * 100;
            $visitperc = round($visitperc, 2);
            $visitperc = number_format($visitperc);
            return $visitperc;
        } else if ($cvisits == 0) {
            $visitperc = 0;
            return $visitperc;
        } else {
            $visitperc = "100";
            return $visitperc;
        }
    }
    
    protected function calcconvdiff($cc, $pc, $ct, $pt) {
        if (($pc * $ct) > 0) {
            $convdiff = $cc * $pt / ($pc * $ct) - 1;
            $convdiff = $convdiff * 100;
            $convdiff = round($convdiff, 2);
            $convdiff = number_format($convdiff);
            return $convdiff;
        } else {
            if ($cc > 0) {
                $convdiff = "100";
            } else {
                $convdiff = "0";
            }
            return $convdiff;
        }
        
    }
    
    protected function calcconvrate($conversions, $visits) {
        if ($visits > 0 && $conversions > 0) {
            $convrate = ($conversions / $visits) * 100;
            $convrate = round($convrate, 2);
            return $convrate;
        } else {
            $convrate = 0;
            return $convrate;
        }
        
    }
    
    protected function floorOf($num) {
        if ($num > 100) {
            $nearest = 100;
        } else if ($num > 10) {
            $nearest = 10;
        } else {
            $nearest = floor($num);
        }
        if ($nearest == 0) {
            return $nearest;
        } else {
            $result = floor($num / $nearest) * $nearest;
            return $result;
        }
    }
    
    protected function graph($dimensions, $metrics, $filter = null) {
        if ($metrics[0] == "transactions" && $metrics[1] == "visits") {
            $flag = 1;
        }
        $graph = new GoogleGraph();
        $daily = "";
        //Graph
        $graph->Graph->setType('line');
        $graph->Graph->setSize(1000, 125);
        $graph->Graph->setAxis(array('x', 'y', 'r'));
        $graph->Graph->setGridLines(0, 50, 1, 0);
        $graph->Graph->addFill('chart', 'white', 'solid');
        // Get the daily data for the graph
        $this->checkTokenValid();
        $dailydata = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-01", strtotime($this->now." - 1 months")), date("Y-m-t", strtotime($this->now." - 1 months")), 'ga:'.$metrics[0], array("dimensions"=>"ga:date"));
        $result = $dailydata->getRows();
        for ($i = 0; $i < sizeof($result); $i++) {
            $daily[] = $result[$i][1];
        }
        $maxmetric1 = max($daily);
        $tempceiling = $this->ceiling($maxmetric1);
        $totalmetric1 = $this->clickarray($tempceiling);
        $metric1data = $this->clickdata($daily, $tempceiling);
        //$graph->Graph->addAxisLabel($months);
        $graph->Graph->addAxisLabel($totalmetric1);
        $graph->Graph->addAxisLabel($totalmetric1);
        
        $graph->Graph->addAxisStyle(array(0, '#000000', 10));
        $graph->Graph->addAxisStyle(array(3, '#0000dd', 12, 1));
        //$graph->Graph->setAxisRange(array('',0,$totclicks));
        
        //Lines
        $graph->Graph->setLineColors(array('#0000FF', '#0000FF'));
        $graph->Graph->addLineStyle(array(3, 6, 0));
        $graph->Graph->addLineStyle(array(1, 1, 0));
        //if ($metrics[2] == "blank") {
          //  $graph->Graph->setLegend(array("Visitors"));
        //}
        //Shapes
        $graph->Data->addData($metric1data);
        //Output Graph
        $graphurl = $graph->graphURL();
        
        return $graphurl;
    }
    
    protected function pdffooter() {
        $this->pdf->Image('/home/rtwpfx/public_html/images/reports/bg-footer.jpg', 0, 269, 220, 30);
        $this->pdf->Image('/home/rtwpfx/public_html/images/reports/logo.png', 10, 270, 30);
        
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(50, 278, "Campaign Manager: ".$this->cmname);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Text(50, 283, "E-Web Marketing, Level 2, 20 Chandos Street, St. Leonards, 2065");
        $this->pdf->Text(188, 278, "Need Help?");
        $this->pdf->SetFont('Arial', 'B', 10);
        $this->pdf->Text(172, 283, "Call (02) 9438 5633");
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Text(188, 295, "Page No: ".$this->pdf->PageNo());
    }
    
    protected function pdfHeader($title = "", $daterun = "") {
        $this->pdf->Image('/home/rtwpfx/public_html/images/reports/bg-ranking-reporting.jpg', 0, 0, 220, 30);
        $this->pdf->Image('/home/rtwpfx/public_html/images/reports/logo.png', 10, 3, 30);
        $this->pdf->Ln(20);
        $this->pdf->SetTextColor(0, 0, 0);
        //$this->pdf->Text(70,24,$title);
        //$this->pdf->SetFontSize(14);
        //$this->pdf->Text(160,10,$this->companyname);
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Text(145, 8, $this->clientname);
        
        if ($title == "Ranking Report") {
            if ($this->rrstart) {
                $this->pdf->Text(145, 15, "Report For: ".date("F Y", strtotime($this->rrstart)));
            } else {
                $this->pdf->Text(145, 15, "Report For: ".date("F Y", strtotime($daterun)));
            }
            $this->pdf->SetFontSize(8);
            if ($this->rrto && $this->rrto != "0000-00-00") {
                $this->pdf->Text(145, 19, "Previous Report: ".date("F Y", strtotime($this->rrto)));
            } else if ($this->rrto != "0000-00-00") {
                $getcorrectprevdate = "SELECT parsed_date FROM rr_reports WHERE company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1,1";
                $prevdatereport = mysql_query($getcorrectprevdate);
                $pdate = mysql_fetch_assoc($prevdatereport);
                $this->pdf->Text(145, 19, "Previous Report: ".date("F Y", strtotime($pdate['parsed_date'])));
            }
            
        } else if ($title == "goals") {
        } else {
            $this->pdf->Text(145, 15, "Report For: ".date("F Y", strtotime($this->startdate)));
            $this->pdf->SetFontSize(8);
            $this->pdf->Text(145, 19, "Previous Report: ".date("F Y", strtotime($this->prevstartdate)));
        }
        //$this->pdf->Line(10,30,200,30);
        $this->pdf->Ln(5);
        //exit("Test");
        //return $this->pdf;
    }

	protected function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\', $eol = '\n') { 
        if (is_string($input) && !empty($input)) { 
            $output = array(); 
            $tmp    = preg_split("/".$eol."/",$input); 
            if (is_array($tmp) && !empty($tmp)) { 
                while (list($line_num, $line) = each($tmp)) { 
                    if (preg_match("/".$escape.$enclosure."/",$line)) { 
                        while ($strlen = strlen($line)) { 
                            $pos_delimiter       = strpos($line,$delimiter); 
                            $pos_enclosure_start = strpos($line,$enclosure); 
                            if ( 
                                is_int($pos_delimiter) && is_int($pos_enclosure_start) 
                                && ($pos_enclosure_start < $pos_delimiter) 
                                ) { 
                                $enclosed_str = substr($line,1); 
                                $pos_enclosure_end = strpos($enclosed_str,$enclosure); 
                                $enclosed_str = substr($enclosed_str,0,$pos_enclosure_end); 
                                $output[$line_num][] = $enclosed_str; 
                                $offset = $pos_enclosure_end+3; 
                            } else { 
                                if (empty($pos_delimiter) && empty($pos_enclosure_start)) { 
                                    $output[$line_num][] = substr($line,0); 
                                    $offset = strlen($line); 
                                } else { 
                                    $output[$line_num][] = substr($line,0,$pos_delimiter); 
                                    $offset = ( 
                                                !empty($pos_enclosure_start) 
                                                && ($pos_enclosure_start < $pos_delimiter) 
                                                ) 
                                                ?$pos_enclosure_start 
                                                :$pos_delimiter+1; 
                                } 
                            } 
                            $line = substr($line,$offset); 
                        } 
                    } else { 
                        $line = preg_split("/".$delimiter."/",$line); 
    
                        /* 
                         * Validating against pesky extra line breaks creating false rows. 
                         */ 
                        if (is_array($line) && !empty($line[0])) { 
                            $output[$line_num] = $line; 
                        }  
                    } 
                } 
                return $output; 
            } else { 
                return false; 
            } 
        } else { 
            return false; 
        } 
    } 
    
    public function goalReport() {
        if ($this->profileId > 0) {
            // I know we have analyics, so lets throw out the numbers for the good people
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("Goal Progress"), 8, "Goal Progress", '', '', 'L');
            // Now I see if they have any traffic goals
            // 3 month Goals
            $check3monthsql = "SELECT analytics_traffic3, analytics_traffic3due FROM clientstatus AS CL WHERE company_id = '".$this->cid."' AND (analytics_traffic3 > 0 AND analytics_traffic3due <> 0000-00-00)";
            $month3result = mysql_query($check3monthsql);
            if (mysql_num_rows($month3result) > 0) {
                $month3 = mysql_fetch_assoc($month3result);
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetFillColor(49, 145, 214);
                $this->pdf->Ln(15);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(140, '5', "3 Month Traffic Goal", '', '', 'L', true);
                $this->pdf->Cell(30, '5', 'Aim: '.$month3['analytics_traffic3due'], '', '', 'R', true);
                $this->pdf->Ln(8);
                $this->pdf->SetFontSize(15);
                
                //Get the current monthly traffic
                $currenttraffic = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-01"), date("Y-m-t"), "ga:visits");
                $ctraff = $currenttraffic->getRows();
                //Figure out percentage to target
                $perc = ($ctraff[0][0] / $month3['analytics_traffic3']) * 100;
                //Check if the perc is greater than 100 or goal acheived!
                if ($perc > 100) {
                    $this->pdf->SetTextColor(0, 174, 16);
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(170, '5', number_format($perc, 2)."% to goal", '', '', 'C');
                $this->pdf->Ln(8);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->SetTextColor('0', '0', '0');
                $this->pdf->Cell(85, '5', 'Actual: '.number_format($ctraff[0][0])." visits", '', '', 'L');
                $this->pdf->Cell(85, '5', 'Goal: '.number_format($month3['analytics_traffic3'])." visits", '', '', 'R');
                
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->Ln(5);
                //$this->pdf->Image($costurl,'10','70','190','','PNG');
                //We have a 3 month goal!
            }
            //Check if they have Conversion goals
            $convsql = "SELECT analytics_conversions3, analytics_conversions3due FROM clientstatus AS CL WHERE company_id = '".$this->cid."' AND (analytics_conversions3 > 0 AND analytics_conversions3due <> 0000-00-00)";
            $month3conv = mysql_query($convsql);
            if (mysql_num_rows($month3conv) > 0) {
                $conv3month = mysql_fetch_assoc($month3conv);
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetFillColor(49, 145, 214);
                $this->pdf->SetFontSize(10);
                $this->pdf->Ln(15);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(140, '5', "3 Month Conversion Goal", '', '', 'L', true);
                $this->pdf->Cell(30, '5', 'Aim: '.$conv3month['analytics_conversions3due'], '', '', 'R', true);
                $this->pdf->Ln(8);
                $this->pdf->SetFontSize(15);
                
                //Get the current monthly conversions
                $currentconv = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-01"), date("Y-m-t"), "ga:goalCompletionsAll");
                $cconv = $currentconv->getRows();
                
                //Figure out percentage to target
                $perc = ($cconv[0][0] / $conv3month['analytics_conversions3']) * 100;
                //Check if the perc is greater than 100 or goal acheived!
                if ($perc > 100) {
                    $this->pdf->SetTextColor(0, 174, 16);
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(170, '5', number_format($perc, 2)."% to goal", '', '', 'C');
                $this->pdf->Ln(8);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->SetTextColor('0', '0', '0');
                $this->pdf->Cell(85, '5', 'Actual: '.number_format($cconv[0][0])." conversions", '', '', 'L');
                $this->pdf->Cell(85, '5', 'Goal: '.number_format($conv3month['analytics_conversions3'])." conversions", '', '', 'R');
                $this->pdf->Ln(5);
            }
            
            $ecommsql = "SELECT analytics_ecommerce3, analytics_ecommerce3due FROM clientstatus AS CL WHERE company_id = '".$this->cid."' AND (analytics_ecommerce3 > 0 AND analytics_ecommerce3due <> 0000-00-00)";
            $month3ecomm = mysql_query($ecommsql);
            if (mysql_num_rows($month3ecomm) > 0) {
                $ecomm3month = mysql_fetch_assoc($month3ecomm);
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetFillColor(49, 145, 214);
                $this->pdf->SetFontSize(10);
                $this->pdf->Ln(15);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(140, '5', "3 Month E-Commerce Goal", '', '', 'L', true);
                $this->pdf->Cell(30, '5', 'Aim: '.$ecomm3month['analytics_ecommerce3due'], '', '', 'R', true);
                $this->pdf->Ln(8);
                $this->pdf->SetFontSize(15);
                
                //Get the current monthly conversions
                $currentecomm = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-01"), date("Y-m-t"), "ga:transactionRevenue");
                $cecomm = $currentecomm->getRows();
                
                //Figure out percentage to target
                $perc = ($cecomm[0][0] / $ecomm3month['analytics_ecommerce3']) * 100;
                //Check if the perc is greater than 100 or goal acheived!
                if ($perc > 100) {
                    $this->pdf->SetTextColor(0, 174, 16);
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->Cell(170, '5', number_format($perc, 2)."% to goal", '', '', 'C');
                $this->pdf->Ln(8);
                $this->pdf->Cell(10, '5', '', '', '', '');
                $this->pdf->SetTextColor('0', '0', '0');
                $this->pdf->Cell(85, '5', 'Actual: $'.number_format($cecomm[0][0], 2)." Revenue", '', '', 'L');
                $this->pdf->Cell(85, '5', 'Goal: $'.number_format($ecomm3month['analytics_ecommerce3'], 2)." Revenue", '', '', 'R');
                $this->pdf->Ln(5);
            }
            $this->pdffooter();
        }
    }
    
    public function summaryGoalReport() {
        if ($this->profileId > 0) {
            // Check the analytics id
            
            $sql = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1";
            // Get the Report Id for this client for this month
            $sql2 = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1,1";
            // Get the Report Id for this client for this month
            $reportidresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevrid = mysql_query($sql2) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw3ranking = mysql_fetch_assoc($reportidresult)) {
                $currentreportid = $kw3ranking['report_id'];
            }
            while ($prid = mysql_fetch_assoc($prevrid)) {
                $prevreportid = $prid['report_id'];
            }
            $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1'";
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $gettargetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $gettarget = mysql_fetch_assoc($gettargetkwresult);
            $tempkeyword = explode("\n", $gettarget['keywords']);
            for ($i = 0; $i < sizeof($tempkeyword); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($keywords[$i]);
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                        
                    }
                } else {
                    unset($keywords[$i]);
                }
            }
            array_values($tempkeyword);
            $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            if (mysql_num_rows($getstatsresult) == 0) {
                $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0'";
                $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                if (mysql_num_rows($getstatsresult) == 0) {
                    $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2'";
                    $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                }
            }
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell(10, 10, '', '', 0, 0);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(8);
            $this->pdf->SetFontSize(10);
            $this->setBarColour();
			$this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(10, 5, '', '', 0, 0);
            $this->pdf->Cell(170, 5, "Goal Progress", '', '', 'L',TRUE);
            $this->pdf->Ln(5);
            $this->pdf->Image('/home/rtwpfx/public_html/images/reports/traffic.png', 175, 35);
            if ($this->ecom == "Y" && $this->ecom12due != "0000-00-00") {
                $month0 = date("Y-m-01", strtotime($this->ecom12due." - 1 year"));
                $month0sql = "SELECT ATS.nbRev FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$month0."'";
                $month0result = mysql_query($month0sql);
                $brow = mysql_fetch_assoc($month0result);
                $basetraffic = $brow['nbRev'];
                $actualsql = "SELECT ATS.nbRev FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$this->startdate."'";
                $actualresult = mysql_query($actualsql);
                $arow = mysql_fetch_assoc($actualresult);
                $actual = $arow['nbRev'];
                
                $xtraffic3 = $this->ecom3 - $basetraffic;
                $xtraffic6 = $this->ecom6 - $this->ecom3;
                $xtraffic12 = $this->ecom12 - $this->ecom6;
                //First calculate where we are
                $diff = abs($this->calc_total_months($this->ecom12due) - $this->calc_total_months($this->startdate));
                $targ3 = $this->ecom3;
                $targ3due = $this->ecom3due;
                $targ6 = $this->ecom6;
                $targ6due = $this->ecom6due;
                $targ12 = $this->ecom12;
                $targ12due = $this->ecom12due;
                $e = true;
            } else if ($this->goals == "Y" && $this->goals12due != "0000-00-00") {
                $month0 = date("Y-m-01", strtotime($this->goals12due." - 1 year"));
                $month0sql = "SELECT ATS.nbConvs FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$month0."'";
                $month0result = mysql_query($month0sql);
                $brow = mysql_fetch_assoc($month0result);
                $basetraffic = $brow['nbConvs'];
                $actualsql = "SELECT ATS.nbConvs FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$this->startdate."'";
                $actualresult = mysql_query($actualsql);
                $arow = mysql_fetch_assoc($actualresult);
                $actual = $arow['nbConvs'];
                
                $xtraffic3 = $this->goals3 - $basetraffic;
                $xtraffic6 = $this->goals6 - $this->goals3;
                $xtraffic12 = $this->goals12 - $this->goals6;
                //First calculate where we are
                $diff = abs($this->calc_total_months($this->goals12due) - $this->calc_total_months($this->startdate));
                $targ3 = $this->goals3;
                $targ3due = $this->goals3due;
                $targ6 = $this->goals6;
                $targ6due = $this->goals6due;
                $targ12 = $this->goals12;
                $targ12due = $this->goals12due;
                $g = true;
            } else {
            
                $month0 = date("Y-m-01", strtotime($this->traf12due." - 1 year"));
                $month0sql = "SELECT ATS.nonBranded FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$month0."'";
                $month0result = mysql_query($month0sql);
                $brow = mysql_fetch_assoc($month0result);
                $basetraffic = $brow['nonBranded'];
                $actualsql = "SELECT ATS.nonBranded FROM analytics_traffic_stats AS ATS WHERE ATS.company_id = '".$this->cid."' AND ATS.date ='".$this->startdate."'";
                $actualresult = mysql_query($actualsql);
                $arow = mysql_fetch_assoc($actualresult);
                $actual = $arow['nonBranded'];
                
                $xtraffic3 = $this->traf3 - $basetraffic;
                $xtraffic6 = $this->traf6 - $this->traf3;
                $xtraffic12 = $this->traf12 - $this->traf6;
                //First calculate where we are
                $diff = abs($this->calc_total_months($this->traf12due) - $this->calc_total_months($this->startdate));
                $targ3 = $this->traf3;
                $targ3due = $this->traf3due;
                $targ6 = $this->traf6;
                $targ6due = $this->traf6due;
                $targ12 = $this->traf12;
                $targ12due = $this->traf12due;
                $t = true;

                
            }
            $diff = 12 - $diff;
            $this->pdf->Cell(10, 10, '', '', 0, 0);
            switch ($diff) {
            
                case "1": // Month 1
                
                    $target = $basetraffic + ($xtraffic3 * 1 / 3);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;

                    
                case "2": // Month 2
                    $target = $basetraffic + ($xtraffic3 * 2 / 3);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    
                    break;
                    
                case "3": // Month 3
                    $goalperc = round($this->calc_percGoal($actual, $targ3), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "4": // Month 4
                    $target = $targ3 + ($xtraffic6 * 1 / 3);
                    $goalperc = round(calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                case "5": // Month 5
                    $target = $targ3 + ($xtraffic6 * 2 / 3);
                    $goalperc = round(calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    
                    break;
                    
                case "6": // Month 6
                    $goalperc = round(calc_percGoal($actual, $targ6), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "7": // Month 7
                    $target = $targ6 + ($xtraffic12 * 1.6);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "8": // Month 8
                    $target = $targ6 + ($xtraffic12 * 2 / 63);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "9": // Month 9
                    $target = $targ6 + ($xtraffic12 * 3 / 6);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                case "10": // Month 10
                    $target = $targ6 + ($xtraffic12 * 4 / 6);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "11": // Month 11
                    $target = $targ6 + ($xtraffic12 * 5 / 6);
                    $goalperc = round($this->calc_percGoal($actual, $target), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                case "12": // Month 12
                    $target = $targ6 + $xtraffic12;
                    $goalperc = round($this->calc_percGoal($actual, $targ12), 2);
                    $curmonth = date("M Y", strtotime($this->startdate));
                    if ($e) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Revenue: $".number_format($actual)) + 5, 8, $curmonth." Organic Non Branded Revenue: $".number_format($actual), '', '', 'L');
                    } elseif ($g) {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Convs: ".$actual) + 5, 8, $curmonth." Organic Non Branded Convs: ".$actual, '', '', 'L');
                    } else {
                        $this->pdf->Cell($this->pdf->GetStringWidth($curmonth." Organic Non Branded Visits: ".$actual) + 5, 8, $curmonth." Organic Non Branded Visits: ".$actual, '', '', 'L');
                    }
                    break;
                    
                default: // Change the order of these, it should go 12, 6 then 3
                    break;
                    
            }
            $this->pdf->SetFont('Arial', 'B');
            if ($goalperc > 2) {
                $this->pdf->SetTextColor(0, 174, 16);
                $this->pdf->Cell($this->pdf->GetStringWidth(round($goalperc)."% ahead of target"), 8, round($goalperc)."% ahead of target", '', '', 'R');
            } else if ($goalperc < - 2) {
                $this->pdf->SetTextColor(0, 51, 102);
                $this->pdf->Cell($this->pdf->GetStringWidth(abs(round($goalperc))."% behind target"), 8, abs(round($goalperc))."% behind target", '', '', 'R');
            } else {
                $this->pdf->SetTextColor(0, 174, 16);
                $this->pdf->Cell($this->pdf->GetStringWidth("On Target"), 8, "On Target", '', '', 'R');
            }
            $this->pdf->SetFont('');
            $this->pdf->Ln(15);
            $this->setBarColour();
            $this->pdf->Cell(10, 10, '', '', 0, 0);
            $this->pdf->Cell(13, 10, '', '', 0, 0, TRUE);
            for ($grow = 1; $grow <= 12; $grow++) {
                if ($grow == $diff) {
                    if ($goalperc > 2) {
                        $this->pdf->SetFillColor(198, 239, 206);
                    } elseif ($goalperc < - 2) {
                        $this->pdf->SetFillColor(255, 235, 156);
                    } else {
                        $this->setBarColour();
                    }
                    $this->pdf->Cell(13, 10, '', '', 0, 0, TRUE);
                } elseif ($grow > $diff) {
                    $this->pdf->SetFillColor(217, 217, 217);
                    $this->pdf->Cell(13, 10, '', '', 0, 0, TRUE);
                } else {
                    $this->setBarColour();
                    $this->pdf->Cell(13, 10, '', '', 0, 0, TRUE);
                }
            }
            
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Ln(10);
            $this->pdf->Cell(10, 10, '', '', 0, 0);
            $this->pdf->Cell(13, 10, date("M Y", strtotime($month0)), '', 0, 'C');
            for ($mrow = 1; $mrow <= 12; $mrow++) {
                if ($mrow == $diff) {
                    $this->pdf->SetFont('Arial', 'B');
                    if ($goalperc > 2) {
                        $this->pdf->SetTextColor(0, 174, 16);
                    } elseif ($goalperc < - 2) {
                        $this->pdf->SetTextColor(0, 51, 102);
                    } else {
                        $this->pdf->SetTextColor(0, 174, 16);
                    }
                    $diff = $diff + 2;
                    $this->pdf->Cell(13, 10, $curmonth, '', 0, 'C');
                    $diff = $diff - 2;
                    $this->pdf->SetTextColor(0, 0, 0);
                    $this->pdf->SetFont('');
                } elseif ($mrow == 3) {
                    $this->pdf->Cell(13, 10, date("M Y", strtotime($targ3due)), '', 0, 'C');
                } else if ($mrow == 6) {
                    $this->pdf->Cell(13, 10, date("M Y", strtotime($targ6due)), '', 0, 'C');
                } else if ($mrow == 12) {
                    $this->pdf->Cell(13, 10, date("M Y", strtotime($targ12due)), '', 0, 'C');
                } else {
                    $this->pdf->Cell(13, 10, '', '', 0, 0);
                }
            }
            $this->pdf->Ln(5);
            $this->pdf->Cell(10, 10, '', '', 0, 0);
            if ($e) {
                $this->pdf->Cell(13, 10, "$".number_format($basetraffic), '', 0, 'C');
            } else {
                $this->pdf->Cell(13, 10, number_format($basetraffic), '', 0, 'C');
            }
            for ($xrow = 1; $xrow <= 12; $xrow++) {
                if ($xrow == $diff) {
                    $this->pdf->SetFont('Arial', 'B');
                    if ($goalperc > 2) {
                        $this->pdf->SetTextColor(0, 174, 16);
                    } elseif ($goalperc < - 2) {
                        $this->pdf->SetTextColor(0, 51, 102);
                    } else {
                        $this->pdf->SetTextColor(0, 174, 16);
                    }
                    if ($e) {
                        $this->pdf->Cell(13, 10, "$".number_format($actual), '', 0, 'C');
                    } else {
                        $this->pdf->Cell(13, 10, number_format($actual), '', 0, 'C');
                    }
                    $this->pdf->SetTextColor(0, 0, 0);
                    $this->pdf->SetFont('');
                } elseif ($xrow == 3) {
                    if ($e) {
                        $this->pdf->Cell(13, 10, "$".number_format($targ3), '', 0, 'C');
                    } else {
                        $this->pdf->Cell(13, 10, number_format($targ3), '', 0, 'C');
                    }
                } elseif ($xrow == 6) {
                    if ($e) {
                        $this->pdf->Cell(13, 10, "$".number_format($targ6), '', 0, 'C');
                    } else {
                        $this->pdf->Cell(13, 10, number_format($targ6), '', 0, 'C');
                    }
                } elseif ($xrow == 12) {
                    if ($e) {
                        $this->pdf->Cell(13, 10, "$".number_format($targ12), '', 0, 'C');
                    } else {
                        $this->pdf->Cell(13, 10, number_format($targ12), '', 0, 'C');
                    }
                } else {
                    $this->pdf->Cell(13, 10, '', '', 0, 'C');
                }
            }
            $this->pdf->Ln(5);
            //$this->pdf->Cell(100, 8, date("j F Y", strtotime($this->startdate))." - ".date("j F Y", strtotime($this->enddate)), '', '', 'L');
            //$this->pdf->Image($this->chart."&chm=B,76A4FB,0,1,0", '10', '60', '190', '', 'PNG');
            $this->pdf->SetTextColor(255, 255, 255);
            $this->setBarColour();
            // $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->Ln(7);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(170, '5', "Site Usage", '', '', 'L', true);
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            //Get the Visitor Info
            $this->checkTokenValid();
            $visits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //if (count($visits->getRows()) > 0) {
            $visit = $visits->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', number_format($visit[0][0])." Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Cell(70, '12', number_format($visit[0][1])." Unique Visitors", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevvisits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$prevvisits = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',$this->prevstartdate,$this->prevenddate);
            // Get the previous month information
            $this->pdf->SetFont('Arial', '', 10);
            //	if (count($prevvisits->getRows()) > 0) {
            $previsit = $prevvisits->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $previsit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $lastyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$lastyear = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
            $this->pdf->SetFont('Arial', '', 10);
            //if (count($lastyear->getRows()) > 0) {
            $lastvisit = $lastyear->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $lastvisit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $lastvisit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(8);
            if ($this->ecom == "Y") {// If the client has goals we will display the goal data
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $ecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //if (count($goals->getRows()) > 0) {
                $ecom = $ecommerce->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($ecom[0][0])." Transactions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Cell(70, '12', "$".number_format($ecom[0][1], 2)." Total Revenue", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevecom = $prevmonthecommerce->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevecom[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevecom[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyearecom = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyearecom1 = $prevyearecom->getRows();
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevyearecom1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevyearecom1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            } else if ($this->goals = "Y") {// If the client has goals we will display the goal data
            
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $goals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //if (count($goals->getRows()) > 0) {
                $goal = $goals->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($goal[0][0])." Goal Conversions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Calculate the conversion rate
                $convrate = $this->calcconvrate($goal[0][0], $goal[0][1]);
                $this->pdf->Cell(70, '12', $convrate."% Conversion Rate", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthgoals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevgoal = $prevmonthgoals->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($goal[0][0], $prevgoal[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevgoal[0][0], $goal[0][1], $prevgoal[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyeargoal = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyeargoal1 = $prevyeargoal->getRows();
                $transperc = $this->calcvisitdiff($goal[0][0], $prevyeargoal1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevyeargoal1[0][0], $goal[0][1], $prevyeargoal1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            }
            // Now we add the Bounce Rate Stuff
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            $this->checkTokenValid();
            $bounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$bounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->startdate,$this->enddate);
            $bouncevisit = $bounce->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', round($bouncevisit[0][0], 2)."% Bounce Rate", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Get the % New Visits
            $this->pdf->Cell(70, '12', round($bouncevisit[0][1], 2)."% New Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now find the previous information
            $this->pdf->Ln(5);
            $this->pdf->SetFont('Arial', '', 10);
            $this->checkTokenValid();
            $prevbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->prevstartdate,$this->prevenddate);
            $prevbouncevisit = $prevbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevbouncevisit[0][0]);
            if ($transperc > 0) {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            } else {
                $this->pdf->SetTextColor(0, 174, 16);
            }
            $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevyearbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevyearbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
            // Get the previous month information
            $prevyearbouncevisit = $prevyearbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevyearbouncevisit[0][0]);
            if ($transperc < 0) {
                $this->pdf->SetTextColor(0, 174, 16);
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            }
            $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevyearbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now we get the traffic Summary
            $this->pdf->Ln(13);
            $this->pdf->Cell(10, '8', '', '', '', '');
            $this->pdf->SetFontSize(12);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(60, '12', "Traffic Summary", '', '', 'L');
            $this->pdf->Cell(40, '12', '', '', '', '');
            $this->pdf->Cell(70, '12', 'Top Organic Keywords', '', '', 'L');
            $this->pdf->Cell(30, '12', '', '', '', '');
            $this->pdf->SetFontSize(9);
            $this->pdf->Ln(13);
            //$this->pdf->SetFillColor(49, 145, 214);
            $this->setBarColour();
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(17, '5', '', '', '', '', true);
            $this->pdf->Cell(15, '5', 'Visits', '', '', 'C', true);
            $this->pdf->Cell(18, '5', 'Last Month', '', '', 'C', true);
            $this->pdf->Cell(17, '5', 'Last Year', '', '', 'C', true);
            $this->pdf->Cell(15, '5', 'Change', '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->Cell(52, '5', '', '', '', '', true);
            $this->pdf->Cell(16, '5', 'Visits', '', '', 'C', true);
            // $this->pdf->Cell(16, '5', 'Previous', '', '', 'C', true);
            // $this->pdf->Cell(16, '5', 'Change', '', '', 'C', true);
            $this->pdf->Ln(5);
            // Get the Not Provided Keywords
            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
            $nprov = $nprovided->getRows();
            if ($nprovided->getRows() > 0) {
                $nprovd = $nprov[0][0];
            }
            // Build the filters
            $targetkwsql = "SELECT CL.excluded_keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['excluded_keywords']);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($keywords[$i]);
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    }
                    if (strlen($keywords[$i]) > 0) {
                        $filterkw[] = $keywords[$i];
                    }
                }
            }
            if (is_array($filterkw)) {
                $filterkw = array_values($filterkw);
                usort($filterkw, array($this, 'sort'));
                if ($filterkw[0] == "") {
                    unset($filterkw[0]);
                    $filterkw = array_values($filterkw);
                }
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    for ($x = 0; $x < sizeof($filterkw); $x++) {
                        if (stristr($filterkw[$x], $filterkw[$i]) && $filterkw[$i] != $filterkw[$x]) {
                            unset($filterkw[$x]);
                        }
                    }
                    $filterkw = array_values($filterkw);
                }
                $filterkw = array_values($filterkw);
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    $filterkw[$i] = "ga:keyword!@".$filterkw[$i];
                }
                $filter = implode(";", $filterkw);
                if (substr($filter, -1) == ";") {
                    $filter = substr_replace($filter, "", -1, 1);
                }
            }
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['keywords']);
            }
            for ($i = 0; $i <= sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    } else {
                        $keywords[$i] = trim($keywords[$i]);
                    }
                } else {
                    unset($keywords[$i]);
                }
            }
            $keywords = array_values($keywords);
            usort($keywords, array($this, 'sort'));
            if ($keywords[0] == "") {
                unset($keywords[0]);
                $keywords = array_values($keywords);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                for ($x = 0; $x < sizeof($keywords); $x++) {
                    if (@stristr($keywords[$x], $keywords[$i]) && $keywords[$i] != $keywords[$x]) {
                        unset($keywords[$x]);
                    }
                }
                $keywords = array_values($keywords);
            }
            $keywords = array_values($keywords);
            for ($i = 0; $i < sizeof($keywords); $i++) {
                $keywords[$i] = trim("ga:keyword=@".$keywords[$i]);
            }
            $myfilter = implode(",", $keywords);
            if (substr($myfilter, -1) == ",") {
                $myfilter = substr_replace($myfilter, "", -1, 1);
            }
            $myfilter = str_replace("ga:keyword=@,", "", $myfilter);
            if ($filter) {
                $toporg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".$filter, "max-results"=>"15"));
            } else {
                $toporg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!=(not provided)", "max-results"=>"15"));
            }
            $toporganics = $toporg->getRows();
            for ($y = 0; $y < 15; $y++) {
                $mod = $y % 2;
                if ($mod == 0) {
                    $this->setColourlight();
                } else {
                    $this->setColourdark();
                }
                switch ($y) {
                    case 0:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Total', '', '', 'L', true);
                        $this->pdf->Cell(15, '5', number_format($visit[0][0]), '', '', 'C', true);
                        $this->pdf->Cell(18, '5', number_format($previsit[0][0]), '', '', 'C', true);
                        $this->pdf->Cell(17, '5', number_format($lastvisit[0][0]), '', '', 'C', true);
                        $change = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 1:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Organic', '', '', 'L', true);
                        // Now get the organic visits for the month
                        $this->checkTokenValid();
                        $org = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$org = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->startdate,$this->enddate);
                        $organic = $org->getRows();
                        $this->pdf->Cell(15, '5', number_format($organic[0][0]), '', '', 'C', true);
                        $this->checkTokenValid();
                        $orglast = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$orglast = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->prevstartdate,$this->prevenddate);
                        $prevorganic = $orglast->getRows();
                        $this->pdf->Cell(18, '5', number_format($prevorganic[0][0]), '', '', 'C', true);
                        $this->checkTokenValid();
                        $orgyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$orgyear = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',date("Y-m-d" , strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
                        $prevorganicyear = $orgyear->getRows();
                        $this->pdf->Cell(17, '5', number_format($prevorganicyear[0][0]), '', '', 'C', true);
                        $change = $this->calcvisitdiff($organic[0][0], $prevorganic[0][0]);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 2:
                    	$prevyeartargetedkw = 0;
						$prevtargetedkw  = 0; 
						$targetedkw = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Targeted', '', '', 'L', true);
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter)));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";".$filter;
                                        
                                        $tempfilter = str_replace(",;", ";", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $targetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $targetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        }
                        if ($filter) {
                            if (!$targetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $targetedkw += $kwvisit[0][0];
                                }
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $targetedkw / $total;
                            if ($targetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $targetedkw += $nprovd * $temp;
                            round($targetedkw);
                        }
                        
                        if ($targetedkw > 0) {
                            $this->pdf->Cell(15, '5', number_format($targetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";".$filter;
                                        
                                        $tempfilter = str_replace(",;", ";", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevtargetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevtargetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        }
                        if ($filter) {
                            if (!$prevtargetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $prevtargetedkw += $kwvisit[0][0];
                                }
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $prevtargetedkw / $total;
                            if ($prevtargetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevtargetedkw += $nprovd * $temp;
                            round($prevtargetedkw);
                        }
                        if ($prevtargetedkw > 0) {
                            $this->pdf->Cell(18, '5', number_format($prevtargetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";".$filter;
                                        
                                        $tempfilter = str_replace(",;", ";", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevyeartargetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevyeartargetedkw += $totaltargets[0][0];
                                    }
                                }
                            }
                        }
                        if ($filter) {
                            if (!$prevyeartargetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $prevyeartargetedkw += $kwvisit[0][0];
                                }
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $prevyeartargetedkw / $total;
                            if ($prevyeartargetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevyeartargetedkw += $nprovd * $temp;
                            round($prevyeartargetedkw);
                        }
                        if ($prevyeartargetedkw > 0) {
                            $this->pdf->Cell(17, '5', number_format($prevyeartargetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                        }
                        $change = $this->calcvisitdiff($targetedkw, $prevtargetedkw);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 3:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Paid', '', '', 'L', true);
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        $kwvisit = $kws->getRows();
                        $paidkw = $kwvisit[0][0];
                        //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->startdate,$this->enddate,1,1000);
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(15, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->prevstartdate,$this->prevenddate,1,1000);
                        $kwvisit = $kws->getRows();
                        $prevpaidkw = $kwvisit[0][0];
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(18, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        //			$kws = $this->requestReportData($this->profileId,array("keyword"),array("visits"),array("-visits"),'medium == cpc',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1000);
                        $kwvisit = $kws->getRows();
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(17, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                        }
                        $change = $this->calcvisitdiff($paidkw, $prevpaidkw);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 4:
						$prevyearunbrandkw = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        if ($filter) {
                            $this->pdf->Cell(17, '5', 'UnBranded', '', '', 'L', true);
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $unbrandkwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $unbrandkw = $unbrandkwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            $total = $organic[0][0] - $nprovd;
                            
                            $temp = $unbrandkw / $total;
                            if ($unbrandkw > $total) {
                                $temp = $temp - 1;
                            }
                            $unbrandkw += $nprovd * $temp;
                            round($unbrandkw);
                            if ($unbrandkw > 0) {
                                $this->pdf->Cell(15, '5', number_format($unbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                            }
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $kwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $prevunbrandkw = $kwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            $total = $prevorganic[0][0] - $nprovd;
                            
                            $temp = $prevunbrandkw / $total;
                            if ($prevunbrandkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevunbrandkw += $nprovd * $temp;
                            round($prevunbrandkw);
                            if ($prevunbrandkw > 0) {
                                $this->pdf->Cell(18, '5', number_format($prevunbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                            }
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $kwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $prevyearunbrandkw += $kwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            if ($prevorganicyear[0][0] > 0) {
                                $total = $prevorganicyear[0][0] - $nprovd;
                                $temp = $prevyearunbrandkw / $total;
                                if ($prevyearunbrandkw > $total) {
                                    $temp = $temp - 1;
                                }
                                $prevyearunbrandkw += $nprovd * $temp;
                                round($prevyearunbrandkw);
                            } else {
                                $prevyearunbrandkw = 0;
                            }
                            if ($prevyearunbrandkw > 0) {
                                $this->pdf->Cell(17, '5', number_format($prevyearunbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                            }
                            $change = $this->calcvisitdiff($unbrandkw, $prevunbrandkw);
                            if ($change > 0) {
                                $this->pdf->SetTextColor(0, 147, 16);
                                $change = "+".$change;
                            } else {
                                $this->pdf->SetTextColor(0, 51, 102);
                            }
                            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(18, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(17, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                        }
                        break;
                    case 7:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetFontSize(12);
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(60, '5', "Ranking Summary", '', '', 'L');
                        $this->pdf->Cell(22, '5', '', '', '', 'L');
                        $this->pdf->SetFontSize(9);
                        break;
                    case 9:
                        //$this->pdf->SetFillColor(49, 145, 214);
                        $this->setBarColour();
                        $this->pdf->SetTextColor(255, 255, 255);
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(32, '5', '', '', '', '', true);
                        $this->pdf->Cell(18, '5', 'Current', '', '', 'C', true);
                        $this->pdf->Cell(17, '5', 'Previous', '', '', 'C', true);
                        $this->pdf->Cell(15, '5', 'Change', '', '', 'C', true);
                        $this->setColourdark();
                        //$this->pdf->SetFillColor(193, 222, 243);
                        $this->pdf->SetTextColor(0, 0, 0);
                        break;
                    case 10:
                    	$totalmovement = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', '#1', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position = '1'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position = '1'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 11:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Top 3', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '3'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '3'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '3'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '3'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 12:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Top 5', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '5'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '5'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '5'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '5'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 13:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Page 1', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.page = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position = '1'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position = '1'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $p1 = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($p1 > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $p1 = "+".$p1;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $p1, '', '', 'C', true);
                        break;
                    case 14:
                        $this->pdf->Cell(10, '8', '', '', '', '');
                        $this->pdf->Cell(64, '5', 'Page 1 Positions Changed', '', '', 'L', true);
                        if ($p1 > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $p1, '', '', 'C', true);
                        break;
                    default:
                        $this->pdf->Cell(10, '8', '', '', '', '');
                        $this->pdf->Cell(17, '5', '', '', '', 'L');
                        $this->pdf->Cell(15, '5', '', '', '', 'L');
                        $this->pdf->Cell(18, '5', '', '', '', 'L');
                        $this->pdf->Cell(17, '5', '', '', '', 'L');
                        $this->pdf->Cell(15, '5', '', '', '', 'L');
                        break;
                }
                $this->pdf->Cell(18, '5', '', '', '', '');
                $this->pdf->SetTextColor(0, 0, 0);
                // Now just loop  through all the keywords and shit and we should be done
                $this->checkTokenValid();
                $this->pdf->Cell(52, '5', $toporganics[$y][0], '', '', 'L', true);
                $this->pdf->Cell(16, '5', $toporganics[$y][1], '', '', 'C', true);
                // Now get the previous for that keyword
                /*
                 $this->checkTokenValid();
                 $prevorg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;ga:keyword==".$toporganics[$y][0]));
                 if ($prevorg) {
                 $prevorganics = $prevorg->getRows();
                 $this->pdf->Cell(16, '5', $prevorganics[0][0], '', '', 'C', true);
                 $change = $this->calcvisitdiff($toporganics[$y][1], $prevorganics[0][0]);
                 if ($change > 0) {
                 $this->pdf->SetTextColor(0, 147, 16);
                 $change = "+".$change;
                 } else {
                 $this->pdf->SetTextColor(0, 51, 102);
                 }
                 $this->pdf->Cell(16, '5', $change."%", '', '', 'C', true);
                 } else {
                 $this->pdf->Cell(16, '5', '', '', '', 'C', true);
                 $this->pdf->SetTextColor(0, 147, 16);
                 $this->pdf->Cell(16, '5', "", '', '', 'C', true);
                 }
                 */
                $this->pdf->SetTextColor(0, 0, 0);
                // Now get the change percentage for those two
                $this->pdf->Ln(5);
            }
            $this->pdffooter();
        }
    }
    public function summaryReport() {
        if ($this->profileId > 0) {
            // Check the analytics id
            $sql = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1";
            // Get the Report Id for this client for this month
            $sql2 = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1,1";
            // Get the Report Id for this client for this month
            $reportidresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevrid = mysql_query($sql2) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw3ranking = mysql_fetch_assoc($reportidresult)) {
                $currentreportid = $kw3ranking['report_id'];
            }
            while ($prid = mysql_fetch_assoc($prevrid)) {
                $prevreportid = $prid['report_id'];
            }
            $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1'";
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $gettargetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $gettarget = mysql_fetch_assoc($gettargetkwresult);
            $tempkeyword = explode("\n", $gettarget['keywords']);
            for ($i = 0; $i < sizeof($tempkeyword); $i++) {
                if (substr($tempkeyword[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($tempkeyword[$i]);
                    if ( empty($tempkeyword[$i])) {
                        unset($tempkeyword[$i]);
                    }
                } else {
                    unset($tempkeyword[$i]);
                    
                }
            }
            array_values($tempkeyword);
            $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            if (mysql_num_rows($getstatsresult) == 0) {
                $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0'";
                $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                if (mysql_num_rows($getstatsresult) == 0) {
                    $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2'";
                    $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                }
            }
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("Traffic Profile"), 8, "Traffic Profile", '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->Cell(100, 8, date("j F Y", strtotime($this->startdate))." - ".date("j F Y", strtotime($this->enddate)), '', '', 'L');
            $this->pdf->Image('/home/rtwpfx/public_html/images/reports/traffic.png', 175, 35);
            $this->pdf->Ln(45);
            $this->insertChart();
            //$this->pdf->Image($this->chart."&chm=B,76A4FB,0,1,0", '10', '60', '190', '', 'PNG');
            $this->pdf->SetTextColor(255, 255, 255);
            $this->setBarColour();
            // $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(170, '5', "Site Usage", '', '', 'L', true);
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            //Get the Visitor Info
            $this->checkTokenValid();
            $visits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //if (count($visits->getRows()) > 0) {
            $visit = $visits->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', number_format($visit[0][0])." Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Cell(70, '12', number_format($visit[0][1])." Unique Visitors", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevvisits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$prevvisits = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',$this->prevstartdate,$this->prevenddate);
            // Get the previous month information
            $this->pdf->SetFont('Arial', '', 10);
            //	if (count($prevvisits->getRows()) > 0) {
            $previsit = $prevvisits->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $previsit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $lastyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$lastyear = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
            $this->pdf->SetFont('Arial', '', 10);
            //if (count($lastyear->getRows()) > 0) {
            $lastvisit = $lastyear->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $lastvisit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $lastvisit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(8);
            if ($this->ecom == "Y") {// If the client has goals we will display the goal data
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $ecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //if (count($goals->getRows()) > 0) {
                $ecom = $ecommerce->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($ecom[0][0])." Transactions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Cell(70, '12', "$".number_format($ecom[0][1], 2)." Total Revenue", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevecom = $prevmonthecommerce->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevecom[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevecom[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyearecom = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyearecom1 = $prevyearecom->getRows();
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevyearecom1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevyearecom1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            } else if ($this->goals = "Y") {// If the client has goals we will display the goal data
            
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $goals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //if (count($goals->getRows()) > 0) {
                $goal = $goals->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($goal[0][0])." Goal Conversions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Calculate the conversion rate
                $convrate = $this->calcconvrate($goal[0][0], $goal[0][1]);
                $this->pdf->Cell(70, '12', $convrate."% Conversion Rate", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthgoals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevgoal = $prevmonthgoals->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($goal[0][0], $prevgoal[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevgoal[0][0], $goal[0][1], $prevgoal[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyeargoal = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyeargoal1 = $prevyeargoal->getRows();
                $transperc = $this->calcvisitdiff($goal[0][0], $prevyeargoal1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevyeargoal1[0][0], $goal[0][1], $prevyeargoal1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            }
            // Now we add the Bounce Rate Stuff
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            $this->checkTokenValid();
            $bounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$bounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->startdate,$this->enddate);
            $bouncevisit = $bounce->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', round($bouncevisit[0][0], 2)."% Bounce Rate", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Get the % New Visits
            $this->pdf->Cell(70, '12', round($bouncevisit[0][1], 2)."% New Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now find the previous information
            $this->pdf->Ln(5);
            $this->pdf->SetFont('Arial', '', 10);
            $this->checkTokenValid();
            $prevbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->prevstartdate,$this->prevenddate);
            $prevbouncevisit = $prevbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevbouncevisit[0][0]);
            if ($transperc > 0) {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            } else {
                $this->pdf->SetTextColor(0, 174, 16);
            }
            $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevyearbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevyearbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
            // Get the previous month information
            $prevyearbouncevisit = $prevyearbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevyearbouncevisit[0][0]);
            if ($transperc < 0) {
                $this->pdf->SetTextColor(0, 174, 16);
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            }
            $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevyearbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now we get the traffic Summary
            $this->pdf->Ln(13);
            $this->pdf->Cell(10, '8', '', '', '', '');
            $this->pdf->SetFontSize(12);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(60, '12', "Traffic Summary", '', '', 'L');
            $this->pdf->Cell(40, '12', '', '', '', '');
            $this->pdf->Cell(70, '12', 'Top Organic Keywords', '', '', 'L');
            $this->pdf->Cell(30, '12', '', '', '', '');
            $this->pdf->SetFontSize(9);
            $this->pdf->Ln(13);
            //$this->pdf->SetFillColor(49, 145, 214);
            $this->setBarColour();
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(17, '5', '', '', '', '', true);
            $this->pdf->Cell(15, '5', 'Visits', '', '', 'C', true);
            $this->pdf->Cell(18, '5', 'Last Month', '', '', 'C', true);
            $this->pdf->Cell(17, '5', 'Last Year', '', '', 'C', true);
            $this->pdf->Cell(15, '5', 'Change', '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->Cell(36, '5', '', '', '', '', true);
            $this->pdf->Cell(16, '5', 'Current', '', '', 'C', true);
            $this->pdf->Cell(16, '5', 'Previous', '', '', 'C', true);
            $this->pdf->Cell(16, '5', 'Change', '', '', 'C', true);
            $this->pdf->Ln(5);
            // Get the Not Provided Keywords
            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
            $nprov = $nprovided->getRows();
            if ($nprovided->getRows() > 0) {
                $nprovd = $nprov[0][0];
            }
            // Build the filters
            $targetkwsql = "SELECT CL.excluded_keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['excluded_keywords']);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($keywords[$i]);
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    }
                    if (strlen($keywords[$i]) > 0) {
                        $filterkw[] = $keywords[$i];
                    }
                }
            }
            if (is_array($filterkw)) {
                $filterkw = array_values($filterkw);
                usort($filterkw, array($this, 'sort'));
                if ($filterkw[0] == "") {
                    unset($filterkw[0]);
                    $filterkw = array_values($filterkw);
                }
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    for ($x = 0; $x < sizeof($filterkw); $x++) {
                        if (stristr($filterkw[$x], $filterkw[$i]) && $filterkw[$i] != $filterkw[$x]) {
                            unset($filterkw[$x]);
                        }
                    }
                    $filterkw = array_values($filterkw);
                }
                $filterkw = array_values($filterkw);
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    $filterkw[$i] = "ga:keyword!@".$filterkw[$i];
                }
                $filter = implode(";", $filterkw);
                if (substr($filter, -1) == ";") {
                    $filter = substr_replace($filter, "", -1, 1);
                }
            }
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['keywords']);
            }
            for ($i = 0; $i <= sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    } else {
                        $keywords[$i] = trim($keywords[$i]);
                    }
                } else {
                    unset($keywords[$i]);
                }
            }
            $keywords = array_values($keywords);
            usort($keywords, array($this, 'sort'));
            if ($keywords[0] == "") {
                unset($keywords[0]);
                $keywords = array_values($keywords);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                for ($x = 0; $x < sizeof($keywords); $x++) {
                    if (@stristr($keywords[$x], $keywords[$i]) && $keywords[$i] != $keywords[$x]) {
                        unset($keywords[$x]);
                    }
                }
                $keywords = array_values($keywords);
            }
            $keywords = array_values($keywords);
            for ($i = 0; $i < sizeof($keywords); $i++) {
                $keywords[$i] = trim("ga:keyword=@".$keywords[$i]);
            }
            $myfilter = implode(",", $keywords);
            if (substr($myfilter, -1) == ",") {
                $myfilter = substr_replace($myfilter, "", -1, 1);
            }
            $myfilter = str_replace("ga:keyword=@,", "", $myfilter);
            if ($filter) {
                $toporg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".$filter, "max-results"=>"15"));
            } else {
                $toporg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!=(not provided)", "max-results"=>"15"));
            }
            $toporganics = $toporg->getRows();
            for ($y = 0; $y < 15; $y++) {
                $mod = $y % 2;
                if ($mod == 0) {
                    $this->setColourlight();
                } else {
                    $this->setColourdark();
                }
                switch ($y) {
                    case 0:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Total', '', '', 'L', true);
                        $this->pdf->Cell(15, '5', number_format($visit[0][0]), '', '', 'C', true);
                        $this->pdf->Cell(18, '5', number_format($previsit[0][0]), '', '', 'C', true);
                        $this->pdf->Cell(17, '5', number_format($lastvisit[0][0]), '', '', 'C', true);
                        $change = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 1:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Organic', '', '', 'L', true);
                        // Now get the organic visits for the month
                        $this->checkTokenValid();
                        $org = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$org = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->startdate,$this->enddate);
                        $organic = $org->getRows();
                        $this->pdf->Cell(15, '5', number_format($organic[0][0]), '', '', 'C', true);
                        $this->checkTokenValid();
                        $orglast = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$orglast = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->prevstartdate,$this->prevenddate);
                        $prevorganic = $orglast->getRows();
                        $this->pdf->Cell(18, '5', number_format($prevorganic[0][0]), '', '', 'C', true);
                        $this->checkTokenValid();
                        $orgyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
                        //			$orgyear = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',date("Y-m-d" , strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
                        $prevorganicyear = $orgyear->getRows();
                        $this->pdf->Cell(17, '5', number_format($prevorganicyear[0][0]), '', '', 'C', true);
                        $change = $this->calcvisitdiff($organic[0][0], $prevorganic[0][0]);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 2:
                    	$targetedkw = 0;
						$prevtargetedkw = 0;
						$prevyeartargetedkw = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Targeted', '', '', 'L', true);
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter)));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                	if (mb_strlen(trim($tempfilter).",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                		$tempfilter .= $tempkw[$tmpcnt].",";
									} else { // Now we have to do the query
										 $tempfilter = $tempfilter.";".$filter;
										 $tempfilter = str_replace(",;", ";", $tempfilter);
										 $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
										 // Now add the rows together;
										 unset($tempfilter);
										 $totaltargets = $kws->getRows();
										 $targetedkw += $totaltargets[0][0];
									}
                                }
                            } else {
								$totaltargets = $kws->getRows();
                                $targetedkw += $totaltargets[0][0];
							}
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $targetedkw += $totaltargets[0][0];
                                    }
                                }
                            } else {
								$totaltargets = $kws->getRows();
                                $targetedkw += $totaltargets[0][0];
							}
                        }
                        if ($filter) {
                            if (!$targetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $targetedkw += $kwvisit[0][0];
                                }
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $targetedkw / $total;
                            if ($targetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $targetedkw += $nprovd * $temp;
                            round($targetedkw);
                        }
                        
                        if ($targetedkw > 0) {
                            $this->pdf->Cell(15, '5', number_format($targetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";".$filter;
                                        
                                        $tempfilter = str_replace(",;", ";", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevtargetedkw += $totaltargets[0][0];
                                    }
                                }
                            } else {
								$totaltargets = $kws->getRows();
                                $prevtargetedkw += $totaltargets[0][0];
							}
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevtargetedkw += $totaltargets[0][0];
                                    }
                                }
                            } else {
								 $totaltargets = $kws->getRows();
                                 $prevtargetedkw += $totaltargets[0][0];
							}
                        }
                        if ($filter) {
                            if (!$prevtargetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $prevtargetedkw += $kwvisit[0][0];
                                }
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $prevtargetedkw / $total;
                            if ($prevtargetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevtargetedkw += $nprovd * $temp;
                            round($prevtargetedkw);
                        }
                        if ($prevtargetedkw > 0) {
                            $this->pdf->Cell(18, '5', number_format($prevtargetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        if ($myfilter && $filter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter.";".$filter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt].";".$filter) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";".$filter;
                                        
                                        $tempfilter = str_replace(",;", ";", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevyeartargetedkw += $totaltargets[0][0];
                                    }
                                }
                            } else {
								 $totaltargets = $kws->getRows();
                                 $prevyeartargetedkw += $totaltargets[0][0];
							}
                        } elseif ($myfilter) {
                            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
                            if (mb_strlen($myfilter) > 862) {
                                $tempkw = explode(",", $myfilter);
                                for ($tmpcnt = 0; $tmpcnt < sizeof($tempkw); $tmpcnt++) {
                                    if (mb_strlen($tempfilter.",".$tempkw[$tmpcnt]) <= 862) {
                                        $tempfilter .= $tempkw[$tmpcnt].",";
                                    } else { // Now we have to do the query
                                        $tempfilter = $tempfilter.";test";
                                        $tempfilter = str_replace(",;test", "", $tempfilter);
                                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($tempfilter)));
                                        // Now add the rows together;
                                        unset($tempfilter);
                                        $totaltargets = $kws->getRows();
                                        $prevyeartargetedkw += $totaltargets[0][0];
                                    }
                                }
                            } else {
								$totaltargets = $kws->getRows();
								$prevyeartargetedkw += $totaltargets[0][0];
							}
                        }
                        if ($filter) {
                            if (!$prevyeartargetedkw) {
                                if ($kws->getRows() > 0) {
                                    $kwvisit = $kws->getRows();
                                    $prevyeartargetedkw += $kwvisit[0][0];
                                }
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            //Take Not Provided into consideration
                            $total = $organic[0][0] - $nprovd;
                            $temp = $prevyeartargetedkw / $total;
                            if ($prevyeartargetedkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevyeartargetedkw += $nprovd * $temp;
                            round($prevyeartargetedkw);
                        }
                        if ($prevyeartargetedkw > 0) {
                            $this->pdf->Cell(17, '5', number_format($prevyeartargetedkw), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                        }
                        $change = $this->calcvisitdiff($targetedkw, $prevtargetedkw);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 3:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(17, '5', 'Paid', '', '', 'L', true);
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        $kwvisit = $kws->getRows();
                        $paidkw = $kwvisit[0][0];
                        //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->startdate,$this->enddate,1,1000);
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(15, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->prevstartdate,$this->prevenddate,1,1000);
                        $kwvisit = $kws->getRows();
                        $prevpaidkw = $kwvisit[0][0];
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(18, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                        }
                        $this->checkTokenValid();
                        $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
                        //			$kws = $this->requestReportData($this->profileId,array("keyword"),array("visits"),array("-visits"),'medium == cpc',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1000);
                        $kwvisit = $kws->getRows();
                        if ($kwvisit[0][0] > 0) {
                            $this->pdf->Cell(17, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                        }
                        $change = $this->calcvisitdiff($paidkw, $prevpaidkw);
                        if ($change > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = "+".$change;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        break;
                    case 4:
						$prevyearunbrandkw = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        if ($filter) {
                            $this->pdf->Cell(17, '5', 'UnBranded', '', '', 'L', true);
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $unbrandkwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $unbrandkw = $unbrandkwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            $total = $organic[0][0] - $nprovd;
                            
                            $temp = $unbrandkw / $total;
                            if ($unbrandkw > $total) {
                                $temp = $temp - 1;
                            }
                            $unbrandkw += $nprovd * $temp;
                            round($unbrandkw);
                            if ($unbrandkw > 0) {
                                $this->pdf->Cell(15, '5', number_format($unbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                            }
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $kwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $prevunbrandkw = $kwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            $total = $prevorganic[0][0] - $nprovd;
                            
                            $temp = $prevunbrandkw / $total;
                            if ($prevunbrandkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevunbrandkw += $nprovd * $temp;
                            round($prevunbrandkw);
                            if ($prevunbrandkw > 0) {
                                $this->pdf->Cell(18, '5', number_format($prevunbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(18, '5', '0', '', '', 'C', true);
                            }
                            $this->checkTokenValid();
                            $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                            $kwvisit = $unbrandedkws->getRows();
                            if ($unbrandedkws->getRows() > 0) {
                                $prevyearunbrandkw += $kwvisit[0][1];
                            }
                            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
                            $nprov = $nprovided->getRows();
                            if ($nprovided->getRows() > 0) {
                                $nprovd = $nprov[0][0];
                            } else {
                                unset($nprovd);
                            }
                            $total = $prevorganicyear[0][0] - $nprovd;
                            $temp = $prevyearunbrandkw / $total;
                            if ($prevyearunbrandkw > $total) {
                                $temp = $temp - 1;
                            }
                            $prevyearunbrandkw += $nprovd * $temp;
                            round($prevyearunbrandkw);
                            if ($prevyearunbrandkw > 0) {
                                $this->pdf->Cell(17, '5', number_format($prevyearunbrandkw), '', '', 'C', true);
                            } else {
                                $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                            }
                            $change = $this->calcvisitdiff($unbrandkw, $prevunbrandkw);
                            if ($change > 0) {
                                $this->pdf->SetTextColor(0, 147, 16);
                                $change = "+".$change;
                            } else {
                                $this->pdf->SetTextColor(0, 51, 102);
                            }
                            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
                        } else {
                            $this->pdf->Cell(17, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(18, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(17, '5', '', '', '', 'L', true);
                            $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                        }
                        break;
                    case 7:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetFontSize(12);
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(60, '5', "Ranking Summary", '', '', 'L');
                        $this->pdf->Cell(22, '5', '', '', '', 'L');
                        $this->pdf->SetFontSize(9);
                        break;
                    case 9:
                        //$this->pdf->SetFillColor(49, 145, 214);
                        $this->setBarColour();
                        $this->pdf->SetTextColor(255, 255, 255);
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->Cell(32, '5', '', '', '', '', true);
                        $this->pdf->Cell(18, '5', 'Current', '', '', 'C', true);
                        $this->pdf->Cell(17, '5', 'Previous', '', '', 'C', true);
                        $this->pdf->Cell(15, '5', 'Change', '', '', 'C', true);
                        $this->setColourdark();
                        //$this->pdf->SetFillColor(193, 222, 243);
                        $this->pdf->SetTextColor(0, 0, 0);
                        break;
                    case 10:
                    	$totalmovement = 0;
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', '#1', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position = '1'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position = '1'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 11:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Top 3', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '3'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '3'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '3'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '3'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 12:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Top 5', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '5'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '5'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '5'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '5'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $theone = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($theone > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $theone = "+".$theone;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
                        break;
                    case 13:
                        $this->pdf->Cell(10, '5', '', '', '', '');
                        $this->pdf->SetTextColor(0, 0, 0);
                        $this->pdf->Cell(28, '5', 'Page 1', '', '', 'L', true);
                        // Get the first ranking keywords
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.page = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prevseid = '1';
                        if (mysql_num_rows($sqlresult) == 0) {
                            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.page = '1'";
                            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                            $prevseid = '0';
                            if (mysql_num_rows($sqlresult) == 0) {
                                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.page = '1'";
                                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                                $prevseid = '2';
                            }
                        }
                        $current1 = mysql_num_rows($sqlresult);
                        $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
                        $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.page = '1'";
                        $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                        $prev1 = mysql_num_rows($sqlresult);
                        $p1 = $current1 - $prev1;
                        $totalmovement = $totalmovement + $theone;
                        $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
                        if ($p1 > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $p1 = "+".$p1;
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $p1, '', '', 'C', true);
                        break;
                    case 14:
                        $this->pdf->Cell(10, '8', '', '', '', '');
                        $this->pdf->Cell(64, '5', 'Page 1 Positions Changed', '', '', 'L', true);
                        if ($p1 > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                        } else {
                            $this->pdf->SetTextColor(0, 51, 102);
                        }
                        $this->pdf->Cell(18, '5', $p1, '', '', 'C', true);
                        break;
                    default:
                        $this->pdf->Cell(10, '8', '', '', '', '');
                        $this->pdf->Cell(17, '5', '', '', '', 'L');
                        $this->pdf->Cell(15, '5', '', '', '', 'L');
                        $this->pdf->Cell(18, '5', '', '', '', 'L');
                        $this->pdf->Cell(17, '5', '', '', '', 'L');
                        $this->pdf->Cell(15, '5', '', '', '', 'L');
                        break;
                }
                $this->pdf->Cell(18, '5', '', '', '', '');
                $this->pdf->SetTextColor(0, 0, 0);
                // Now just loop  through all the keywords and shit and we should be done
                $this->checkTokenValid();
                $this->pdf->Cell(36, '5', $toporganics[$y][0], '', '', 'L', true);
                $this->pdf->Cell(16, '5', $toporganics[$y][1], '', '', 'C', true);
                // Now get the previous for that keyword
                $this->checkTokenValid();
                $prevorg = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;ga:keyword==".$toporganics[$y][0]));
                if ($prevorg) {
                    $prevorganics = $prevorg->getRows();
                    $this->pdf->Cell(16, '5', $prevorganics[0][0], '', '', 'C', true);
                    $change = $this->calcvisitdiff($toporganics[$y][1], $prevorganics[0][0]);
                    if ($change > 0) {
                        $this->pdf->SetTextColor(0, 147, 16);
                        $change = "+".$change;
                    } else {
                        $this->pdf->SetTextColor(0, 51, 102);
                    }
                    $this->pdf->Cell(16, '5', $change."%", '', '', 'C', true);
                } else {
                    $this->pdf->Cell(16, '5', '', '', '', 'C', true);
                    $this->pdf->SetTextColor(0, 147, 16);
                    $this->pdf->Cell(16, '5', "", '', '', 'C', true);
                }
                $this->pdf->SetTextColor(0, 0, 0);
                // Now get the change percentage for those two
                $this->pdf->Ln(5);
            }
            $this->pdffooter();
        }
    }
    public function summaryOldReport() {
        if ($this->profileId > 0) {
            // Check the analytics id
            $sql = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1";
            // Get the Report Id for this client for this month
            $sql2 = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1,1";
            // Get the Report Id for this client for this month
            $reportidresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevrid = mysql_query($sql2) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw3ranking = mysql_fetch_assoc($reportidresult)) {
                $currentreportid = $kw3ranking['report_id'];
            }
            while ($prid = mysql_fetch_assoc($prevrid)) {
                $prevreportid = $prid['report_id'];
            }
            $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1'";
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $gettargetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $gettarget = mysql_fetch_assoc($gettargetkwresult);
            $tempkeyword = explode("\n", $gettarget['keywords']);
            for ($i = 0; $i < sizeof($tempkeyword); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($keywords[$i]);
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    }
                } else {
                    unset($keywords[$i]);
                    
                }
            }
            array_values($tempkeyword);
            $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            if (mysql_num_rows($getstatsresult) == 0) {
                $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0'";
                $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                if (mysql_num_rows($getstatsresult) == 0) {
                    $getstats = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2'";
                    $getstatsresult = mysql_query($getstats) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                }
            }
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("Traffic Profile"), 8, "Traffic Profile", '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->Cell(100, 8, date("j F Y", strtotime($this->startdate))." - ".date("j F Y", strtotime($this->enddate)), '', '', 'L');
            $this->pdf->Image('/home/rtwpfx/public_html/images/reports/traffic.png', 175, 35);
            $this->pdf->Ln(45);
            $this->pdf->Image($this->chart."&chm=B,76A4FB,0,1,0", '10', '60', '190', '', 'PNG');
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(170, '5', "Site Usage", '', '', 'L', true);
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            //Get the Visitor Info
            $this->checkTokenValid();
            $visits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //if (count($visits->getRows()) > 0) {
            $visit = $visits->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', number_format($visit[0][0])." Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Cell(70, '12', number_format($visit[0][1])." Unique Visitors", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevvisits = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$prevvisits = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',$this->prevstartdate,$this->prevenddate);
            // Get the previous month information
            $this->pdf->SetFont('Arial', '', 10);
            //	if (count($prevvisits->getRows()) > 0) {
            $previsit = $prevvisits->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $previsit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $lastyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits,ga:visitors', array("sort"=>"ga:visits"));
            //			$lastyear = $this->requestReportData($this->profileId,array("month"),array("visits","visitors"),array("-visits"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
            $this->pdf->SetFont('Arial', '', 10);
            //if (count($lastyear->getRows()) > 0) {
            $lastvisit = $lastyear->getRows();
            $visitperc = $this->calcvisitdiff($visit[0][0], $lastvisit[0][0]);
            if ($visitperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitperc = "+".$visitperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $visitorperc = $this->calcvisitdiff($visit[0][1], $lastvisit[0][1]);
            if ($visitorperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $visitorperc = "+".$visitorperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $visitorperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            //}
            $this->pdf->Ln(8);
            if ($this->ecom == "Y") {// If the client has goals we will display the goal data
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $ecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //if (count($goals->getRows()) > 0) {
                $ecom = $ecommerce->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($ecom[0][0])." Transactions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Cell(70, '12', "$".number_format($ecom[0][1], 2)." Total Revenue", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthecommerce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevecom = $prevmonthecommerce->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevecom[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevecom[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyearecom = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:transactions,ga:transactionRevenue', array("sort"=>"ga:transactions"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyearecom1 = $prevyearecom->getRows();
                $transperc = $this->calcvisitdiff($ecom[0][0], $prevyearecom1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcvisitdiff($ecom[0][1], $prevyearecom1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            } else if ($this->goals = "Y") {// If the client has goals we will display the goal data
            
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFontSize(15);
                //Get the Goal Info
                $this->checkTokenValid();
                $goals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //if (count($goals->getRows()) > 0) {
                $goal = $goals->getRows();
                $this->pdf->SetFont('Arial', 'B', 15);
                $this->pdf->Cell(70, '12', number_format($goal[0][0])." Goal Conversions", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Calculate the conversion rate
                $convrate = $this->calcconvrate($goal[0][0], $goal[0][1]);
                $this->pdf->Cell(70, '12', $convrate."% Conversion Rate", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                // Now find the previous information
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevmonthgoals = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevmonthgoals = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',$this->prevstartdate,$this->prevenddate);
                // Get the previous month information
                //if (count($prevmonthgoals->getRows()) > 0) {
                $prevgoal = $prevmonthgoals->getRows();
                $this->pdf->SetFont('Arial', '', 10);
                $transperc = $this->calcvisitdiff($goal[0][0], $prevgoal[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevgoal[0][0], $goal[0][1], $prevgoal[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } elseif ($revperc != "") {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
                } else {
                    $this->pdf->Cell(70, '12', '', '', '', 'R');
                }
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $this->pdf->Ln(5);
                $this->checkTokenValid();
                $prevyeargoal = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:goalCompletionsAll,ga:visits', array("sort"=>"ga:goalCompletionsAll"));
                //				$prevyeargoal = $this->requestReportData($this->profileId,array("month"),array("goalCompletionsAll","visits"),array("-goalCompletionsAll"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
                // Get the previous month information
                $this->pdf->SetFont('Arial', '', 10);
                $prevyeargoal1 = $prevyeargoal->getRows();
                $transperc = $this->calcvisitdiff($goal[0][0], $prevyeargoal1[0][0]);
                if ($transperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $transperc = "+".$transperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
                $revperc = $this->calcconvdiff($goal[0][0], $prevyeargoal1[0][0], $goal[0][1], $prevyeargoal1[0][1]);
                if ($revperc > 0) {
                    $this->pdf->SetTextColor(0, 174, 16);
                    $revperc = "+".$revperc;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
                $this->pdf->Cell(30, '12', '', '', '', 'C');
            }
            // Now we add the Bounce Rate Stuff
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(15);
            $this->checkTokenValid();
            $bounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$bounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->startdate,$this->enddate);
            $bouncevisit = $bounce->getRows();
            $this->pdf->SetFont('Arial', 'B', 15);
            $this->pdf->Cell(70, '12', round($bouncevisit[0][0], 2)."% Bounce Rate", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Get the % New Visits
            $this->pdf->Cell(70, '12', round($bouncevisit[0][1], 2)."% New Visits", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now find the previous information
            $this->pdf->Ln(5);
            $this->pdf->SetFont('Arial', '', 10);
            $this->checkTokenValid();
            $prevbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',$this->prevstartdate,$this->prevenddate);
            $prevbouncevisit = $prevbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevbouncevisit[0][0]);
            if ($transperc > 0) {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            } else {
                $this->pdf->SetTextColor(0, 174, 16);
            }
            $this->pdf->Cell(70, '12', $transperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last month", '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $this->pdf->Ln(5);
            $this->checkTokenValid();
            $prevyearbounce = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visitBounceRate,ga:percentNewVisits', array("sort"=>"ga:visitBounceRate"));
            //			$prevyearbounce = $this->requestReportData($this->profileId,array("month"),array("visitBounceRate","percentNewVisits"),array("-visitBounceRate"),'',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d" , strtotime($this->enddate . " - 1 year")));
            // Get the previous month information
            $prevyearbouncevisit = $prevyearbounce->getRows();
            $transperc = $this->calcvisitdiff($bouncevisit[0][0], $prevyearbouncevisit[0][0]);
            if ($transperc < 0) {
                $this->pdf->SetTextColor(0, 174, 16);
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
                $transperc = "+".$transperc;
            }
            $this->pdf->Cell(70, '12', $transperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            $revperc = $this->calcvisitdiff($bouncevisit[0][1], $prevyearbouncevisit[0][1]);
            if ($revperc > 0) {
                $this->pdf->SetTextColor(0, 174, 16);
                $revperc = "+".$revperc;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(70, '12', $revperc."% from last ".date("F", strtotime($this->startdate)), '', '', 'R');
            $this->pdf->Cell(30, '12', '', '', '', 'C');
            // Now we get the traffic Summary
            $this->pdf->Ln(13);
            $this->pdf->Cell(10, '8', '', '', '', '');
            $this->pdf->SetFontSize(12);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(60, '12', "Traffic Summary", '', '', 'L');
            $this->pdf->Cell(40, '12', '', '', '', '');
            $this->pdf->Cell(70, '12', 'Ranking Summary', '', '', 'L');
            $this->pdf->Cell(30, '12', '', '', '', '');
            $this->pdf->SetFontSize(9);
            $this->pdf->Ln(13);
            $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', '', '', '', '', true);
            $this->pdf->Cell(15, '5', 'Visits', '', '', 'C', true);
            $this->pdf->Cell(20, '5', 'Last Month', '', '', 'C', true);
            $this->pdf->Cell(17, '5', 'Last Year', '', '', 'C', true);
            $this->pdf->Cell(15, '5', 'Change', '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', '', '', '', '', true);
            $this->pdf->Cell(18, '5', 'Current', '', '', 'C', true);
            $this->pdf->Cell(18, '5', 'Previous', '', '', 'C', true);
            $this->pdf->Cell(18, '5', 'Change', '', '', 'C', true);
            $this->pdf->Ln(5);
            $this->pdf->SetFillColor(234, 244, 251);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', 'Total', '', '', 'L', true);
            $this->pdf->Cell(15, '5', number_format($visit[0][0]), '', '', 'C', true);
            $this->pdf->Cell(20, '5', number_format($previsit[0][0]), '', '', 'C', true);
            $this->pdf->Cell(17, '5', number_format($lastvisit[0][0]), '', '', 'C', true);
            $change = $this->calcvisitdiff($visit[0][0], $previsit[0][0]);
            if ($change > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $change = "+".$change;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(15, '5', '#1', '', '', 'L', true);
            // Get the first ranking keywords
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position = '1'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevseid = '1';
            if (mysql_num_rows($sqlresult) == 0) {
                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position = '1'";
                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                $prevseid = '0';
                if (mysql_num_rows($sqlresult) == 0) {
                    $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position = '1'";
                    $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                    $prevseid = '2';
                }
            }
            $current1 = mysql_num_rows($sqlresult);
            $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position = '1'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prev1 = mysql_num_rows($sqlresult);
            $theone = $current1 - $prev1;
            $totalmovement = $totalmovement + $theone;
            $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
            if ($theone > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $theone = "+".$theone;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
            // Row 2
            $this->pdf->Ln(5);
            $this->pdf->SetFillColor(193, 222, 243);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', 'Organic', '', '', 'L', true);
            // Now get the organic visits for the month
            $this->checkTokenValid();
            $org = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
            //			$org = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->startdate,$this->enddate);
            $organic = $org->getRows();
            $this->pdf->Cell(15, '5', number_format($organic[0][0]), '', '', 'C', true);
            $this->checkTokenValid();
            $orglast = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
            //			$orglast = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',$this->prevstartdate,$this->prevenddate);
            $prevorganic = $orglast->getRows();
            $this->pdf->Cell(20, '5', number_format($prevorganic[0][0]), '', '', 'C', true);
            $this->checkTokenValid();
            $orgyear = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==organic"));
            //			$orgyear = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic',date("Y-m-d" , strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1);
            $prevorganicyear = $orgyear->getRows();
            $this->pdf->Cell(17, '5', number_format($prevorganicyear[0][0]), '', '', 'C', true);
            $change = $this->calcvisitdiff($organic[0][0], $prevorganic[0][0]);
            if ($change > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $change = "+".$change;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(15, '5', 'Top 3', '', '', 'L', true);
            // Get the first ranking keywords
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '3'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevseid = '1';
            if (mysql_num_rows($sqlresult) == 0) {
                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '3'";
                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                $prevseid = '0';
                if (mysql_num_rows($sqlresult) == 0) {
                    $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '3'";
                    $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                    $prevseid = '2';
                }
            }
            $current1 = mysql_num_rows($sqlresult);
            $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '3'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prev1 = mysql_num_rows($sqlresult);
            $theone = $current1 - $prev1;
            $totalmovement = $totalmovement + $theone;
            $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
            if ($theone > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $theone = "+".$theone;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
            $this->pdf->Ln(5);
            // Now we get the paid keywords
            $this->pdf->SetFillColor(234, 244, 251);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', 'Paid', '', '', 'L', true);
            $this->checkTokenValid();
            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
            $kwvisit = $kws->getRows();
            $paidkw = $kwvisit[0][0];
            //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->startdate,$this->enddate,1,1000);
            if ($kwvisit[0][0] > 0) {
                $this->pdf->Cell(15, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
            } else {
                $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
            }
            $this->checkTokenValid();
            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
            //			$kws = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium == cpc',$this->prevstartdate,$this->prevenddate,1,1000);
            $kwvisit = $kws->getRows();
            $prevpaidkw = $kwvisit[0][0];
            if ($kwvisit[0][0] > 0) {
                $this->pdf->Cell(20, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
            } else {
                $this->pdf->Cell(20, '5', '0', '', '', 'C', true);
            }
            $this->checkTokenValid();
            $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("sort"=>"ga:visits", "filters"=>"ga:medium==cpc"));
            //			$kws = $this->requestReportData($this->profileId,array("keyword"),array("visits"),array("-visits"),'medium == cpc',date("Y-m-d", strtotime($this->startdate . " - 1 year")),date("Y-m-d", strtotime($this->enddate . " - 1 year")),1,1000);
            $kwvisit = $kws->getRows();
            if ($kwvisit[0][0] > 0) {
                $this->pdf->Cell(17, '5', number_format($kwvisit[0][0]), '', '', 'C', true);
            } else {
                $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
            }
            $change = $this->calcvisitdiff($paidkw, $prevpaidkw);
            if ($change > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $change = "+".$change;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(15, '5', 'Top 5', '', '', '', true);
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.position <= '5'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevseid = '1';
            if (mysql_num_rows($sqlresult) == 0) {
                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.position <= '5'";
                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                $prevseid = '0';
                if (mysql_num_rows($sqlresult) == 0) {
                    $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.position <= '5'";
                    $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                    $prevseid = '2';
                }
            }
            $current1 = mysql_num_rows($sqlresult);
            $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.position <= '5'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prev1 = mysql_num_rows($sqlresult);
            $theone = $current1 - $prev1;
            $totalmovement = $totalmovement + $theone;
            $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
            if ($theone > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $theone = "+".$theone;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
            $this->pdf->Ln(5);
            $this->pdf->SetFillColor(193, 222, 243);
            $this->pdf->SetTextColor(0, 0, 0);
            unset($keywords);
            unset($targetedkw);
            unset($prevtargetedkw);
            unset($prevyeartargetedkw);
            // Now we get the targeted keywords
            $this->pdf->SetFillColor(193, 222, 243);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(15, '5', 'Targeted', '', '', 'L', true);
            $nprovided = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword==(not provided)"));
            $nprov = $nprovided->getRows();
            if ($nprovided->getRows() > 0) {
                $nprovd = $nprov[0][0];
            }
            $targetkwsql = "SELECT CL.excluded_keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['excluded_keywords']);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    $keywords[$i] = trim($keywords[$i]);
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    }
                    if (strlen($keywords[$i]) > 0) {
                        $filterkw[] = $keywords[$i];
                    }
                }
            }
            if (is_array($filterkw)) {
                $filterkw = array_values($filterkw);
                usort($filterkw, array($this, 'sort'));
                if ($filterkw[0] == "") {
                    unset($filterkw[0]);
                    $filterkw = array_values($filterkw);
                }
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    for ($x = 0; $x < sizeof($filterkw); $x++) {
                        if (stristr($filterkw[$x], $filterkw[$i]) && $filterkw[$i] != $filterkw[$x]) {
                            unset($filterkw[$x]);
                        }
                    }
                    $filterkw = array_values($filterkw);
                }
                $filterkw = array_values($filterkw);
                for ($i = 0; $i < sizeof($filterkw); $i++) {
                    $filterkw[$i] = "ga:keyword!@".$filterkw[$i];
                }
                $filter = implode(";", $filterkw);
                if (substr($filter, -1) == ";") {
                    $filter = substr_replace($filter, "", -1, 1);
                }
            }
            $targetkwsql = "SELECT CL.keywords FROM clientstatus as CL WHERE CL.company_id = '".$this->cid."'";
            $targetkwresult = mysql_query($targetkwsql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            while ($kw = mysql_fetch_assoc($targetkwresult)) {
                $keywords = explode("\n", $kw['keywords']);
            }
            for ($i = 0; $i <= sizeof($keywords); $i++) {
                if (substr($keywords[$i], 0, 1) != "*") {
                    if ( empty($keywords[$i])) {
                        unset($keywords[$i]);
                    } else {
                        $keywords[$i] = trim($keywords[$i]);
                    }
                } else {
                    unset($keywords[$i]);
                }
            }
            $keywords = array_values($keywords);
            usort($keywords, array($this, 'sort'));
            if ($keywords[0] == "") {
                unset($keywords[0]);
                $keywords = array_values($keywords);
            }
            for ($i = 0; $i < sizeof($keywords); $i++) {
                for ($x = 0; $x < sizeof($keywords); $x++) {
                    if (stristr($keywords[$x], $keywords[$i]) && $keywords[$i] != $keywords[$x]) {
                        unset($keywords[$x]);
                    }
                }
                $keywords = array_values($keywords);
            }
            $keywords = array_values($keywords);
            for ($i = 0; $i < sizeof($keywords); $i++) {
                $keywords[$i] = "ga:keyword=@".$keywords[$i];
            }
            $myfilter = implode(",", $keywords);
            if (substr($myfilter, -1) == ",") {
                $myfilter = substr_replace($myfilter, "", -1, 1);
            }
            $this->checkTokenValid();
            if (!$filter) {
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
            } else {
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
            }
            if ($kws->getRows() > 0) {
                $kwvisit = $kws->getRows();
                $targetedkw += $kwvisit[0][0];
            }
            //Take Not Provided into consideration
            $total = $organic[0][0] - $nprovd;
            $temp = $targetedkw / $total;
            if ($targetedkw > $total) {
                $temp = $temp - 1;
            }
            $targetedkw += $nprovd * $temp;
            round($targetedkw);
            if ($targetedkw > 0) {
                $this->pdf->Cell(15, '5', number_format($targetedkw), '', '', 'C', true);
            } else {
                $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
            }
            $this->checkTokenValid();
            if (!$filter) {
            
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
            } else {
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
            }
            if ($kws->getRows() > 0) {
                $kwvisit = $kws->getRows();
                $prevtargetedkw += $kwvisit[0][0];
            }
            //Take Not Provided into consideration
            $total = $organic[0][0] - $nprovd;
            $temp = $prevtargetedkw / $total;
            if ($prevtargetedkw > $total) {
                $temp = $temp - 1;
            }
            $prevtargetedkw += $nprovd * $temp;
            round($prevtargetedkw);
            if ($prevtargetedkw > 0) {
                $this->pdf->Cell(20, '5', number_format($prevtargetedkw), '', '', 'C', true);
            } else {
                $this->pdf->Cell(20, '5', '0', '', '', 'C', true);
            }
            $this->checkTokenValid();
            if (!$filter) {
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter), "sort"=>"-ga:visits"));
            } else {
                $kws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("filters"=>"ga:medium==organic;".trim($myfilter).";".trim($filter), "sort"=>"-ga:visits"));
            }
            if ($kws->getRows() > 0) {
                $kwvisit = $kws->getRows();
                $prevyeartargetedkw += $kwvisit[0][0];
            }
            //Take Not Provided into consideration
            $total = $organic[0][0] - $nprovd;
            $temp = $prevyeartargetedkw / $total;
            if ($prevyeartargetedkw > $total) {
                $temp = $temp - 1;
            }
            $prevyeartargetedkw += $nprovd * $temp;
            round($prevyeartargetedkw);
            if ($prevyeartargetedkw > 0) {
                $this->pdf->Cell(17, '5', number_format($prevyeartargetedkw), '', '', 'C', true);
            } else {
                $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
            }
            $change = $this->calcvisitdiff($targetedkw, $prevtargetedkw);
            if ($change > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $change = "+".$change;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(15, '5', 'Page 1', '', '', '', true);
            // Get the first ranking keywords
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '1' AND RRS.page = '1'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prevseid = '1';
            if (mysql_num_rows($sqlresult) == 0) {
                $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '0' AND RRS.page <= '1'";
                $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                $prevseid = '0';
                if (mysql_num_rows($sqlresult) == 0) {
                    $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$currentreportid."' AND RRS.se_id = '2' AND RRS.page <= '1'";
                    $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
                    $prevseid = '2';
                }
            }
            $current1 = mysql_num_rows($sqlresult);
            $this->pdf->Cell(18, '5', $current1, '', '', 'C', true);
            $sql = "SELECT * FROM rr_stats AS RRS WHERE RRS.report_id = '".$prevreportid."' AND RRS.se_id = '".$prevseid."' AND RRS.page <= '1'";
            $sqlresult = mysql_query($sql) or die(mail("aris@ewebmarketing.com.au", "Summary Report Error", mysql_error()));
            $prev1 = mysql_num_rows($sqlresult);
            $theone = $current1 - $prev1;
            $totalmovement = $theone;
            $this->pdf->Cell(18, '5', $prev1, '', '', 'C', true);
            if ($theone > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $theone = "+".$theone;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(18, '5', $theone, '', '', 'C', true);
            $this->pdf->Ln(5);
            $this->pdf->SetFillColor(234, 244, 251);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(10, '5', '', '', '', '');
            if ($filter) {
                $this->pdf->Cell(15, '5', 'UnBranded', '', '', 'L', true);
                $this->checkTokenValid();
                $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                $unbrandkwvisit = $unbrandedkws->getRows();
                if ($unbrandedkws->getRows() > 0) {
                    $unbrandkw = $unbrandkwvisit[0][1];
                }
                $total = $organic[0][0] - $nprovd;
                $temp = $unbrandkw / $total;
                if ($unbrandkw > $total) {
                    $temp = $temp - 1;
                }
                $unbrandkw += $nprovd * $temp;
                round($unbrandkw);
                if ($unbrandkw > 0) {
                    $this->pdf->Cell(15, '5', number_format($unbrandkw), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(15, '5', '0', '', '', 'C', true);
                }
                $this->checkTokenValid();
                $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                $kwvisit = $unbrandedkws->getRows();
                if ($unbrandedkws->getRows() > 0) {
                    $prevunbrandkw = $kwvisit[0][1];
                }
                $total = $organic[0][0] - $nprovd;
                $temp = $prevunbrandkw / $total;
                if ($prevunbrandkw > $total) {
                    $temp = $temp - 1;
                }
                $prevunbrandkw += $nprovd * $temp;
                round($prevunbrandkw);
                if ($prevunbrandkw > 0) {
                    $this->pdf->Cell(20, '5', number_format($prevunbrandkw), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(20, '5', '0', '', '', 'C', true);
                }
                $this->checkTokenValid();
                $unbrandedkws = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-d", strtotime($this->startdate." - 1 year")), date("Y-m-d", strtotime($this->enddate." - 1 year")), 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword!=(not provided);".trim($filter), "sort"=>"-ga:visits"));
                $kwvisit = $unbrandedkws->getRows();
                if ($unbrandedkws->getRows() > 0) {
                    $prevyearunbrandkw += $kwvisit[0][1];
                }
                $total = $organic[0][0] - $nprovd;
                $temp = $prevyearunbrandkw / $total;
                if ($prevyearunbrandkw > $total) {
                    $temp = $temp - 1;
                }
                $prevyearunbrandkw += $nprovd * $temp;
                round($prevyearunbrandkw);
                if ($prevyearunbrandkw > 0) {
                    $this->pdf->Cell(17, '5', number_format($prevyearunbrandkw), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(17, '5', '0', '', '', 'C', true);
                }
                $change = $this->calcvisitdiff($unbrandkw, $prevunbrandkw);
                if ($change > 0) {
                    $this->pdf->SetTextColor(0, 147, 16);
                    $change = "+".$change;
                } else {
                    $this->pdf->SetTextColor(0, 51, 102);
                }
                $this->pdf->Cell(15, '5', $change."%", '', '', 'C', true);
            } else {
                $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                $this->pdf->Cell(15, '5', '', '', '', 'L', true);
                $this->pdf->Cell(20, '5', '', '', '', 'L', true);
                $this->pdf->Cell(17, '5', '', '', '', 'L', true);
                $this->pdf->Cell(15, '5', '', '', '', 'L', true);
            }
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(51, '5', 'Page 1 Positions Changed', '', '', '', true);
            // Get the first ranking keywords
            if ($totalmovement > 0) {
                $this->pdf->SetTextColor(0, 147, 16);
                $totalmovement = "+".$totalmovement;
            } else {
                $this->pdf->SetTextColor(0, 51, 102);
            }
            $this->pdf->Cell(18, '5', $totalmovement, '', '', 'C', true);
            // Now  we get the top organic keywords
            $this->checkTokenValid();
            if (!$filter) {
            
                $toporganic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!@(not provided)", "max-results"=>"5", "sort"=>"-ga:visits"));
            } else {
                $toporganic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:keyword", "filters"=>"ga:medium==organic;ga:keyword!@(not provided);".$filter, "max-results"=>"5", "sort"=>"-ga:visits"));
            }
            $toporganicresult = $toporganic->getRows();
            //print_r($toporganicresult);
            //exit();
            if ($toporganic->getRows() > 0) {
                for ($counter = 0; $counter < sizeof($toporganic->getRows()); $counter++) {
                    $this->checkTokenValid();
                    $previoustoporganic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==organic;ga:keyword==".$toporganicresult[$counter][0], "max-results"=>"1"));
                    $prevorganic = $previoustoporganic->getRows();
                    $organicdiff = $this->calcvisitdiff($toporganicresult[$counter][1], $prevorganic[0][0]);
                    $toporganickw[] = array($toporganicresult[$counter][0], $toporganicresult[$counter][1], $prevorganic[0][0], $organicdiff);
                }
            }
            //Now get the Visits By Medium
            $medium = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("filters"=>"ga:visits>0", "dimensions"=>"ga:medium", "sort"=>"-ga:visits", "max-results"=>"5"));
            $mediumresult = $medium->getRows();
            if ($medium->getRows() > 0) {
                for ($counter = 0; $counter < sizeof($medium->getRows()); $counter++) {
                    $prevmedium = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->prevstartdate, $this->prevenddate, 'ga:visits', array("filters"=>"ga:medium==".$mediumresult[$counter][0], "dimensions"=>"ga:medium", "sort"=>"-ga:visits", "max-results"=>"1"));
                    $prevmediumresult = $prevmedium->getRows();
                    $mediumfinal[] = array($mediumresult[$counter][0], $mediumresult[$counter][1], $prevmediumresult[0][1], $this->calcvisitdiff($mediumresult[$counter][1], $prevmediumresult[0][1]));
                }
            }
            $this->pdf->Ln(13);
            $this->pdf->Cell(10, '8', '', '', '', '');
            $this->pdf->SetFontSize(12);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Cell(60, '12', "Top Organic Keyword", '', '', 'L');
            $this->pdf->Cell(40, '12', '', '', '', '');
            $this->pdf->Cell(70, '12', 'Visits By Medium', '', '', 'L');
            $this->pdf->Cell(30, '12', '', '', '', '');
            $this->pdf->SetFontSize(9);
            $this->pdf->Ln(13);
            //We now have the data that we require, so we must now begin using it to build the next step of the report
            $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Cell(31, '5', '', '', '', '', true);
            $this->pdf->Cell(17, '5', 'Current', '', '', 'C', true);
            $this->pdf->Cell(17, '5', 'Previous', '', '', 'C', true);
            $this->pdf->Cell(17, '5', 'Change', '', '', 'C', true);
            $this->pdf->Cell(18, '5', '', '', '', '');
            $this->pdf->Cell(23, '5', 'Medium', '', '', '', true);
            $this->pdf->Cell(23, '5', 'Current', '', '', 'C', true);
            $this->pdf->Cell(23, '5', 'Percentage', '', '', 'C', true);
            $this->pdf->Ln(5);
            $this->pdf->SetTextColor(0, 0, 0);
            for ($i = 0; $i < 5; $i++) {
                $fillcolor = $i % 2;
                if ($fillcolor == 0) {
                    $this->pdf->SetFillColor(234, 244, 251);
                } else {
                    $this->pdf->SetFillColor(193, 222, 243);
                }
                $this->pdf->Cell(10, '5', '', '', '', '');
                if (strlen($toporganickw[$i][0]) > 20) {
                    $toporganickw[$i][0] = substr_replace($toporganickw[$i][0], '...', 20);
                }
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->Cell(31, '5', $toporganickw[$i][0], '', '', 'L', true);
                if ($toporganickw[$i][1] > 0) {
                    $this->pdf->Cell(17, '5', number_format($toporganickw[$i][1]), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(17, '5', $toporganickw[$i][1], '', '', 'C', true);
                }
                if ($toporganickw[$i][2] > 0) {
                    $this->pdf->Cell(17, '5', number_format($toporganickw[$i][2]), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(17, '5', $toporganickw[$i][2], '', '', 'C', true);
                }
                if ($toporganickw[$i][3] >= 0) {
                    if ($toporganickw[$i][0] != "") {
                        if ($toporganickw[$i] > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $toporganickw[$i][3] = "+".$toporganickw[$i][3];
                        }
                        $this->pdf->Cell(17, '5', $toporganickw[$i][3]."%", '', '', 'C', true);
                    } elseif ($toporganickw[$i][3] < 0) {
                        if ($toporganickw[$i][0] != "") {
                            $this->pdf->SetTextColor(0, 51, 102);
                            $this->pdf->Cell(17, '5', $toporganickw[$i][3]."%", '', '', 'C', true);
                        }
                    } else {
                        $this->pdf->Cell(17, '5', '', '', '', 'C', true);
                    }
                } else if ($toporganickw[$i][3] < 0) {
                    $this->pdf->SetTextColor(0, 51, 102);
                    $this->pdf->Cell(17, '5', $toporganickw[$i][3]."%", '', '', 'C', true);
                } else {
                    $this->pdf->Cell(17, '5', '', '', '', 'C', true);
                }
                $this->pdf->SetTextColor(0, 0, 0);
                if (strlen($mediumfinal[$i][0]) > 10) {
                    $mediumfinal[$i][0] = substr_replace($mediumfinal[$i][0], '...', 10);
                }
                $this->pdf->Cell(18, '5', '', '', '', '');
                $this->pdf->Cell(23, '5', $mediumfinal[$i][0], '', '', 'L', true);
                if ($mediumfinal[$i][1] > 0) {
                    $this->pdf->Cell(23, '5', number_format($mediumfinal[$i][1]), '', '', 'C', true);
                } else {
                    $this->pdf->Cell(23, '5', $mediumfinal[$i][1], '', '', 'C', true);
                }
                if ($mediumfinal[$i][1] > 0) {
                    $mediumperc = ($mediumfinal[$i][1] / $visit[0][0]) * 100;
                    $mediumperc = round($mediumperc, 2);
                    $this->pdf->Cell(23, '5', $mediumperc."%", '', '', 'C', true);
                } else {
                    $this->pdf->Cell(23, '5', '', '', '', 'C', true);
                }
                $this->pdf->Ln(5);
            }
            //Call the footer
            $this->pdffooter();
        }
    }
    public function rankingReport() {
        if ($this->rrstart && $this->rrto) {
            $getreportid = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' AND RRR.parsed_date = '".$this->rrstart."' ORDER BY parsed_date DESC LIMIT 1";
        } else {
            $getreportid = "SELECT * FROM rr_reports AS RRR WHERE RRR.company_id = '".$this->cid."' ORDER BY parsed_date DESC LIMIT 1";
        }
        $result = mysql_query($getreportid) or die(mysql_error());
        while ($row = mysql_fetch_assoc($result)) {
            $reportid = $row['report_id'];
            $daterun = $row['parsed_date'];
        }
        if ($reportid > 0) {
            // Find out if I have to show the analytics vists & or Conversions!
            $getvisitsql = "SELECT show_visitrr,show_convrr FROM clientstatus AS CL WHERE company_id = '".$this->cid."'";
            $getvistresult = mysql_query($getvisitsql);
            while ($visitrow = mysql_fetch_assoc($getvistresult)) {
                if ($visitrow['show_visitrr'] == 1) {
                    $length = 13;
                    $showvisit = true;
                }
                if ($visitrow['show_convrr'] == 1) {
                    $length += 13;
                    $showconv = true;
                }
                if ($length) {
                    $length = 100 - $length;
                } else {
                    $length = 100;
                }
            }
            $this->pdf->AddPage();
            $this->pdfHeader("Ranking Report", $daterun);
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("Search Engine Ranking Report"), 8, "Search Engine Ranking Report", '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->Cell(100, 8, 'Date Run: '.date("d F Y", strtotime($daterun)), '', '', 'L');
            $this->pdf->Image('/home/rtwpfx/public_html/images/reports/google.jpg', 165, 35);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->setBarColour();
            //$this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->Cell(10, '5', '', '', '', '');
            $this->pdf->Ln(8);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(10);
            $getreportstats = "SELECT keyword, url, position FROM rr_stats WHERE report_id = '".$reportid."' AND se_id = '1' GROUP BY keyword ORDER BY position ASC";
            $result = mysql_query($getreportstats) or die(mysql_error());
            if (mysql_num_rows($result) == 0) {
                $getreportstats = "SELECT keyword, url, position FROM rr_stats WHERE report_id = '".$reportid."' AND se_id = '0' GROUP BY keyword ORDER BY position ASC";
                $prevstat = 1;
                $quickcheckresult = mysql_query($getreportstats) or die(mysql_error());
                if (mysql_num_rows($quickcheckresult) == 0) {
                    $getreportstats = "SELECT keyword, url, position FROM rr_stats WHERE report_id = '".$reportid."' AND se_id = '2' GROUP BY keyword ORDER BY position ASC";
                    $prevstat = 2;
                }
            } else {
            	$prevstat = 0;
            }
            $result = mysql_query($getreportstats) or die(mysql_error());
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFontSize(8);
            $this->pdf->Cell(54, '8', 'Keyword', '', '', 'L', true);
            $this->pdf->Cell($length, '8', 'URL', '', '', 'L', true);
            $this->pdf->Cell(13, '8', 'Position', '', '', 'L', true);
            $this->pdf->Cell(13, '8', 'Previous', '', '', 'C', true);
            $this->pdf->Cell(13, '8', 'Change', '', '', 'R', true);
            if ($showvisit) {
                $this->pdf->Cell(13, '8', 'Visits', '', '', 'C', true);
            }
            if ($showconv) {
                $this->pdf->Cell(13, '8', 'Convs', '', '', 'C', true);
            }
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFontSize(9);
            //$this->pdf->Cell($this->pdf->GetStringWidth("Keyword	Position	Previous Position	Change"),10,"Keyword	Position	Previous Position	Change",'B','','L');
            $this->pdf->Ln(8);
            $i = 1;
            while ($row = mysql_fetch_assoc($result)) {
                if (substr($row['keyword'], 0, 1) != "*") {
                    $row['keyword'] = trim($row['keyword']);
                    if (! empty($row['keyword'])) {
                        // Here I have to match the keyword to last months keyword
                        if ($prevstat == 1) {
                            if ($this->rrto) {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='0' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id AND RRR.parsed_date = '".$this->rrto."') ORDER BY RRS.report_id DESC LIMIT 1";
                            } else {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='0' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id) ORDER BY RRS.report_id DESC LIMIT 1,1";
                            }
                        } else if ($prevstat == 2) {
                            if ($this->rrto) {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='2' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id AND RRR.parsed_date = '".$this->rrto."') ORDER BY RRS.report_id DESC LIMIT 1";
                            } else {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='2' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id) ORDER BY RRS.report_id DESC LIMIT 1,1";
                            }
                        } else {
                            //$prevpossql = "SELECT position from rr_stats WHERE keyword = '" . $row['keyword'] . "' AND se_id ='1' AND report_id <> '" . $reportid . "' ORDER BY report_id DESC LIMIT 1";
                            if ($this->rrto) {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='1' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id AND RRR.parsed_date = '".$this->rrto."') ORDER BY RRS.report_id DESC LIMIT 1";
                            } else {
                                $prevpossql = "SELECT * from rr_stats AS RRS, rr_reports as RRR WHERE keyword = '".mysql_real_escape_string($row['keyword'])."' AND (se_id ='1' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id) ORDER BY RRS.report_id DESC LIMIT 1,1";
                            }
                        }
                        if ($this->rrto && $this->rrto != "0000-00-00" && $showvisit) {
                            $this->checkTokenValid();
                            $traffic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->rrto, $this->rrstart, 'ga:visits', array("dimensions"=>"ga:month", "sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $row['keyword'])."?", "max-results"=>"1"));
                            //$traffic = $this->requestReportData($this->profileId,array("month"),array("visits"),array("-visits"),'medium==organic && keyword=~' . str_replace(" ","\s",$row['keyword']) . '?',$this->rrto,$this->rrstart,1,1);
                            $traff = $traffic->getRows();
                            if ($traffic->getRows() > 0) {
                                $visits = $traff[0][1];
                            }
                        } else if ($showvisit) {
                            $this->checkTokenValid();
                            $traffic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:month", "sort"=>"-ga:visits", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $row['keyword'])."?", "max-results"=>"1"));
                            $traff = $traffic->getRows();
                            if ($traffic->getRows() > 0) {
                                $visits = $traff[0][1];
                            }
                        }
                        // Do the same but for convs
                        if ($this->rrto && $this->rrto != "0000-00-00" && $showconv) {
                            $this->checkTokenValid();
                            $conversion = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->rrto, $this->rrstart, 'ga:goalCompletionsAll', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $row['keyword'])."?", "max-results"=>"1"));
                            $conv = $conversion->getRows();
                            if ($conversion->getRows() > 0) {
                                $conversions = $conv[0][1];
                            }
                        } else if ($showconv) {
                            $this->checkTokenValid();
                            $conversion = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:goalCompletionsAll', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $row['keyword'])."?", "max-results"=>"1"));
                            $conv = $conversion->getRows();
                            if ($conversion->getRows() > 0) {
                                $conversions = $conv[0][1];
                            }
                        }
                        $prevposresult = mysql_query($prevpossql) or die($prevpossql." ".mysql_error());
                        $prevpos = mysql_fetch_assoc($prevposresult);
                        $row['previous_position'] = $prevpos['position'];
                        if ($prevpos['position'] < 1) {
                            $row['previous_position'] = "0";
                        }
                        $fillcolor = $i % 2;
                        if ($fillcolor == 0) {
                            $this->setColourlight();
                        } else {
                            $this->setColourdark();
                        }
                        $pbreak = $i % 25;
                        if (strlen($row['keyword']) > 39) {
                            $this->pdf->Cell(54, '8', substr_replace($row['keyword'], '...', 39), '', '', 'L', true);
                        } else {
                            $this->pdf->Cell(54, '8', $row['keyword'], '', '', 'L', true);
                        }
                        if ($row['url'] != $this->homepage) {
                            $url = str_replace($this->homepage, "", $row['url']);
                            if (strlen($url) > 80) {
                                $url = substr_replace($url, '...', 80);
                            }
                        } else {
                            $url = "/";
                        }
                        $this->pdf->Cell($length, '8', $url, '', '', 'L', true, $row['url']);
                        $this->pdf->Cell(13, '8', $row['position'], '', '', 'C', true);
                        if ($row['previous_position'] == 0) {
                            $prev = "-";
                        } else {
                            $prev = $row['previous_position'];
                        }
                        $this->pdf->Cell(13, '8', $prev, '', '', 'C', true);
                        if ($row['previous_position'] - $row['position'] > 0) {
                            $this->pdf->SetTextColor(0, 147, 16);
                            $change = $row['previous_position'] - $row['position'];
                            $change = "+".$change;
                            //$this->pdf->Cell(0,'8',$change,'','','C',true);
                        } elseif ($row['previous_position'] - $row['position'] < 0) {
                            if ($row['previous_position'] == 0) {
                                $this->pdf->SetTextColor(0, 147, 16);
                                $change = "++";
                            } else {
                                $this->pdf->SetTextColor(0, 51, 102);
                                $change = $row['previous_position'] - $row['position'];
                            }
                        } else {
                            $this->pdf->SetTextColor(0, 0, 0);
                            $change = "-";
                        }
                        $this->pdf->Cell(13, '8', $change, '', '', 'C', true);
                        $this->pdf->SetTextColor(0, 0, 0);
                        if ($showvisit) {
                            $this->pdf->Cell(13, '8', $visits, '', '', 'C', true);
                        }
                        if ($showconv) {
                            $this->pdf->Cell(13, '8', $conversions, '', '', 'C', true);
                        }
                        //$this->pdf->Cell($this->pdf->GetStringWidth($i . ": " . $row['keyword']),8,$i . ": " .  $row['keyword'],'B','','L','',$row['url']);
                        //$this->pdf->Cell($this->pdf->GetStringWidth("	" . $row['position'] . "	" . $row['previous_position']),8,"	" . $row['position'] . "	" . $row['previous_position'],'B','','L');
                        //$this->pdf->Cell($this->pdf->GetStringWidth("	" . $row['previous_position'] - $row['position']),8,"	" . $row['previous_position'] - $row['position'],'B','','L');
                        $this->pdf->Ln(8);
                        
                        $this->pdffooter();
                        if ($pbreak == 0) {
                            $this->pdf->AddPage();
                            $this->pdfHeader("Ranking Report", $daterun);
                            $this->setBarColour();
                            // $this->pdf->SetFillColor(49, 145, 214);
                            $this->pdf->SetTextColor(255, 255, 255);
                            $this->pdf->Cell(54, '8', 'Keyword', '', '', 'L', true);
                            $this->pdf->Cell($length, '8', 'URL', '', '', 'L', true);
                            $this->pdf->Cell(13, '8', 'Position', '', '', 'L', true);
                            $this->pdf->Cell(13, '8', 'Previous', '', '', 'C', true);
                            $this->pdf->Cell(13, '8', 'Change', '', '', 'R', true);
                            if ($showvisit) {
                                $this->pdf->Cell(13, '8', 'Visits', '', '', 'C', true);
                            }
                            if ($showconv) {
                                $this->pdf->Cell(13, '8', 'Convs', '', '', 'C', true);
                            }
                            $this->pdf->SetTextColor(0, 0, 0);
                            $this->pdf->Ln(8);
                        }
                        $i++;
                        // WE need to store the keyword so we can find the non ranking ones
                        $kw[] = $row['keyword'];
                    }
                }
            }
            // Now we find the keywords that aren't in the stats i.e Not Ranking
            $sql = "SELECT CL.keywords FROM clientstatus AS CL WHERE CL.company_id = '".$this->cid."'";
            $nonkw = mysql_query($sql);
            while ($row = mysql_fetch_assoc($nonkw)) {
                $mykeywords = explode("\n", $row['keywords']);
                for ($b = 0; $b < sizeof($mykeywords); $b++) {
                    if (substr($mykeywords[$b], 0, 1) != "*") {
                        $mykeywords[$b] = str_replace("*", "", $mykeywords[$b]);
                        if (! empty($mykeywords[$b])) {
                            $mykeywords[$b] = trim($mykeywords[$b]);
                            if (strlen($mykeywords[$b]) > 0) {
                                if (!@in_array(trim($mykeywords[$b]), $kw)) {
                                    $fillcolor = $i % 2;
                                    $pbreak = $i % 25;
                                    if ($pbreak == 0) {
                                        $this->pdf->AddPage();
                                        $this->pdfHeader("Ranking Report", $daterun);
                                        $this->setBarColour();
                                        //$this->pdf->SetFillColor(49, 145, 214);
                                        $this->pdf->SetTextColor(255, 255, 255);
                                        $this->pdf->Cell(54, '8', 'Keyword', '', '', 'L', true);
                                        $this->pdf->Cell($length, '8', 'URL', '', '', 'L', true);
                                        $this->pdf->Cell(13, '8', 'Position', '', '', 'L', true);
                                        $this->pdf->Cell(13, '8', 'Previous', '', '', 'C', true);
                                        $this->pdf->Cell(13, '8', 'Change', '', '', 'R', true);
                                        if ($showvisit) {
                                            $this->pdf->Cell(13, '8', 'Visits', '', '', 'C', true);
                                        }
                                        if ($showconv) {
                                            $this->pdf->Cell(13, '8', 'Convs', '', '', 'C', true);
                                        }
                                        $this->pdf->SetTextColor(0, 0, 0);
                                        $this->pdf->Ln(8);
                                    }
                                    if ($fillcolor == 0) {
                                        $this->setColourlight();
                                    } else {
                                        $this->setColourdark();
                                    }
                                    if (strlen($mykeywords[$b]) > 39) {
                                        $this->pdf->Cell(54, '8', substr_replace($mykeywords[$b], '...', 39), '', '', 'L', true);
                                    } else {
                                        $this->pdf->Cell(54, '8', $mykeywords[$b], '', '', 'L', true);
                                    }
                                    $this->pdf->Cell($length, '8', '-', '', '', 'L', true);
                                    $this->pdf->Cell(13, '8', '-', '', '', 'C', true);
                                    //
                                    if ($prevstat != 2 && $prevstat != 1) {
                                        $prevstat = 1;
                                    }
                                    if ($this->rrto) {
                                        $prevpossql = "SELECT position from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($mykeywords[$b])."' AND (se_id ='".$prevstat."' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id AND RRR.parsed_date = '".$this->rrto."') ORDER BY RRS.report_id DESC LIMIT 1";
                                    } else {
                                        $prevpossql = "SELECT position from rr_stats AS RRS, rr_reports as RRR WHERE RRS.keyword = '".mysql_real_escape_string($mykeywords[$b])."' AND (se_id ='".$prevstat."' AND RRR.company_id = '".$this->cid."' AND RRS.report_id=RRR.report_id) ORDER BY RRS.report_id DESC LIMIT 1,1";
                                    }
                                    if ($this->rrto && $this->rrto != "0000-00-00" && $showvisit) {
                                        $this->checkTokenValid();
                                        $traffic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->rrto, $this->rrstart, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $mykeywords[$b])."?", "max-results"=>"1"));
                                        $traff = $traffic->getRows();
                                        if ($traffic->getRows() > 0) {
                                            $visits = $traff[0][1];
                                        }
                                    } else if ($showvisit) {
                                        $this->checkTokenValid();
                                        $traffic = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:visits', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $mykeywords[$b])."?", "max-results"=>"1"));
                                        $traff = $traffic->getRows();
                                        if ($traffic->getRows() > 0) {
                                            $visits = $traff[0][1];
                                        }
                                    }
                                    if ($this->rrto && $this->rrto != "0000-00-00" && $showconv) {
                                        $conversion = "";
                                        $this->checkTokenValid();
                                        $conversion = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->rrto, $this->rrstart, 'ga:goalCompletionsAll', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $mykeywords[$b])."?", "max-results"=>"1"));
                                        $conv = $conversion->getRows();
                                        if ($conversion->getRows() > 0) {
                                            $conversions = $conv[0][1];
                                        }
                                    } else if ($showconv) {
                                        $conversion = "";
                                        $this->checkTokenValid();
                                        $conversion = $this->analytics->data_ga->get('ga:'.$this->profileId, $this->startdate, $this->enddate, 'ga:goalCompletionsAll', array("dimensions"=>"ga:month", "filters"=>"ga:medium==organic;ga:keyword=~".str_replace(" ", "\s", $mykeywords[$b])."?", "max-results"=>"1"));
                                        $conv = $conversion->getRows();
                                        if ($conversion->getRows() > 0) {
                                            $conversions = $conv[0][1];
                                        }
                                    }
                                    $prevrankingresult = mysql_query($prevpossql);
                                    $prevrankkw = mysql_fetch_assoc($prevrankingresult);
                                    if ($prevrankkw['position'] > 0) {
                                        $this->pdf->Cell(13, '8', $prevrankkw['position'], '', '', 'C', true);
                                        $this->pdf->Cell(13, '8', '--', '', '', 'C', true);
                                    } else {
                                        $this->pdf->Cell(13, '8', '-', '', '', 'C', true);
                                        $this->pdf->Cell(13, '8', '-', '', '', 'C', true);
                                    }
                                    $this->pdf->SetTextColor(0, 0, 0);
                                    if ($showvisit) {
                                        $this->pdf->Cell(13, '8', $visits, '', '', 'C', true);
                                    }
                                    if ($showconv) {
                                        $this->pdf->Cell(13, '8', $conversions, '', '', 'C', true);
                                    }
                                    $this->pdf->Ln();
                                    $this->pdffooter();
                                    $i++;
                                }
                            }
                        }
                    }
                }
            }
            $this->pdffooter();
        }
    }
    public function createReport($reports) {
        $this->pdf->title = str_replace("amp;", "", $this->companyname);
        $this->pdf->SetAuthor("E-Web Marketing");
        $this->pdf->SetSubject("Report for ".$this->companyname." ".date("m")."-".date("Y"));
        $this->pdf->SetFont('Arial', '', 15);
        $this->pdf->SetFillColor(255, 255, 255);
        for ($i = 0; $i < sizeof($reports); $i++) {
            $reportFunction = $reports[$i]."Report";
            call_user_func(array($this, $reportFunction));
        }
		if (isset($_SESSION['employeeid'])) {
			$findemail = "SELECT firstname,surname,email from employee WHERE employee_id = '".$_SESSION['employeeid']."'";
			$findemailresult = mysql_query($findemail) or die(mysql_error());
			$findemailrow = mysql_fetch_assoc($findemailresult);
			if ($this->pdf->PageNo() == 0) {
				sendmail_mail_attachment($findemailrow['firstname']." ".$findemailrow['surname'], $findemailrow['email'], "Report Error", "Hi ".$findemailrow['firstname']."
				Something went wrong when generating your report, please make sure that everything is correct in tracker & AWR before trying again!
				Love Tracker", array());
				exit();
			}
        }
		$emailname = str_replace("/", "-", $this->companyname."-".$this->enddate."-report.pdf");
      	$emailname = str_replace(" ", "-", $emailname);
       	$this->pdf->Output('/home/rtwpfx/public_html/inc/tmppdf/'.trim($emailname), 'F');
        
        //Upload the report to S3
        if (!class_exists('S3'))
            require_once 'inc/S3.php';
            
        // AWS access info
        if (!defined('awsAccessKey'))
            define('awsAccessKey', 'AKIAJC3GJV4ZFML2MRXQ');
        if (!defined('awsSecretKey'))
            define('awsSecretKey', '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');
        // Instantiate the class
        $s3 = new S3(awsAccessKey, awsSecretKey);
        $bucket = "cdn.ewebtracker.info";
        $fileName = "ranking-reports/".$this->companyname."-".date("F-Y", strtotime($this->startdate)).".pdf";
        $fileTempName = "/home/rtwpfx/public_html/inc/tmppdf/".trim($emailname);
        $s3->deleteObject($bucket, $fileName);
        if ($s3->putObjectFile($fileTempName, $bucket, $fileName, S3::ACL_PUBLIC_READ)) {
            // Find the person who submitted the request
            sendmail_mail_attachment("Aris Abramian", "aris@ewebmarketing.com.au", $this->companyname." PDF Report Generated", "The report for ".$this->companyname." is attached\n\nThe Report has been succesfully uploaded to S3. \n\n You can find it here http://cdn.ewebtracker.info/".htmlentities($fileName)."\n\n For ".$this->cmname, array("http://www.ewebtracker.info/inc/tmppdf/".trim($emailname)));
			if (isset($_SESSION['employeeid']) && $_SESSION['employeeid'] != "81") {
				sendmail_mail_attachment($findemailrow['firstname']." ".$findemailrow['surname'], $findemailrow['email'], $this->companyname." PDF Report Generated", "The report for ".$this->companyname." is attached\n\nThe Report has been succesfully uploaded to S3. \n\n You can find it here http://cdn.ewebtracker.info/".htmlentities($fileName), array("http://www.ewebtracker.info/inc/tmppdf/".trim($emailname)));
				$sql = "UPDATE clientstatus SET date_last_report_run = '".date("Y-m-d")."' WHERE company_id = '".$this->cid."' LIMIT 1";
				$result = mysql_query($sql);
			}
            
        } else {
            if (isset($_SESSION['employeeid']) && $_SESSION['employeeid'] != "81") {
                sendmail_mail_attachment($findemailrow['firstname']." ".$findemailrow['surname'], $findemailrow['email'], $this->companyname." PDF Report Generated", "The report for ".$this->companyname." is attached\n We were not able to upload the report to Amazon S3", array("http://www.ewebtracker.info/inc/tmppdf/".trim($emailname)));
                $sql = "UPDATE clientstatus SET date_last_report_run = '".date("Y-m-d")."' WHERE company_id = '".$this->cid."' LIMIT 1";
                $result = mysql_query($sql);
            }
        }
        $this->pdf->Output(trim(str_replace("/", "-", $this->companyname."-report.pdf")), 'I');
    }
    public function ppcReport() {
        if ($this->ppcId) {
            $this->now = date('Y-m-d');
            $months = array();
            $years = array();
            $count = 1;
            $ppcsql = "SELECT PSM.stats_id,PCL.google_ppc_id,C.companyname, PSM.mup,CL.ppc_datestart, PSM.google_budget + PSM.overture_budget + PSM.facebook_budget AS budget FROM clientstatus AS CL, ppc_clientstatus AS PCL, company AS C, ppc_stats_monthly AS PSM WHERE CL.company_id = '".$this->cid."' AND CL.company_id = PCL.company_id AND CL.company_id = PSM.company_id AND PCL.google_ppc_id <> '' AND YEAR(PSM.do_stats) = '".date("Y")."' AND MONTH(PSM.do_stats) = '".date("m")."' AND CL.company_id = C.companyid ";
            $ppc_client = mysql_query($ppcsql) or die(mysql_error());
            while ($ppc_clients = mysql_fetch_assoc($ppc_client)) {
                $startdate = explode("-", $ppc_clients["ppc_datestart"]);
                while ($count < 13) {
                    if ($startdate[0] == date('Y', strtotime($this->now." - $count months"))) {
                        if ($startdate[1] <= date('m', strtotime($this->now." - $count months"))) {
                            $months[] = date('m', strtotime($this->now." - $count months"));
                            $years[] = date('Y', strtotime($this->now." - $count months"));
                            $days[] = date('t', strtotime($this->now." - $count months"));
                        }
                    } elseif ($startdate[0] < date('Y', strtotime($this->now." - $count months"))) {
                        $months[] = date('m', strtotime($this->now." - $count months"));
                        $years[] = date('Y', strtotime($this->now." - $count months"));
                        $days[] = date('t', strtotime($this->now." - $count months"));
                    }
                    $count++;
                }
                $mup = $ppc_clients['mup'];
            }
            $n = 0;
            $k = 0;
            $a = 0;
            $displayNetworkFlag = 0;
            $searchNetworkFlag = 0;
            //echo "<br>PPC Client: ".$ppc_client['companyname'];
            //echo "<br>PPC Client ID: ".$ppc_client['google_ppc_id'];
            $this->ppcobj->LogDefaults();
            $url = "https://adwords.google.com/api/adwords/reportdownload/v201306";
            for ($i = 0; $i < sizeof($months); $i++) {
                $reportDefinition = "<reportDefinition>";
                $reportDefinition .= "<selector>";
                $reportDefinition .= "<fields>Id</fields>";
                $reportDefinition .= "<fields>Name</fields>";
                $reportDefinition .= "<fields>Impressions</fields>";
                $reportDefinition .= "<fields>Clicks</fields>";
                $reportDefinition .= "<fields>Ctr</fields>";
                $reportDefinition .= "<fields>AveragePosition</fields>";
                $reportDefinition .= "<fields>Conversions</fields>";
                $reportDefinition .= "<fields>ConversionRate</fields>";
                $reportDefinition .= "<fields>CostPerConversion</fields>";
                $reportDefinition .= "<fields>Status</fields>";
                $reportDefinition .= "<fields>SearchImpressionShare</fields>";
                $reportDefinition .= "<fields>AdNetworkType2</fields>";
                $reportDefinition .= "<fields>AverageCpc</fields>";
                $reportDefinition .= "<fields>Cost</fields>";
                $reportDefinition .= "<dateRange>";
				$mindate = $years[$i].$months[$i]."01";
                $maxdate = $years[$i].$months[$i].$days[$i];
                $reportDefinition .= "<min>".$mindate."</min>";
                $reportDefinition .= "<max>".$maxdate."</max>";
                $reportDefinition .= "</dateRange>";
                $reportDefinition .= "</selector>";
                $reportDefinition .= "<reportName>Campaign performance report #".time()."</reportName>";
                $reportDefinition .= "<reportType>CAMPAIGN_PERFORMANCE_REPORT</reportType>";
                $reportDefinition .= "<dateRangeType>CUSTOM_DATE</dateRangeType>";
                $reportDefinition .= "<downloadFormat>CSV</downloadFormat>";
                $reportDefinition .= "</reportDefinition>";
                $params = array("__rdxml"=>$reportDefinition);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $this->adWordheaders);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($curl);
                curl_close($curl);
                $array = new ArrayObject();
				$array = $this->str_getcsv($result);
                /*
                 // Create report definition.
                 $reportDefinition->selector = $selector;
                 // Create operations.
                 $operation = new ReportDefinitionOperation();
                 $operation->operand = $reportDefinition;
                 $operation->operator = 'ADD';
                 $operations = array($operation);
                 // Add report definition.
                 $result = $reportDefinitionService->mutate($operations);
                 // Display report definitions.
                 */
                if ($i == 0) {// Only do this for the first month of the campaign
                    for ($t = 0, $flag = 1, $u = 0; $t < sizeof($array) - 1; $t++, $u++) {
                        if ($t > 0) {// This happens after the first run because it's comparing to previous runs
                            if (@$campaignData[$u - 1]['name'] == $array[$t][1]) {
                                // If the name is the same as before
                                $flag++;
                                // Set the flag
                                // Add the data below
                                $campaignData[$u - 1]['imps'] += $array[$t][2];
                                $campaignData[$u - 1]['clicks'] += $array[$t][3];
                                $campaignData[$u - 1]['avgpos'] += $array[$t][5];
                                $campaignData[$u - 1]['convs'] += $array[$t][6];
                                $totcost = $array[$t][13];
                                $campaignData[$u - 1]['totcost'] += round($this->markup($totcost, $mup), 2);
                                $u--;
                                //Campaign Data get incremented at the end, so we decrement it here
                            } else {
                                // If the Campaign name is different
                                if ($flag > 1) {
                                    // If we are in a different campaign, but there are more than 1 previous campaign
                                    //Calculate stuff
                                    if ($campaignData[$u - 1]['convs'] > 0) {
                                        $campaignData[$u - 1]['cpconv'
                                        ] = round($campaignData[$u - 1]['totcost'] / $campaignData[$u - 1]['convs'], 2);
                                    } else {
                                        $campaignData[$u - 1]['cpconv'] = 0;
                                    }
                                    if ($campaignData[$u - 1]['clicks'] > 0) {
                                        $campaignData[$u - 1]['convrate'] = round(100 * $campaignData[$u - 1]['convs'] / $campaignData[$u - 1]['clicks'], 2)."%";
                                        $campaignData[$u - 1]['avgbid'] = round($campaignData[$u - 1]['totcost'] / $campaignData[$u - 1]['clicks'], 2);
                                    } else {
                                        $campaignData[$u - 1]['convrate'] = 0;
                                        $campaignData[$u - 1]['avgbid'] = 0;
                                    }
                                    if ($campaignData[$u - 1]['imps'] > 0) {
                                        $campaignData[$u - 1]['ctr'] = round(100 * $campaignData[$u - 1]['clicks'] / $campaignData[$u - 1]['imps'], 2)."%";
                                    } else {
                                        $campaignData[$u - 1]['ctr'] = 0;
                                    }
                                    $campaignData[$u - 1]['avgpos'] = round($campaignData[$u - 1]['avgpos'] / $flag, 2);
                                    $flag = 1;
                                }
                                //Actually set the data for this run
                                $campaignData[$u]['name'] = $array[$t][1];
                                $campaignData[$u]['imps'] = $array[$t][2];
                                $campaignData[$u]['clicks'] = $array[$t][3];
                                $campaignData[$u]['ctr'] = $array[$t][4];
                                $campaignData[$u]['avgpos'] = $array[$t][5];
                                $campaignData[$u]['convs'] = $array[$t][6];
                                $campaignData[$u]['convrate'] = $array[$t][7];
                                $cpconv = $array[$t][8];
                                $campaignData[$u]['cpconv'] = round($this->markup($cpconv, $mup), 2);
                                $avgbid = $array[$t][12];
                                $totcost = $array[$t][13];
                                $campaignData[$u]['totcost'] = round($this->markup($totcost, $mup), 2);
                                $campaignData[$u]['avgbid'] = round($this->markup($avgbid, $mup), 2);
                            }
                        } else {
                            if ($flag > 1) {
                                if ($campaignData[$u - 1]['convs'] > 0) {
                                    $campaignData[$u - 1]['cpconv'] = "$".round($campaignData[$u - 1]['totcost'] / $campaignData[$u - 1]['convs'], 2);
                                } else {
                                    $campaignData[$u - 1]['cpconv'] = 0;
                                }
                                if ($campaignData[$u - 1]['clicks'] > 0) {
                                    $campaignData[$u - 1]['convrate'] = round(100 * $campaignData[$u - 1]['convs'] / $campaignData[$u - 1]['clicks'], 2)."%";
                                    $campaignData[$u - 1]['avgbid'] = round($campaignData[$u - 1]['totcost'] / $campaignData[$u - 1]['clicks'], 2);
                                } else {
                                    $campaignData[$u - 1]['convrate'] = 0;
                                    $campaignData[$u - 1]['avgbid'] = 0;
                                }
                                if ($campaignData[$u - 1]['imps'] > 0) {
                                    $campaignData[$u - 1]['ctr'] = round(100 * $campaignData[$u - 1]['clicks'] / $campaignData[$u - 1]['imps'], 2)."%";
                                } else {
                                    $campaignData[$u - 1]['ctr'] = 0;
                                }
                                $campaignData[$u - 1]['avgpos'] = round($campaignData[$u - 1]['avgpos'] / $flag, 2);
                                $flag = 1;
                            } else {
								$t = 1;
                                $campaignData[$t]['name'] = $array[$t][1];
                                $campaignData[$t]['imps'] = $array[$t][2];
                                $campaignData[$t]['clicks'] = $array[$t][3];
                                $campaignData[$t]['ctr'] = $array[$t][4];
                                $campaignData[$t]['avgpos'] = $array[$t][5];
                                $campaignData[$t]['convs'] = $array[$t][6];
                                $campaignData[$t]['convrate'] = $array[$t][7];
                                $cpconv = $array[$t][8];
								$campaignData[$t]['cpconv'] = round($this->markup($cpconv, $mup), 2);
                                $avgbid = $array[$t][12];
                                $totcost = $array[$t][13];
                                $campaignData[$t]['totcost'] = round($this->markup($totcost, $mup), 2);
                                $campaignData[$t]['avgbid'] = round($this->markup($avgbid, $mup), 2);
                            }
                        }
                        if ($array[$t][11] == "Display Network") {
                            $campaignData[$t]['type'] = "Display";
                            $displayNetworkFlag += 1;
                            $displayNetwork['clicks'] += $array[$t][3];
                            $displayNetwork['imps'] += $array[$t][2];
                            $totcost = str_replace(" ", "", str_replace("\"", "", $array[$t][13]));
                            $displayNetwork['totcost'] += round($this->markup($totcost, $mup), 2);
                            $displayNetwork['convs'] += $array[$t][6];
                            $displayNetwork['avgpos'] += $array[$t][5];
                        } else {
                            $campaignData[$i]['type'] = "Search";
                            $searchNetworkFlag += 1;
                            @$searchNetwork['clicks'] += $array[$t][3];
                            @$searchNetwork['imps'] += $array[$t][2];
                            $totcost = str_replace(" ", "", str_replace("\"", "", $array[$t][13]));
                            @$searchNetwork['totcost'] += round($this->markup($totcost, $mup), 2);
                            @$searchNetwork['convs'] += $array[$t][6];
                            @$searchNetwork['avgpos'] += $array[$t][5];
                        }
                    }
                }
                // Prepare data for Graphing
                if ($array[sizeof($array) - 1][2] > 0) {
                    $imparray[$n] = $array[sizeof($array) - 1][2];
                    $clickarray[$n] = $array[sizeof($array) - 1][3];
                    $ctrarray[$n] = $array[sizeof($array) - 1][4];
                    $avgposarray[$n] = $array[sizeof($array) - 1][5];
                    $conversionsarray[$n] = $array[sizeof($array) - 1][6];
                    $convratearray[$n] = $array[sizeof($array) - 1][7];
                    $cpconv = $array[sizeof($array) - 1][8];
                    $impressionshare[$n] = $array[sizeof($array) - 1][10];
                    $avgbid = $array[sizeof($array)- 1][12];
                    $totcost = str_replace(",","",$array[sizeof($array) - 1][13]);
                    $cpconvarray[$n] = round($this->markup($cpconv, $mup),2);
                    $totcostarray[$n] = round($this->markup($totcost, $mup),2);
                    $avgbidarray[$n] = round(($totcostarray[$n] / $clickarray[$n]),2);
                    $monthsyear[$n] = date("M-y", strtotime($years[$n]."-".$months[$n]));
                    $n++;
                }
            }
            include ("/home/rtwpfx/public_html/inc/TotalCostvConversionsGraphClass.php");
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("PPC Report"), 8, "PPC Report", '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->Image($costurl, '10', '50', '190', '', 'PNG');
            $this->pdf->Ln(35);
            $this->setBarColour();
            // $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetDrawColor(0, 0, 0);
            $this->pdf->SetFont('', 'B');
            $this->pdf->SetFontSize(8);
            if ($impressionshare[0] < 90) {// If they have an impressionshare less than 90% tell them to inrease their budget
                if ($impressionshare[0] > 0) {
                    $opportunity = round(100 * ($totcostarray[0] * $mup / $impressionshare[0]) / $mup, 2);
                } else if ($mup > 0) {
                    $opportunity = round(100 * ($totcostarray[0] * $mup / 9) / $mup, 2);
                } else {
                    $opportunity = "Mark-up not set";
                }
                $this->pdf->SetDrawColor(255, 0, 0);
                $this->pdf->SetTextColor(255, 0, 0);
                if ($impressionshare[0] != "< 10%") {
                    $this->pdf->Cell('190', 5, 'Your current impression share is only '.$impressionshare[0].'! Increase your PPC budget to $'.number_format($opportunity, 2).' to DOMINATE!', '1', 0, 'L');
                } else {
                    $this->pdf->Cell('190', 5, 'Your current impression share is '.$impressionshare[0].'! Increase your PPC budget to $'.number_format($opportunity, 2).' to DOMINATE!', '1', 0, 'L');
                }
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Ln(8);
            }
            $header = array('Month-Year', 'Impressions', 'Clicks', 'CTR', 'Avg. Bid', 'Tot Cost', 'Avg Pos', 'Conv\'s', 'Conv Rate', 'CPConv');
            $w = array(20, 20, 15, 15, 20, 30, 15, 15, 20, 20);
            for ($i = 0; $i < count($header); $i++) {
                $this->pdf->Cell($w[$i], 4, $header[$i], 0, 0, 'C', true);
            }
            $this->pdf->Ln();
            $fill = true;
            $this->pdf->SetTextColor(0);
            $this->pdf->SetFont('');
            for ($i = 0; $i <= sizeof($monthsyear) - 1; $i++) {
                $j = 0;
                $fillcolor = $i % 2;
                if ($fillcolor == 0) {
                    $this->setColourlight();
                } else {
                    $this->setColourdark();
                }
                if ($i <= 2) {
                    $this->pdf->SetFont('', 'B');
                } else {
                    $this->pdf->SetFont('');
                }
                $this->pdf->Cell($w[$j++], 4, $monthsyear[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($imparray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($clickarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $ctrarray[$i]."%", 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".round($totcostarray[$i] / $clickarray[$i], 2), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".number_format($totcostarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $avgposarray[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($conversionsarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $convratearray[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".$cpconvarray[$i], 0, 0, 'C', $fill);
                $this->pdf->Ln();
            }
            $this->pdffooter();
            // Get the PPC Feedback
            $feedback = "SELECT T.task_description FROM tasks as T, task_monthly_feedback as TMF WHERE T.company_id = '".$this->cid."' AND (T.task_id = TMF.task_id AND TMF.type = 'PPC') ORDER BY TMF.task_id DESC LIMIT 1";
            $feedbackresult = mysql_query($feedback) or die(mysql_error());
            if (mysql_num_rows($feedbackresult) == 0) {
                $feedback = "SELECT T.task_description FROM tasks as T, task_monthly_maintenance as TMM WHERE T.company_id = '".$this->cid."' AND (T.task_id = TMM.task_id AND T.task_description LIKE '%Insight%' AND T.about = 'ppc') ORDER BY TMM.task_id DESC LIMIT 1";
                $feedbackresult = mysql_query($feedback) or die(mysql_error());
            }
            while ($row = mysql_fetch_assoc($feedbackresult)) {
                $feedback_info = $row['task_description'];
            }
            if (sizeof($feedback_info) > 0) {
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetFont('', 'B');
                $this->pdf->Ln();
                $this->setBarColour();
                $this->pdf->Cell(array_sum($w), 5, 'PPC Feedback', 0, 0, 'L', true);
                $this->pdf->Ln();
                $this->setColourlight();
                $this->pdf->SetTextColor(0);
                $this->pdf->SetFont('');
                $newarray = explode("\n", $feedback_info);
                for ($temp = 0; $temp < sizeof($newarray) - 1; $temp++) {
                    $newpage = $this->pdf->countLines(array_sum($w), 4, stripslashes($newarray[$temp])."\n", '', 'L', true);
                    @$newpage = $counter + $newpage;
                    @$counter += $this->pdf->countLines(array_sum($w), 4, stripslashes($newarray[$temp])."\n", '', 'L', true);
                    if ($counter > 28 && $once == 0) {// Add a new page
                        $this->pdf->AddPage();
                        $this->pdffooter();
                        $this->pdfHeader();
                        $this->pdf->SetTextColor(255, 255, 255);
                        $this->pdf->SetFont('', 'B');
                        $this->pdf->Ln();
                        $this->setBarColour();
                        $this->pdf->Cell(array_sum($w), 5, 'PPC Feedback', 0, 0, 'L', true);
                        $this->pdf->Ln();
                        $this->setColourlight();
                        $this->pdf->SetTextColor(0);
                        $this->pdf->SetFont('');
                        $once = 1;
                    }
                    $this->pdf->MultiCell(array_sum($w), 4, stripslashes($newarray[$temp]), '', 'L', true);
                }
            }
        }
    }
    
    public function arisppcReport() {
        if ($this->ppcId) {
            $mup = $ppc_client['mup'];
            $clientname = $ppc_client['companyname'];
            // Create the click and impressions array
            for ($x = 0; $x <= 11; $x++) {
                $clicksql = "SELECT impressionShare,network,date,clicks, impressions, avg_position,cost,conv_1perClick FROM adwords_traffic_stats as ATS WHERE ATS.company_id = '".$this->cid."' AND date = '".date("Y-m-01", strtotime($this->startdate." -".$x." months"))."'";
                $clickresult = mysql_query($clicksql);
                if (mysql_num_rows($clickresult) == 0) {
                    break;
                }
                while ($clx = mysql_fetch_assoc($clickresult)) {
                    $clix += $clx['clicks'];
                    $impx += $clx['impressions'];
                    $impxt[] = ($clx['impressions'] * $clx['impressionShare']);
                    //if ($clx['network'] == "Google Search") {
                    $avgpx += $clx['avg_position'];
                    //} else {
                    //  $avgpx += 1;
                    //}
                    $costx += $clx['cost'];
                    $convx += $clx['conv_1perClick'];
                }
                $imptx = array_sum($impxt);
                $impressionshare[] = $imptx / $impx;
                $ctrx += round(($clix / $impx) * 100, 2);
                $cnvrx += round(($convx / $clix) * 100, 2);
                $clickarray[] = $clix;
                $imparray[] = $impx;
                $ctrarray[] = $ctrx;
                $convratearray[] = $cnvrx."%";
                $avgposarray[] = round($avgpx / mysql_num_rows($clickresult), 2);
                $avgbidarray[] = round(($costx / $clix), 2);
                $monthsyear[] = date("M-y", strtotime($this->startdate." -".$x." months"));
                $totcostarray[] = $costx;
                $conversionsarray[] = $convx;
                $cpconvarray[] = round($costx / $convx, 2);
                unset($clix);
                unset($impx);
                unset($impxt);
                unset($avgpx);
                unset($ctrx);
                unset($cnvrx);
                unset($costx);
                unset($convx);
                unset($imptx);
            }
            include ("/home/rtwpfx/public_html/inc/TotalCostvConversionsGraphClass.php");
            $this->pdf->AddPage();
            $this->pdfHeader();
            $this->pdf->SetFontSize(15);
            $this->pdf->Cell($this->pdf->GetStringWidth(str_replace("http://www.", "", $this->homepage)), 8, str_replace("http://www.", "", $this->homepage), '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->SetFontSize(10);
            $this->pdf->Cell($this->pdf->GetStringWidth("PPC Report"), 8, "PPC Report", '', '', 'L');
            $this->pdf->Ln(5);
            $this->pdf->Image($costurl, '10', '50', '190', '', 'PNG');
            $this->pdf->Ln(35);
            $this->setBarColour();
            // $this->pdf->SetFillColor(49, 145, 214);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetDrawColor(0, 0, 0);
            $this->pdf->SetFont('', 'B');
            $this->pdf->SetFontSize(8);
            if ($impressionshare[0] < 90) {// If they have an impressionshare less than 90% tell them to inrease their budget
                if ($impressionshare[0] > 0) {
                    $opportunity = round(100 * ($totcostarray[0] / $impressionshare[0]), 2);
                }
                
                $this->pdf->SetDrawColor(255, 0, 0);
                $this->pdf->SetTextColor(255, 0, 0);
                if ($impressionshare[0] != "< 10%") {
                    $this->pdf->Cell('190', 5, 'Your current impression share is only '.round($impressionshare[0], 2).'%! Increase your PPC budget to $'.number_format($opportunity, 2).' to DOMINATE!', '1', 0, 'L');
                } else {
                    $this->pdf->Cell('190', 5, 'Your current impression share is '.round($impressionshare[0], 2).'%! Increase your PPC budget to $'.number_format($opportunity, 2).' to DOMINATE!', '1', 0, 'L');
                }
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetDrawColor(0, 0, 0);
                $this->pdf->Ln(8);
                
            }
            $header = array('Month-Year', 'Impressions', 'Clicks', 'CTR', 'Avg. Bid', 'Tot Cost', 'Avg Pos', 'Conv\'s', 'Conv Rate', 'CPConv');
            $w = array(20, 20, 15, 15, 20, 30, 15, 15, 20, 20);
            for ($i = 0; $i < count($header); $i++) {
                $this->pdf->Cell($w[$i], 4, $header[$i], 0, 0, 'C', true);
            }
            $this->pdf->Ln();
            $fill = true;
            $this->pdf->SetTextColor(0);
            $this->pdf->SetFont('');
            for ($i = 0; $i <= sizeof($monthsyear) - 1; $i++) {
                $j = 0;
                $fillcolor = $i % 2;
                if ($fillcolor == 0) {
                    $this->setColourlight();
                } else {
                    $this->setColourdark();
                }
                if ($i <= 2) {
                    $this->pdf->SetFont('', 'B');
                } else {
                    $this->pdf->SetFont('');
                }
                $this->pdf->Cell($w[$j++], 4, $monthsyear[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($imparray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($clickarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $ctrarray[$i]."%", 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".round($totcostarray[$i] / $clickarray[$i], 2), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".number_format($totcostarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $avgposarray[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, number_format($conversionsarray[$i]), 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, $convratearray[$i], 0, 0, 'C', $fill);
                $this->pdf->Cell($w[$j++], 4, "$".$cpconvarray[$i], 0, 0, 'C', $fill);
                $this->pdf->Ln();
            }
            $this->pdffooter();
            
            // Get the PPC Feedback
            $feedback = "SELECT T.task_description FROM tasks as T, task_monthly_feedback as TMF WHERE T.company_id = '".$this->cid."' AND (T.task_id = TMF.task_id AND TMF.type = 'PPC') ORDER BY TMF.task_id DESC LIMIT 1";
            $feedbackresult = mysql_query($feedback) or die(mysql_error());
            if (mysql_num_rows($feedbackresult) == 0) {
                $feedback = "SELECT T.task_description FROM tasks as T, task_monthly_maintenance as TMM WHERE T.company_id = '".$this->cid."' AND (T.task_id = TMM.task_id AND T.task_description LIKE '%Insight%' AND T.about = 'ppc') ORDER BY TMM.task_id DESC LIMIT 1";
                $feedbackresult = mysql_query($feedback) or die(mysql_error());
            }
            while ($row = mysql_fetch_assoc($feedbackresult)) {
                $feedback_info = $row['task_description'];
            }
            if (sizeof($feedback_info) > 0) {
                $this->pdf->SetTextColor(255, 255, 255);
                $this->pdf->SetFont('', 'B');
                $this->pdf->Ln();
                $this->setBarColour();
                $this->pdf->Cell(array_sum($w), 5, 'PPC Feedback', 0, 0, 'L', true);
                $this->pdf->Ln();
                $this->setColourlight();
                $this->pdf->SetTextColor(0);
                $this->pdf->SetFont('');
                $newarray = explode("\n", $feedback_info);
                for ($temp = 0; $temp < sizeof($newarray) - 1; $temp++) {
                    $newpage = $this->pdf->countLines(array_sum($w), 4, stripslashes($newarray[$temp])."\n", '', 'L', true);
                    $newpage = $counter + $newpage;
                    $counter += $this->pdf->countLines(array_sum($w), 4, stripslashes($newarray[$temp])."\n", '', 'L', true);
                    if ($counter > 28 && $once == 0) {// Add a new page
                        $this->pdf->AddPage();
                        $this->pdffooter();
                        $this->pdfHeader();
                        $this->pdf->SetTextColor(255, 255, 255);
                        $this->pdf->SetFont('', 'B');
                        $this->pdf->Ln();
                        $this->setBarColour();
                        $this->pdf->Cell(array_sum($w), 5, 'PPC Feedback', 0, 0, 'L', true);
                        $this->pdf->Ln();
                        $this->setColourlight();
                        $this->pdf->SetTextColor(0);
                        $this->pdf->SetFont('');
                        $once = 1;
                    }
                    $this->pdf->MultiCell(array_sum($w), 4, stripslashes($newarray[$temp]), '', 'L', true);
                }
            }
        }
    }
    private function checkToken() {
        //$this->client->setAccessToken('ya29.AHES6ZQqMY3GQ6I6Cz-tAhtZX_cOReY1ShWMCRSFQ5hELArao4RS');
        $this->client->refreshToken('1/NBOsp-vNvt7TXgf7eGY1pulz28tMCsLLjzS4D0D6BPg');
    }
    private function sort($a, $b) {
        return strlen($a) - strlen($b);
    }
	
	protected function setNow() {
		$this->now = date("Y-m-d");
	}
	
	protected function getNow() {
		echo $this->now;
	}
	
    protected function setupPPC() {
        $this->campaignservice = $this->ppcobj->GetCampaignService('v201306');
        $this->token = $this->ppcobj->GetAuthToken();
        $this->ppcobj->SetClientId($this->ppcId);
        // select the client with clientId
        $this->adWordheaders = array("Authorization: GoogleLogin auth=$this->token", "clientCustomerId: ".$this->ppcId, "developerToken: 9sJL7F0dsHlqDYTKin1zMA");
    }
	
	protected function setupAnalytics() {
		$this->client = new apiClient();
        $this->client->setApplicationName('E-Web Analytics Reporting V3');
        $this->client->setClientId('1085794277755-0knq714he0mqdr0iom5dg3ku122q9d2n.apps.googleusercontent.com');
        $this->client->setClientSecret('pYkgEaIRc6ZnZ_ncHPh9aJUD');
        $this->client->setRedirectUri('http://www.ewebtracker.info/analyticsv3.php');
        $this->client->setAccessType('offline');
        $this->client->setUseObjects('true');
        $this->analytics = new apiAnalyticsService($this->client);
        //Check if the token is valid
        $this->checkToken();
		
        
	}
	
	public function setup($cid, $gpid, $ppcid, $user = NULL, $ppcdatestart, $cname, $goals, $ecom, $package, $rrfrom = "", $rrto = "") {
		$this->now = date('Y-m-d');
		$this->cid = $cid;
        $this->profileId = $gpid;
        
        $this->companyname = $cname;
        
        $fl = "2";
        if ($goals == "Y") {
            $this->goals = "Y";
			/*
            $goalsql = "SELECT analytics_conversions3, analytics_conversions3due, analytics_conversions6, analytics_conversions6due, analytics_conversions12, analytics_conversions12due FROM clientstatus WHERE company_id = '".$cid."'";
            $goalresult = mysql_query($goalsql);
            while ($goalrow = mysql_fetch_assoc($goalresult)) {
                $this->goals3 = $goalrow['analytics_conversions3'];
                $this->goals3due = $goalrow['analytics_conversions3due'];
                $this->goals6 = $goalrow['analytics_conversions6'];
                $this->goals6due = $goalrow['analytics_conversions6due'];
                $this->goals12 = $goalrow['analytics_conversions12'];
                $this->goals12due = $goalrow['analytics_conversions12due'];
            }
			 */
        }
        if ($ecom == "Y") {
            $this->ecom = "Y";
			/*
            $ecomsql = "SELECT analytics_ecommerce3, analytics_ecommerce3due, analytics_ecommerce6, analytics_ecommerce6due, analytics_ecommerce12, analytics_ecommerce12due FROM clientstatus WHERE company_id = '".$cid."'";
            $ecomresult = mysql_query($ecomsql);
            while ($ecomrow = mysql_fetch_assoc($ecomresult)) {
                $this->ecom3 = $ecomrow['analytics_ecommerce3'];
                $this->ecom3due = $ecomrow['analytics_ecommerce3due'];
                $this->ecom6 = $ecomrow['analytics_ecommerce6'];
                $this->ecom6due = $ecomrow['analytics_ecommerce6due'];
                $this->ecom12 = $ecomrow['analytics_ecommerce12'];
                $this->ecom12due = $ecomrow['analytics_ecommerce12due'];
            }
			 * 
			 */
        }
       /* $trafsql = "SELECT analytics_traffic3, analytics_traffic3due, analytics_traffic6, analytics_traffic6due, analytics_traffic12, analytics_traffic12due FROM clientstatus WHERE company_id = '".$cid."'";
        $trafresult = mysql_query($trafsql);
	    
	    
        while ($trafrow = mysql_fetch_assoc($trafresult)) {
            $this->traf3 = $trafrow['analytics_traffic3'];
            $this->traf3due = $trafrow['analytics_traffic3due'];
            $this->traf6 = $trafrow['analytics_traffic6'];
            $this->traf6due = $trafrow['analytics_traffic6due'];
            $this->traf12 = $trafrow['analytics_traffic12'];
            $this->traf12due = $trafrow['analytics_traffic12due'];
        }
	    * 
	    */
		
		$this->startdate = date("Y", strtotime($this->now." - 1 months"))."-".date("m", strtotime($this->now." - 1 months"))."-01";
        $this->enddate = date("Y", strtotime($this->now." - 1 months"))."-".date("m", strtotime($this->now." - 1 months"))."-".date("t", strtotime($this->now." - 1 months"));
        $this->prevstartdate = date("Y", strtotime($this->now." - ".$fl." months"))."-".date("m", strtotime($this->now." - ".$fl." months"))."-01";
        $this->prevenddate = date("Y", strtotime($this->now." - ".$fl." months"))."-".date("m", strtotime($this->now." - ".$fl." months"))."-".date("t", strtotime($this->now." - ".$fl." months"));
        $staff_query = mysql_query("SELECT E.firstname AS programmer_firstname, E.surname AS programmer_surname, E.email AS programmer_email,
E2.firstname AS ppc_firstname, E2.surname AS ppc_surname, E2.email AS ppc_email,
E3.firstname AS cm_firstname,E3.surname AS cm_surname, E3.email AS cm_email,
E4.firstname AS sales_firstname,E4.surname AS sales_surname, E4.email AS sales_email,
E5.firstname AS smm_firstname,E5.surname AS smm_surname, E5.email AS smm_email,
E6.firstname AS setter_firstname, E6.surname AS setter_surname, E6.email AS setter_email
										FROM clientstatus AS CL LEFT JOIN employee AS E ON CL.assigned_to_employee = E.employee_id,
											clientstatus AS CL2 LEFT JOIN employee AS E2 ON CL2.ppc_assigned_to_employee = E2.employee_id,
											clientstatus AS CL3 LEFT JOIN employee AS E3 ON CL3.cm_assigned_to_employee = E3.employee_id,
											company AS C LEFT JOIN employee AS E4 ON C.sales_employee_id = E4.employee_id,
											clientstatus AS CL5 LEFT JOIN employee AS E5 ON CL5.smm_assigned_to_employee = E5.employee_id,
											employee AS E6
										WHERE C.companyid = CL.company_id AND C.companyid = CL2.company_id AND C.companyid = CL3.company_id AND C.companyid = '".$this->cid."' LIMIT 1");
        $sql = mysql_query("SELECT contactname, contactsurname, email FROM company WHERE companyid = '".$this->cid."'");
        $client = mysql_fetch_assoc($sql);
        $this->clientname = $client['contactname']." ".$client['contactsurname'];
        $this->clientfirstname = $client['contactname'];
        $this->clientemail = $client['email'];
        $sql = mysql_query("SELECT homepage FROM company WHERE companyid = '".$this->cid."'");
        $homepage = mysql_fetch_assoc($sql);
        $this->homepage = $homepage['homepage'];
        $staff = mysql_fetch_assoc($staff_query);
        $this->cmname = $staff['cm_firstname']." ".$staff['cm_surname'];
        $this->cmemail = $staff['cm_email'];
        $this->seoname = $staff['programmer_firstname'];
        $this->seoemail = $staff['programmer_email'];
        $this->ppcname = $staff['ppc_firstname'];
        $this->ppcemail = $staff['ppc_email'];
        $this->smmname = $staff['smm_firstname'];
        $this->smmemail = $staff['smm_email'];
        
         if ($rrfrom && $rrto) {
            if ($rrto > $rrfrom) {
                $this->rrstart = $rrto;
                $this->rrto = $rrfrom;
            } else {
                $this->rrstart = $rrfrom;
                $this->rrto = $rrto;
            }
        }
        if ($ppcid) {
            $this->ppcId = $ppcid;
            $this->ppcobj = $user;
            $this->ppcdatestart = $ppcdatestart;
            $this->setupPPC();
        }
		
		$this->pdf = new FPDF();
		$this->setupAnalytics();
		if ($this->profileId > 0) {
            $this->checkTokenValid();
            $results = $this->analytics->data_ga->get('ga:'.$this->profileId, date("Y-m-01", strtotime($this->now." - 1 months")), date("Y-m-t", strtotime($this->now." - 1 months")), "ga:visits");
            if ($results) {
                //Make the chart here so we don't have to make it every god damn time
                $this->chart = $this->graph(array("day"), array("visits"));
            } else {// There's no data
                $checksql = "SELECT * FROM tasks AS T WHERE T.company_id = ".$this->cid." AND (T.task_description = 'The Analytics profile ID (".$this->profileId.") is incorrect or not available to ewebmarketing@gmail.com\n\n Please make sure we still have access and the profile ID is correct') LIMIT 1";
                $checkresult = mysql_query($checksql);
                if (mysql_num_rows($checkresult) == 0) {
                    $sql = "INSERT INTO tasks (company_id, task_description, about, critical, set_date, set_for_group, status) VALUES ('".$this->cid."','The Analytics profile ID (".$this->profileId.") is incorrect or not available to ewebmarketing@gmail.com\n\n Please make sure we still have access and the profile ID is correct','seo',1,'".date("Y-m-d H:i:s")."','programmer','active')";
                    $insertresult = mysql_query($sql) or die(mysql_error());
                }
                $this->profileId = 0;
            }
        }
	}

    function __construct() {
        $this->conn = mysql_connect($this->db_hostname, $this->db_username, $this->db_password);
        mysql_select_db($this->db_database, $this->conn);
    }
}
//$report = new Report('16629','1064466','','','','4mation Technologies Pty Ltd', 'Y','','Silver','','');
//$report->createReport('ranking');

?>
