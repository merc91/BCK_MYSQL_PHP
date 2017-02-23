<?php

/*
 *      Script Backup Mysql
 *      Davide Mercuri
 *
 *      Data Creazione: 18/07/2015
 *
 *      v0.1 -  18/07/2015 - INIZIO SCRIPT
 */


//  Variabili Globali

$host = "host.domain.com";                                    //INSERIRE NOME SERVER

$hostDB = "localhost";                                        //INSERIRE HOST DATABASE
$userDB = "root";                                             //INSERIRE USER DB
$passDB = "r00tdb0";                                          //INSERIRE PASS DB

$home_bck_loc = "/home/mysqlbackup/";                         //INSERIRE IL PERCORSO DELLA CARTELLA LOCALE
$home_bck_remote_assoluto="/home/mysqlbackup/";               //INSERIRE IL PERCORSO DELLA CARTELLA REMOTA
$home_bck_remote = "remote_dir";                              //INSERIRE IL NOME DELLA CARTELLA REMOTA

$userFTP = "user_ftp";                                        //INSERIRE USER FTP
$passFTP = "pass_ftp";                                        //INSERIRE PASS FTP
$hostFTP = "hostftp.domain.com";                              //INSERIRE HOST REMOTO FTP

$home_ncftpput = "/usr/bin/ncftpput";                         //INSERIRE PERCORSO NCFTPPUT
$parametri_ncftpput = "-DD";                                  //INSERIRE PARAMETRI PER NCFTPPUT
$nullFTP = "1>/dev/null 2>/dev/null";

$emailFROM = "bckmysql@domain.com";                           //INSERIRE FROM MAIL
$emailFROM_SENDMAIL = "FROM: bckmysql@domain.com";            //INSERIRE FROM MAIL PER SENDMAIL
$emailTO = "send@domain.com,send_2@domain.com";               //INSERIRE DESTINATARIO MAIL A

$home_mysqldump = "/usr/bin/mysqldump";
$opt_mysqldump = "--databases";

$current_date = date("d-m-Y");

$emailSubject = "BCK MYSQL SCRIPT ".$host;
$emailText = "\r\nEsecuzione script backup mysql per ".$host."\r\n";

// Connessione DATABASE

$connDB = mysql_connect($hostDB,$userDB,$passDB) or die ("Impossibile collegarsi all'host DB selezionato");
mysql_select_db("mysql",$connDB);

$emailDUMP = dump_all_db();
$emailFTP = esegui_ftp();
$email = $emailDUMP."\r\n".$emailFTP;
send_mail($email);

function get_db_name(){
    global $connDB;
    $arrayDB = Array();
    $qTROVA_DB = "SHOW DATABASES;";
    $rTROVA_DB = mysql_query($qTROVA_DB,$connDB);
    while($dTROVA_DB = mysql_fetch_assoc($rTROVA_DB)){
        $arrayDB[] = $dTROVA_DB['Database'];
    }
    return $arrayDB;
}

function stampa_db_name(){
    $aDB = get_db_name();
    echo "Database Trovati: \r\n";
    foreach($aDB as $nomeDB){
        echo $nomeDB."\r\n";
    }
}

function dump_all_db(){
    global $host;
    global $hostDB;
    global $userDB;
    global $passDB;
    global $home_bck_loc;
    global $current_date;
    global $home_mysqldump;
    global $opt_mysqldump;
    $pass = 0;
    $err = 0;
    $emailScript = "\r\n";
    $db_to_dump = Array();

    $db_to_dump =  get_db_name();

    foreach($db_to_dump as $nomeDB){
        $filename_dump = $home_bck_loc."".$nomeDB."_".$current_date.".sql.gz";
        $cmd = $home_mysqldump." --user=".$userDB." --password=".$passDB." ".$opt_mysqldump." ".$nomeDB." | gzip -9 > ".$filename_dump;
        $risultato = exec($cmd,$out,$return);
        //print_r ($return);
        if($return == 0){
            //echo "MYSQL DUMP ".$nomeDB." OK\r\n";
            $emailScript.= "MYSQL DUMP ".$nomeDB." OK\r\n";
            $pass++;
        }else{
            //echo "MYSQL DUMP ".$nomeDB." ERROR\r\n";
            $emailScript.= "MYSQL DUMP ".$nomeDB." ERROR\r\n";
            $err++;
        }

    }
    //echo $emailScript;
    if($err == 0){
        $emailScript .= "\r\nEsecuzione DUMP avvenuta con successo\r\n";
    }else{
        $emailScript .= "\r\nErrore nell'esecuzione del DUMP\r\n";
    }


    return $emailScript;
}

function esegui_ftp(){
    global $userFTP;
    global $passFTP;
    global $hostFTP;
    global $home_ncftpput;
    global $parametri_ncftpput;
    global $nullFTP;
    global $home_bck_loc;
    global $home_bck_remote;

    $okFTP = 0;
    $errFTP = 0;
    $mailFTP = "\r\n";

    exec("ls ".$home_bck_loc,$out);
    foreach ($out as $file) {
        $file = $home_bck_loc."".$file;
        //echo $file."\n";;
        $cmd = "$home_ncftpput $parametri_ncftpput -u $userFTP -p $passFTP $hostFTP $home_bck_remote $file $nullFTP";
        //echo $cmd."\n";
        exec ($cmd,$out1,$risultato);
        if($risultato == 0){
            //echo "FTP ".$file." OK\r\n";
            $mailFTP.= "FTP ".$file." OK\r\n";
            $okFTP++;
            sleep(1);
        }else{
            //echo "FTP ".$file." ERROR\r\n";
            $mailFTP.= "FTP ".$file." ERROR\r\n";
            $errFTP++;
            sleep(1);
        }
    }

    if($errFTP == 0){
        $mailFTP .= "\r\nEsecuzione FTP avvenuta con successo\r\n";
    }else{
        $mailFTP .= "\r\nErrore nell'esportazione dei DUMP\r\n";
    }

    return $mailFTP;

}

function send_mail($emailScript){
    global $emailFROM;
    global $emailTO;
    global $emailCC;
    global $emailSubject;
    global $emailText;
    global $hostFTP;
    global $home_bck_remote_assoluto;
    global $home_bck_remote;

    $corpo_email = $emailText."".$emailScript;
    $corpo_email .= "\r\nFILE esportati su ".$hostFTP." -> ".$home_bck_remote_assoluto."".$home_bck_remote."\r\n";
    $corpo_email .= "\r\nEsecuzione script terminata\r\n";

    mail($emailTO,$emailSubject,$corpo_email,$emailFROM_SENDMAIL);

}
?>
