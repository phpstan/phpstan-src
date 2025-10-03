<?php

namespace Bug13434;

extract( $_GET, EXTR_REFS | EXTR_PREFIX_ALL );
extract( $_GET, EXTR_REFS | EXTR_SKIP );


$ftp = ftp_connect('ftp.example.com');
assert($ftp != false);
ftp_set_option($ftp, FTP_TIMEOUT_SEC | FTP_AUTOSEEK | 1337, true);
