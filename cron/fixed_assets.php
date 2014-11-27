<?php
session_start();
$con = mysql_connect('localhost','root','accelerated2020') or die(mysql_error());
$db1 = mysql_select_db("ummadc_accounting_ver2",$con) or die(mysql_error());

$company = mysql_query("SELECT database_name FROM company_database WHERE id!=1 AND fkcompany_id>=57") or die(mysql_error()); 
while($cr = mysql_fetch_array($company)){
	$database2 = $cr['database_name'];


$con2 = mysql_connect('localhost','root','accelerated2020') or die(mysql_error());
$db2 = mysql_select_db($database2,$con2) or die(mysql_error());
$currentDatetime = date('Y-m-d H:i:s');
$currentDate = date('Y-m-d');




$assets = mysql_query("SELECT fa.*,fas.amount,a.id as aid,fas.id as fasid FROM fixed_assets as fa LEFT JOIN fixed_assets_schedule as fas ON fa.id=fas.fixedasset_id LEFT JOIN account as a ON fa.fa_coa=a.ref_id WHERE fas.date='".$currentDate."' AND fas.status=2 AND fa.status=1 AND fa.delete_status=1")or die(mysql_error());


while($res = mysql_fetch_array($assets)){	

	$no_description = $res['fa_no'].'-'.$res['fa_description'];
	$jnum = mysql_query("SELECT * FROM journal_entries ORDER BY journal_no DESC LIMIT 0,1");
	$jnumres = mysql_fetch_array($jnum);
	$jn = $jnumres['journal_no'];
	$journalno = ++$jn;

	$currentDate = date('Y-m-d');
	
	$jentries = mysql_query("INSERT INTO journal_entries (fkcompany_id,journal_no,fklocation_id,date,description,attachment,approval_for,approval_date,auto_reversal,auto_reversal_date,auto_reversal_id,journal_status,delete_status,	date_created,date_modified) VALUES ('".$res['fkcompany_id']."','".$journalno."','".$res['fklocation_id']."','".$currentDate."','".$no_description."','','0','".$currentDate."','2','','0','1','1','".$currentDatetime."','".$currentDatetime."')")or die(mysql_error());	
	$newid = mysql_insert_id();
	$njid = $newid;
	
	$jentries_list = mysql_query("INSERT INTO journal_entries_list (fkjournal_id,fkaccount_id,journal_description,debit,credit,bank_date,date_created,date_modified) VALUES ('".$njid."','15','".$no_description."','".$res['amount']."','0.00','".$currentDatetime."','".$currentDatetime."','".$currentDatetime."')")or die(mysql_error());
	
	$jentries_list = mysql_query("INSERT INTO journal_entries_list (fkjournal_id,fkaccount_id,journal_description,debit,credit,bank_date,date_created,date_modified) VALUES ('".$njid."','".$res['aid']."','".$no_description."','0.00','".$res['amount']."','".$currentDatetime."','".$currentDatetime."','".$currentDatetime."')")or die(mysql_error());
	
	$fasUpdate     = mysql_query("UPDATE fixed_assets_schedule SET status=1 WHERE id='".$res['fasid']."'");
}

}

	
	

?>