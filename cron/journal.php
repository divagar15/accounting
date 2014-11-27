<?php
session_start();
$con = mysql_connect('localhost','root','accelerated2020') or die(mysql_error());
$db1 = mysql_select_db("ummadc_accounting_ver2",$con) or die(mysql_error());

//echo '<pre>'; print_r($db1); echo '</pre>';



$company = mysql_query("SELECT database_name FROM company_database WHERE id!=1 AND fkcompany_id>=57") or die(mysql_error()); 
while($cr = mysql_fetch_array($company)){
	$database2 = $cr['database_name'];


$con2 = mysql_connect('localhost','root','accelerated2020') or die(mysql_error());
$db2 = mysql_select_db($database2,$con2) or die(mysql_error());

//echo '<pre>'; print_r($db2); echo '</pre>'; die();

	$currentDatetime = date('Y-m-d H:i:s');
	$currentDate = date('Y-m-d');



	$journal = mysql_query("SELECT * FROM $database2.journal_entries WHERE journal_status=1 AND delete_status=1 AND auto_reversal_id=0 AND auto_reversal_date='".$currentDate."'")or die(mysql_error());

	while($res = mysql_fetch_array($journal)){	

		$jnum = mysql_query("SELECT * FROM $database2.journal_entries ORDER BY journal_no DESC LIMIT 0,1");
		$jnumres = mysql_fetch_array($jnum);
		$jn = $jnumres['journal_no'];
		$journalno = ++$jn;
		
		$jourid = $res['id'];
		$currentDate = date('Y-m-d');

		$jentries = mysql_query("INSERT INTO $database2.journal_entries (fkcompany_id,journal_no,fklocation_id,date,description,attachment,approval_for,approval_date,auto_reversal,auto_reversal_date,auto_reversal_id,journal_status,delete_status,	date_created,date_modified) VALUES ('".$res['fkcompany_id']."','".$journalno."','".$res['fklocation_id']."','".$res['auto_reversal_date']."','".$res['description']."','".$res['attachment']."','".$res['approval_for']."','".$currentDate."','2','','0','1','1','".$currentDatetime."','".$currentDatetime."')")or die(mysql_error());	
		$newid = mysql_insert_id();
		$njid = $newid;
		
		$jlistQuery = mysql_query("SELECT * FROM $database2.journal_entries_list WHERE fkjournal_id='".$jourid."'")or die(mysql_error());
			
		while($r = mysql_fetch_array($jlistQuery)){	
			
		$jentries_list = mysql_query("INSERT INTO $database2.journal_entries_list (fkjournal_id,fkaccount_id,journal_description,debit,credit,bank_date,date_created,date_modified) VALUES ('".$njid."','".$r['fkaccount_id']."','".$r['journal_description']."','".$r['credit']."','".$r['debit']."','".$currentDatetime."','".$currentDatetime."','".$currentDatetime."')")or die(mysql_error());
		
		}
		
		$update = mysql_query("UPDATE $database2.journal_entries SET auto_reversal_id='".$njid."' WHERE id='".$jourid."'")or die(mysql_error());
	}

}

	
	

?>