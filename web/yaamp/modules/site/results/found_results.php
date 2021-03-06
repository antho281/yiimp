<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$showrental = (bool) YAAMP_RENTAL;

$algo = user()->getState('yaamp-algo');

$count = getparam('count');
$count = $count? $count: 50;

WriteBoxHeader("Last $count Blocks ($algo)");

$criteria = new CDbCriteria();
$criteria->condition = "t.category NOT IN ('stake','generated')";
$criteria->condition .= " AND IFNULL(coin.visible,1)=1"; // ifnull for rental
if($algo != 'all') {
	$criteria->condition .= " AND t.algo=:algo";
	$criteria->params = array(':algo'=>$algo);
}
$criteria->limit = $count;
$criteria->order = 't.time DESC';
$db_blocks = getdbolistWith('db_blocks', 'coin', $criteria);

echo <<<EOT

<style type="text/css">
span.block { padding: 2px; display: inline-block; text-align: center; min-width: 75px; border-radius: 3px; }
span.block.new       { color: white; background-color: #ad4ef0; }
span.block.orphan    { color: white; background-color: #d9534f; }
span.block.immature  { color: white; background-color: #f0ad4e; }
span.block.confirmed { color: white; background-color: #5cb85c; }
</style>

<table class="dataGrid2">
<thead>
<tr>
<td></td>
<th>Name</th>
<th align="right">Amount</th>
<th align="right">Difficulty</th>
<th align="right">Block</th>
<th align="right">Time</th>
<th align="right">Status</th>
</tr>
</thead>
EOT;

foreach($db_blocks as $db_block)
{
	$d = datetoa2($db_block->time);
	if(!$db_block->coin_id)
	{
		if (!$showrental)
			continue;

		$reward = bitcoinvaluetoa($db_block->amount);

		echo "<tr class='ssrow'>";
		echo "<td width=18><img width=16 src='/images/btc.png'></td>";
		echo "<td><b>Rental</b><span style='font-size: .8em'> ($db_block->algo)</span></td>";
		echo "<td align=right style='font-size: .8em'><b>$reward BTC</b></td>";
		echo "<td align=right style='font-size: .8em'></td>";
		echo "<td align=right style='font-size: .8em'></td>";
		echo "<td align=right style='font-size: .8em'>$d ago</td>";
		echo "<td align=right style='font-size: .8em'>";
		echo "<span style='padding: 2px; color: white; background-color: #5cb85c'>Confirmed</span>";
		echo "</td>";
		echo "</tr>";

		continue;
	}

	$reward = round($db_block->amount, 3);
	$coin = $db_block->coin ? $db_block->coin : getdbo('db_coins', $db_block->coin_id);
	$difficulty = Itoa2($db_block->difficulty, 3);
	$height = number_format($db_block->height, 0, '.', ' ');

	$link = $coin->createExplorerLink($coin->name, array('hash'=>$db_block->blockhash));

	echo '<tr class="ssrow">';
	echo '<td width="18"><img width="16" src="'.$coin->image.'"></td>';
	echo "<td><b>$link</b><span style='font-size: .8em'> ($coin->algo)</span></td>";
	echo "<td align=right style='font-size: .8em'><b>$reward $coin->symbol_show</b></td>";
	echo "<td align=right style='font-size: .8em' title='found $db_block->difficulty_user'>$difficulty</td>";
	echo "<td align=right style='font-size: .8em'>$height</td>";
	echo "<td align=right style='font-size: .8em'>$d ago</td>";
	echo "<td align=right style='font-size: .8em'>";

	if($db_block->category == 'orphan')
		echo '<span class="block orphan">Orphan</span>';

	else if($db_block->category == 'immature')
		echo '<span class="block immature">Immature ('.$db_block->confirmations.')</span>';

	else if($db_block->category == 'generate')
		echo '<span class="block confirmed">Confirmed</span>';

	else if($db_block->category == 'new')
		echo '<span class="block new">New</span>';

	echo "</td>";
	echo "</tr>";
}

echo "</table>";

echo "<br></div></div><br>";




