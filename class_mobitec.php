<?Php
//A class to decode and create messages for mobitec flipdot signs
class mobitec
{
	public $error;
	//Parse data
	function parse($data,$show_output=false)
	{
		$matches=preg_match_all($pattern=sprintf('/%1$s.+?%1$s/s',chr(0xff)),$data,$signs); //Get all signs
		//print_r($signs);
		if($matches==0)
		{
			$this->error=_('No signs found');
			return false;
		}
		$fields=array(0xFF=>'Address',0xD0=>'Width',0xD1=>'Height',0xD2=>'Horizontal offset',0xD3=>'Vertical offset',0xD4=>'Font'); //Field definitions

		foreach($signs[0] as $signkey=>$sign)
		{
			if($sign==chr(0xFF).chr(0x01).chr(0xFF)) //ICU transmits 0xFF 0x01 0xFF continiously when not changing
				continue;
			if(substr($sign,0,1)!=chr(0xFF) || substr($sign,2,1)!=chr(0xA2)) //Valid messages starts with 0xFF, then the sign address and then 0xA2
			{
				echo sprintf("Invalid sign, first byte is %s, third is %s\n",dechex(ord($sign[0])),dechex(ord($sign[2])));
				continue;
			}
			if($show_output)
				echo "----\n\n";
			$message='';
			$linekey=0;
			for($i=0; $i<strlen($sign)-2; $i++) //Loop through the bytes, omitting the last 0xFF
			{
				$byte=ord($sign[$i]); //Get the ASCII code
				if($byte==0xA2)
					continue;
	
				if(isset($fields[$byte])) //Control character
				{
					if($show_output)
						echo sprintf("%s: %s\n",$fields[$byte],dechex(ord($sign[$i+1])));
					$lines[$signkey][$linekey][$fields[$byte]]=ord($sign[$i+1]);
					$last_printable=false;
					$i++;
				}
				else //ASCII
				{
					if($show_output)
					{
						if($last_printable===false && (!ctype_print($sign[$i-1]) || !ctype_print($sign[$i-2])))
							echo "<<";
						echo $sign[$i];
					}
	
					$message.=$sign[$i];
					//Message end is when the next character is a control character or the second next is 0xFF
					if($sign[$i+2]==chr(0xFF) || isset($fields[ord($sign[$i+1])]))
					{
						//echo ">>";
						$lines[$signkey][$linekey]['Text']=$message;
						$message='';
						$linekey++;
					}
					/*if($last_printable===false)
						$message."\n";*/
					
					$last_printable=true;
				}
	
			}
			$message_start=strpos($sign,chr(0xD4))+2; //Message starts two bytes after 0xD4
			if($show_output)
			{
				echo "Message: ".substr($sign,$message_start,strlen($sign)-$message_start-2)."\n";
				echo "\nMessage: $message\n";
				echo sprintf("Address: %s\n",ord(substr($sign,1,1)));
			}
			if(!isset($lines[$signkey][0]['Text']))
				unset($lines[$signkey]);

		}
		if(empty($lines))
		{
			$this->error=_('No valid signs found');
			return false;
		}
		return array_values($lines);
	}

	/*Write text at specified position with specified font
	If parameteres are not specified the parameters from the previous text are re-used
	*/
	public function write_text($text,$x=false,$y=false,$font=false)
	{
		$output='';
		$text=str_replace(array('å'),array(chr(0x7d)),$text);
		if($x!==false) //X position
			$output.=chr(0xD2).chr($x);
		if($y!==false) //Y position
			$output.=chr(0xD3).chr($y);
		if($font!==false) //Font
			$output.=chr(0xD4).chr($font);
		$output.=$text;
		return $output;
	}
	//Calculate checksum
	function checksum($output)
	{
		/*if(substr($output,0,1)!=chr(0xFF) || substr($output,-1,1)!=chr(0xFF))
			return false;*/
		$stringarray=str_split($output,1); //Split the string into an array
		$numarray=array_map('ord',$stringarray); //Turn the array into numeric character code
		$sum=array_sum($numarray); //Get the sum of the array
		$checksum=$sum & 0xFF;
		
		if($checksum==0xFE)
			$checksum=chr(0xFE).chr(0x00);
		elseif($checksum==0XFF)
			$checksum=chr(0xFE).chr(0x01);
		else
			$checksum=chr($checksum);

		return $checksum;
	}
	//Add header and checksum to data to make it ready to send to sign
	function output($data,$address=0x07,$width,$height)
	{
		//Add header
		$output=chr(0xff).chr($address).chr(0xa2); //Start byte, address and text marker
		$output.=chr(0xD0).chr($width); //Width
		$output.=chr(0xD1).chr($height); //Height

		$output.=$data; //Add data
		$output.=$this->checksum(substr($output,1)); //Add checksum (calucated without start byte)
		$output.=chr(0xFF); //And end byte
		return $output;
	}
	
	//Format a value for debugging
	function format_value($string,$return_as_hex=false) //Convert a string to character codes
	{
		$chars=str_split($string); //Split the string into an array
		$chars_dec=array_map('ord',$chars); //Get the character code for each char
		$chars_hex=array_map('dechex',$chars_dec); //Convert the character codes to hex
		foreach($chars_hex as $key=>$char)
			$chars_hex[$key]=str_pad($char,2,'0',STR_PAD_LEFT); //Pad each hex char

		$number_hex=implode('-',$chars_hex); //Merge the chars to a string

		if($return_as_hex===false)
			return hexdec($number_hex); //Return as decimal integer
		else
			return $number_hex; //Return as hex string
	}

	/*Get the character code for a multi-byte character
	Found at: http://php.net/manual/en/function.ord.php#42778
	*/
	function uniord($u) { 
		$k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8'); 
		$k1 = ord(substr($k, 0, 1)); 
		$k2 = ord(substr($k, 1, 1)); 
		return $k2 * 256 + $k1; 
	}
}
