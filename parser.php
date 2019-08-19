<?Php
require 'vendor/autoload.php';
use datagutten\mobitec\decoder;
if(!empty($argv[1]) && file_exists($argv[1]))
{
    $data = file_get_contents($argv[1]);
    try {
        $output = decoder::parse(file_get_contents($argv[1]));
        print_r($output);
    }
    catch (Exception $e)
    {
        printf("Parse error: %s\n", $e->getMessage());
    }
}
else
	echo "A valid file must be specified as the first command line argument\n";