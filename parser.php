<?Php
require 'class_mobitec.php';
$mobitec=new mobitec;
if(!empty($argv[1]) && file_exists($argv[1]))
{
	$output=$mobitec->parse(file_get_contents($argv[1]));
	if($output===false)
		echo $mobitec->error."\n";
	else
		print_r($output);
}
else
	echo "A valid file must be specified as the first command line argument\n";