<?Php
require_once 'class_mobitec.php';
class mobitec_visualizer extends mobitec
{
	function __construct()
	{
		if(!function_exists('imagecreatetruecolor'))
			throw new Exception('GD library is required for visualization, but is not installed');
	}
	//Create a GD image of the data
	function visualize($data, $debug=false)
	{
		if(!is_array($data))
			throw new InvalidArgumentException('Data is not array');
		if(!isset($data[0]['Text']))
			throw new InvalidArgumentException('No text');

		foreach($data as $key=>$line) //Loop through the lines
		{
			if(!is_array($line))
				continue;

			if(!isset($line['Font']))
			{
				if($debug)
					echo sprintf("No font defined for line %s, defaulting to Mobitec Std h 16x9 15\n",$key+1);
				$line['Font']=104;
			}
			//If offset not defined, use offset from previous line
			if(!isset($line['Horizontal offset']))
			{
				if(isset($data[$key-1]['Horizontal offset']))
					$line['Horizontal offset']=$data[$key-1]['Horizontal offset'];
				else
					$line['Horizontal offset']=0;
			}
			if(!isset($line['Vertical offset']))
			{
				if(isset($data[$key-1]['Vertical offset']))
					$line['Vertical offset']=$data[$key-1]['Vertical offset'];
				else
					$line['Vertical offset']=0;
			}

			if(!isset($im))
			{
				if(isset($line['Width']) && isset($line['Height']))
					$im=imagecreatetruecolor($line['Width'],$line['Height']);
				else
					$im=imagecreatetruecolor(120,20); //Create with default size
				if($this->debug)
				{
					$im=imagecreatetruecolor(130,30); //Create extra large
					imagefill($im,0,0,imagecolorallocate($im,255,0,255)); //Fill with purple outside the sign
					$rectangle=imagefilledrectangle($im,0,0,$line['Width']-1,$line['Height']-1,imagecolorallocate($im,0,0,255)); //Create a blue rectangle with the sign size
				}
				else
				{
					imagefill($im,0,0,$bg=imagecolorallocate($im,255,255,255)); //Fill with white
					imagecolortransparent($im,$bg);
				}
					
			}
			if($this->debug) //Write a line at the text start position
				imageline($im,$line['Horizontal offset'],$line['Vertical offset'],$line['Horizontal offset']+10,$line['Vertical offset'],imagecolorallocate($im,255,255,0));
				$pos=$line['Horizontal offset']; //Position for first character
			for($i=0; $i<mb_strlen($line['Text']); $i++) //Loop through the characters
			{
				$char=mb_substr($line['Text'],$i,1);
				$charcode=$this->uniord($char);
				$charfile=sprintf('fonts/font_%s/%s.png',$line['Font'],$charcode); //Combine the font id and the ASCII code of the character
				if(!file_exists(sprintf('fonts/font_%s',$line['Font'])))
				{
					if($debug)
						echo sprintf("Missing font %s\n",$line['Font']);
					break;
				}
				if(file_exists($charfile))
				{
					$im_char=imagecreatefrompng($charfile);
					$char_width=imagesx($im_char); //Get the width of the current character
					$font_height=imagesy($im_char);

					if($i==0) //Remove character spacing for first character
					{
						for($x=0; $x<imagesx($im_char); $x++)
						{
							for($y=0; $y<imagesy($im_char); $y++)
							{
								if(imagecolorat($im_char,$x,$y)<0xFFFFFF) //Color found
									break 2;
							}
							$pos--; //Remove white row
						}
					}
					imagecopy($im,$im_char,$pos,$line['Vertical offset']-$font_height+1,0,0,$char_width,imagesy($im_char));
					$pos=$pos+$char_width; //Next position is current posision plus current character width
				}
				elseif($debug)
					echo sprintf('Missing image for character %s in font %s, Hex: %s Dec: %s Pos: %s',$char,$line['Font'],dechex($charcode),$charcode,$i)."\n";
			}
		}
		return $im;
	}
	function enlarge($im,$multiplier=10) //Enlarge a visualized sign
	{
		if(!is_resource($im))
			return false;
		$im_large=imagecreatetruecolor(imagesx($im)*$multiplier,imagesy($im)*$multiplier);
		imagefill($im_large,0,0,$bg=imagecolorallocate($im_large,255,255,255)); //Fill with white
		imagecolortransparent($im_large,$bg);
		imagecopyresized($im_large,$im,0,0,0,0,imagesx($im)*$multiplier,imagesy($im)*$multiplier,imagesx($im),imagesy($im));
		return $im_large;
	}
}