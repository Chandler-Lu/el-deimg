<?php
class StopWatch
{
	private function getCurrentTime()
	{
		list($usec, $sec) = explode(' ', microtime());
		$time = (float) $sec + (float) $usec;
		return $time;
	}

	public function start()
	{
		if (!$this->isStarted)
		{
			$this->start = $this->getCurrentTime();
			$this->isStarted = true;
		}
	}

	public function stop()
	{
		if ($this->isStarted)
		{
			$this->end = $this->getCurrentTime();
			$this->isStarted = false;
		}
	}

	function getDuration()
	{
		return $this->end - $this->start;
	}

	private $start = 0;
	private $end = 0;
	private $isStarted = false;
}

function toInt16($strBytes,$offset = 0) {
	$low = ord($strBytes[$offset]);
	$high = ord($strBytes[$offset+1]);

	return $high*256 + $low;
}

function toInt32($strBytes,$offset = 0) {
	$low = toInt16($strBytes,$offset);
	$high = toInt16($strBytes,$offset+2);

	return $high*65536 + $low;
}

function showMsgAndBack($msg) {
	echo <<< EOI

<html>
<head>
<META HTTP-EQUIV=Content-Type CONTENT="text/html; charset=UTF-8">
<script>
alert('$msg');
history.back();
</script>
</head>
<body>
</body>
</html>

EOI;
	exit;
}

function showMsgAndGo($msg, $newurl) {
	echo <<< EOI

<html>
<head>
<META HTTP-EQUIV=Content-Type CONTENT="text/html; charset=UTF-8">
<script>
alert('$msg');
window.location='$newurl';
</script>
</head>
<body>
</body>
</html>

EOI;
	exit;
}

function relocation($loc)
{
	header("location:$loc");
	exit;
}

?>