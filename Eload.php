<?php

class Eload {
	
	private $MobileNo = '0123456789'; // SMS Reload System Mobile No
	private $Password = 'blablabla'; // SMS Reload System Password
	private $AgentUserName = '78912'; // AgentUserName cookies to bypass TAC request
	private $agentID = '6666'; // registered agentID
	public $systemURL = 'http://futureeload.dyndns.biz';
	
	public $loginURL = $systemURL.'/agent/Login.aspx';
	public $reloadURL = $systemURL.'/agent/Reload.aspx';
	public $reportURL = $systemURL.'/agent/ReportAgentSales.aspx';
	
	// login
	function __construct()
	{
		$this->ckfile = tempnam ("/tmp", "CURLCOOKIE");
		
		$ch = curl_init();      
        curl_setopt($ch, CURLOPT_URL, $this->loginURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		$content = curl_exec($ch); 
		$data = $this->getAspFormVars($content);
		$data['ctl00$ContentPlaceHolder1$txtMISISN'] = $this->MobileNo;
		$data['ctl00$ContentPlaceHolder1$txtPassword'] = $this->Password;
		$data['ctl00$ContentPlaceHolder1$LoginButton'] = 'Login';
		
		curl_setopt($ch, CURLOPT_COOKIE, "AgentUserName=".$this->AgentUserName);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $this->loginURL);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
		
		$content = curl_exec($ch);
		$this->ch = $ch;
		$this->aspFormVars = $this->getAspFormVars($content, true);
	}
	
	function reload($telco, $amount, $mobileno)
	{
		$data = $this->aspFormVars;
		$topup_data = array(
			'ctl00$ContentPlaceHolder1$DDLTelco' => strtoupper($telco),
			'ctl00$ContentPlaceHolder1$DDLReloadAmount' => $amount,
			'ctl00$ContentPlaceHolder1$txtReloadMSISDN' => str_replace(array('+',' ','-'), '', $mobileno),
			'ctl00$ContentPlaceHolder1$DDLTelco2' => 'MAXIS',
			'ctl00$ContentPlaceHolder1$DDLReloadAmount2' => '5',
			'ctl00$ContentPlaceHolder1$txtReloadMSISDN2' => '01',
			'ctl00$ContentPlaceHolder1$DDLTelco3' => 'MAXIS',
			'ctl00$ContentPlaceHolder1$DDLReloadAmount3' => '5',
			'ctl00$ContentPlaceHolder1$txtReloadMSISDN3' => '01',
			'ctl00$ContentPlaceHolder1$DDLTelco4' => 'MAXIS',
			'ctl00$ContentPlaceHolder1$DDLReloadAmount4' => '5',
			'ctl00$ContentPlaceHolder1$txtReloadMSISDN4' => '01',
			'ctl00$ContentPlaceHolder1$DDLTelco5' => 'MAXIS',
			'ctl00$ContentPlaceHolder1$DDLReloadAmount5' => '5',
			'ctl00$ContentPlaceHolder1$txtReloadMSISDN5' => '01'
		);
		$submit_data = array(
			'ctl00$ContentPlaceHolder1$btnSubmit' => 'Submit',
			'ctl00$ContentPlaceHolder1$ScriptManager1' => 'ctl00$ContentPlaceHolder1$UpdatePanel1|ctl00$ContentPlaceHolder1$btnSubmit'
		);
		$confirm_data = array(		
			'ctl00$ContentPlaceHolder1$btnConfirm' => 'Confirm',
			'ctl00$ContentPlaceHolder1$ScriptManager1' => 'ctl00$ContentPlaceHolder1$UpdatePanel1|ctl00$ContentPlaceHolder1$btnConfirm'
		);
		$ch = $this->ch;
		// send topup  
        curl_setopt($ch, CURLOPT_URL, $this->reloadURL);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, $topup_data, $submit_data));
		
		$result = curl_exec($ch);
		$data = $this->getAspFormVars($result, true);
		
		// confirm topup
        curl_setopt($ch, CURLOPT_URL, $this->reloadURL);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, $topup_data, $confirm_data));
		
		$result = curl_exec($ch);
        
        if($result === false) {
            $result = curl_error($ch);
        }   
            
        curl_close($ch);
		
		include 'simple_html_dom.php'; 
		// return "Reloading..." if successful
		// ctl00_ContentPlaceHolder1_lblErrorMsg2, ctl00_ContentPlaceHolder1_lblErrorMsg3, ctl00_ContentPlaceHolder1_lblErrorMsg4, ctl00_ContentPlaceHolder1_lblErrorMsg5 for other reload status
		return str_get_html($result)->find('span[id="ctl00_ContentPlaceHolder1_lblErrorMsg"] b font', 0)->plaintext;
	}
	
	function getReport($fromdate, $todate)
	{
		$query_data = array(
			'ctl00$ContentPlaceHolder1$txtAgentID' => $this->agentID,
			'ctl00$ContentPlaceHolder1$txtDateFrom' => date('Ymd', strtotime($fromdate)),
			'ctl00$ContentPlaceHolder1$txtDateTo' => date('Ymd', strtotime($todate)),
			'ctl00$ContentPlaceHolder1$btnSearch' => 'Search'
		);		
		$ch = $this->ch;
		
		// get reportURL
		curl_setopt($ch, CURLOPT_URL, $this->reportURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		$result = curl_exec($ch);
		$data = $this->getAspFormVars($result);
		
		// send query  
        curl_setopt($ch, CURLOPT_URL, $this->reportURL);   
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($data, $query_data));
		
		$result = curl_exec($ch);
        
        if($result === false) {
            $result = curl_error($ch);
        }   
            
        curl_close($ch);
		
		include 'simple_html_dom.php';
		$table = str_get_html($result)->find('table[id="ctl00_ContentPlaceHolder1_GridView1"] tbody', 0);
		if(!$table)
			return false;
		foreach($table->find('tr', 0)->find('th font b a font') as $header)
		{
			$header_data[] = $header->plaintext;
		}
		$result_data[] = $header_data;
		
		foreach($table->find('tr') as $tr)
		{				
			$content_data = array();
			foreach($tr->find('td font') as $content)
			{
				$content_data[] = $content->plaintext;			
			}
			if($content_data)
				$result_data[] = $content_data;
		}
		// return array for table populating
		return $result_data;
	}
	
	function getAspFormVars($htmlcontent, $extras = false)
	{
		$regs = array();
		$regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
		$regexEventVal  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';
		
		$viewstate = $this->regexExtract($htmlcontent,$regexViewstate,$regs,1);
        $eventval = $this->regexExtract($htmlcontent, $regexEventVal,$regs,1);
        
        $data['__VIEWSTATE']=$viewstate;
        $data['__EVENTVALIDATION']=$eventval;
		if($extras)
			$data = array('__EVENTTARGET' => '',
				'__EVENTARGUMENT' => '',
				'__VIEWSTATEENCRYPTED' => '',
				'__ASYNCPOST' => true,
				'__LASTFOCUS' => '');
		
		return $data;
	}
	
	function regexExtract($text, $regex, $regs, $nthValue) {
            if (preg_match($regex, $text, $regs)) {
                $result = $regs[$nthValue];
            } else {
                $result = "";
            }
            return $result;
        }
}
?>