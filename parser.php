<?Php
require 'vendor/autoload.php';
use datagutten\mobitec\decoder;


$mobitec=new mobitec;
if(!empty($argv[1]) && file_exists($argv[1]))
{
	$output= decoder::parse(file_get_contents($argv[1]));
    print_r($output);
}
else
	echo "A valid file must be specified as the first command line argument\n";