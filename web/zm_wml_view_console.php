<?php
	if ( !$HTTP_SESSION_VARS[event_reset_time] )
		$HTTP_SESSION_VARS[event_reset_time] = "2000-01-01 00:00:00";

	$db_now = strftime( "%Y-%m-%d %H:%M:%S" );
	$sql = "select M.*, count(E.Id) as EventCount, count(if(E.StartTime>'$HTTP_SESSION_VARS[event_reset_time]' && E.Archived = 0,1,NULL)) as ResetEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by M.Id order by M.Id";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$monitors = array();
	$max_width = 0;
	$max_height = 0;
	$cycle_count = 0;
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( $max_width < $row[Width] ) $max_width = $row[Width];
		if ( $max_height < $row[Height] ) $max_height = $row[Height];
		$sql = "select count(Id) as ZoneCount, count(if(Type='Active',1,NULL)) as ActZoneCount, count(if(Type='Inclusive',1,NULL)) as IncZoneCount, count(if(Type='Exclusive',1,NULL)) as ExcZoneCount, count(if(Type='Inactive',1,NULL)) as InactZoneCount from Zones where MonitorId = '$row[Id]'";
		$result2 = mysql_query( $sql );
		if ( !$result2 )
			echo mysql_error();
		$row2 = mysql_fetch_assoc( $result2 );
		$monitors[] = array_merge( $row, $row2 );
		if ( $row['Function'] != 'None' ) $cycle_count++;
	}

	$sql = "select distinct Device from Monitors order by Device";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$devices = array();

	while( $row = mysql_fetch_assoc( $result ) )
	{
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zmc $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zmc'] = 1;
		}
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zma $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zma'] = 1;
		}
		$devices[] = $row;
	}
?>
<wml>
<card id="zmConsole" title="ZM - Console" ontimer="<?= $PHP_SELF ?>?view=<?= $view ?>">
<timer value="<?= REFRESH_MAIN*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - Console</strong></p>
<p mode="nowrap" align="center"><?= count($monitors) ?> Monitors - <?= strftime( "%T" ) ?></p>
<p mode="nowrap" align="center"><?= $HTTP_SESSION_VARS[event_reset_time] ?></p>
<p align="center">
<table columns="3">
<tr>
<td>Name</td>
<td>Func</td>
<td>Events</td>
</tr>
<?php
	$reset_event_count = 0;
	foreach( $monitors as $monitor )
	{
		$device = $devices[$monitor[Device]];
		$reset_event_count += $monitor[ResetEventCount];
?>
<tr>
<td><a href="<?= $PHP_SELF ?>?view=feed&amp;mid=<?= $monitor[Id] ?>"><?= $monitor[Name] ?></a></td>
<td><a href="<?= $PHP_SELF ?>?view=function&amp;mid=<?= $monitor[Id] ?>"><?= substr( $monitor['Function'], 0, 1 ) ?></a></td>
<td><a href="<?= $PHP_SELF ?>?view=events&amp;mid=<?= $monitor[Id] ?>"><?= $monitor[ResetEventCount] ?></a></td>
</tr>
<?php
	}
?>
</table>
</p>
<p mode="nowrap" align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;action=reset">Reset Event Counts</a></p>
</card>
</wml>