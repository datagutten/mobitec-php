<?Php
require 'vendor/autoload.php';
use datagutten\mobitec\decoder;
use datagutten\mobitec\visualizer;

if(!isset($argv[1]))
	die('Usage: visualizer.php [file] [sign]');

$options=getopt('',array('file:','list','key:','print'));
if(!isset($options['file']))
{
	$argvr=array_reverse($argv);
	$file=$argvr[0];
}
else
	$file=$options['file'];

$lines= decoder::parse(file_get_contents($file), true);
$pathinfo=pathinfo($file);
chdir(__DIR__);
if(isset($options['list']))
{
	foreach($lines as $key=>$line)
	{
		echo "-----$key-----\n";
		echo trim(implode("\n",array_column($lines[$key],'Text')))."\n";
	}
}
else
{
	if(isset($options['key']))
	{
		if(!isset($lines[$options['key']]))
			die(sprintf("Invalid key %s, valid keys are:\n%s\n",$options[$key],implode("\n",array_keys($lines))));
		$lines=array($options['key']=>$lines[$options['key']]);
	}
	if(isset($options['print']))
		print_r($lines);
	if(count($lines)>1)
		$outdir=sprintf('visualized/%s',$pathinfo['basename']);
	else
		$outdir='visualized';
	if(!file_exists($outdir))
		mkdir($outdir,0777,true);
	foreach($lines as $key=>$line)
	{
		$im=visualizer::visualize($line);
		imagepng($im,$outfile=sprintf('%s/%s-%s.png',$outdir,$pathinfo['basename'],$key));
		echo sprintf(_('Wrote message %s to %s'),$key,$outfile)."\n";
	}
}