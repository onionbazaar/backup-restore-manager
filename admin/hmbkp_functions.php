<?PHP
function hmbkp_validateDate( $date, $format = 'Y-m-d-H-i-s' )
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function hmbkp_returntimestamp( $date, $format = 'Y-m-d-H-i-s' )
{
	$dateTimeObject = \DateTime::createFromFormat($format, $date);
	$result = $dateTimeObject->format('Y-m-d H:i:s');	
	return $result;
}

?>
